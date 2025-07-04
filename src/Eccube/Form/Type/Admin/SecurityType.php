<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SecurityType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * SecurityType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     * @param ValidatorInterface $validator
     * @param RequestStack $requestStack
     * @param RouterInterface $router
     */
    public function __construct(EccubeConfig $eccubeConfig, ValidatorInterface $validator, RequestStack $requestStack, RouterInterface $router)
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->validator = $validator;
        $this->requestStack = $requestStack;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $allowHosts = $this->eccubeConfig->get('eccube_admin_allow_hosts');
        $allowHosts = implode("\n", $allowHosts);

        $denyHosts = $this->eccubeConfig->get('eccube_admin_deny_hosts');
        $denyHosts = implode("\n", $denyHosts);

        $routes = $this->getRouteCollection();
        $allowFrontHosts = $this->eccubeConfig->get('eccube_front_allow_hosts');
        $allowFrontHosts = implode("\n", $allowFrontHosts);

        $denyFrontHosts = $this->eccubeConfig->get('eccube_front_deny_hosts');
        $denyFrontHosts = implode("\n", $denyFrontHosts);

        $builder
            ->add('admin_route_dir', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                    new Assert\Regex([
                        'pattern' => '/\A\w+\z/',
                    ]),
                    new Assert\Regex([
                        'pattern' => "/^(?!($routes)$).*$/",
                    ]),
                ],
                'data' => $this->eccubeConfig->get('eccube_admin_route'),
            ])
            ->add('front_allow_hosts', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_ltext_len']]),
                ],
                'data' => $allowFrontHosts,
            ])
            ->add('front_deny_hosts', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_ltext_len']]),
                ],
                'data' => $denyFrontHosts,
            ])
            ->add('admin_allow_hosts', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_ltext_len']]),
                ],
                'data' => $allowHosts,
            ])
            ->add('admin_deny_hosts', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_ltext_len']]),
                ],
                'data' => $denyHosts,
            ])
            ->add('force_ssl', CheckboxType::class, [
                'label' => 'admin.setting.system.security.force_ssl',
                'required' => false,
                'data' => $this->eccubeConfig->get('eccube_force_ssl'),
            ])
            ->add('trusted_hosts', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                    new Assert\Regex([
                        'pattern' => '/^[\x21-\x7e]+$/',
                    ]),
                ],
                'data' => env('TRUSTED_HOSTS'),
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function ($event) {
                $form = $event->getForm();
                $data = $form->getData();

                // フロント画面のアクセス許可リストのvalidate
                $frontAllowHosts = $data['front_allow_hosts'] ?? '';
                if ($frontAllowHosts !== '') {
                    $ips = $frontAllowHosts ? preg_split("/\R/", $frontAllowHosts, -1, PREG_SPLIT_NO_EMPTY) : [];

                    foreach ($ips as $ip) {
                        // 適切なIPとビットマスクになっているか
                        $errors = $this->validator->validate($ip, new Assert\AtLeastOneOf([
                            'constraints' => [
                                new Assert\Ip(),
                                new Assert\Cidr(),
                            ],
                        ]));
                        if ($errors->count() > 0) {
                            $form['front_allow_hosts']->addError(new FormError(trans('admin.setting.system.security.ip_limit_invalid_ip_and_submask', ['%ip%' => $ip])));
                        }
                    }
                }

                // フロント画面のアクセス拒否リストのvalidate
                $frontDenyHosts = $data['front_deny_hosts'] ?? '';
                if ($frontDenyHosts !== '') {
                    $ips = $frontDenyHosts ? preg_split("/\R/", $frontDenyHosts, -1, PREG_SPLIT_NO_EMPTY) : [];

                    foreach ($ips as $ip) {
                        // 適切なIPとビットマスクになっているか
                        $errors = $this->validator->validate($ip, new Assert\AtLeastOneOf([
                            'constraints' => [
                                new Assert\Ip(),
                                new Assert\Cidr(),
                            ],
                        ]));
                        if ($errors->count() > 0) {
                            $form['front_deny_hosts']->addError(new FormError(trans('admin.setting.system.security.ip_limit_invalid_ip_and_submask', ['%ip%' => $ip])));
                        }
                    }
                }

                // 管理画面のアクセス許可リストのvalidate
                $adminAllowHosts = $data['admin_allow_hosts'] ?? '';
                if ($adminAllowHosts !== '') {
                    $ips = $adminAllowHosts ? preg_split("/\R/", $adminAllowHosts, -1, PREG_SPLIT_NO_EMPTY) : [];

                    foreach ($ips as $ip) {
                        // 適切なIPとビットマスクになっているか
                        $errors = $this->validator->validate($ip, new Assert\AtLeastOneOf([
                            'constraints' => [
                                new Assert\Ip(),
                                new Assert\Cidr(),
                            ],
                        ]));
                        if ($errors->count() != 0) {
                            $form['admin_allow_hosts']->addError(new FormError(trans('admin.setting.system.security.ip_limit_invalid_ipv4', ['%ip%' => $ip])));
                        }
                    }
                }

                // 管理画面のアクセス拒否リストのvalidate
                $adminDenyHosts = $data['admin_deny_hosts'] ?? '';
                if ($adminDenyHosts !== '') {
                    $ips = $adminDenyHosts ? preg_split("/\R/", $adminDenyHosts, -1, PREG_SPLIT_NO_EMPTY) : [];

                    foreach ($ips as $ip) {
                        // 適切なIPとビットマスクになっているか
                        $errors = $this->validator->validate($ip, new Assert\AtLeastOneOf([
                            'constraints' => [
                                new Assert\Ip(),
                                new Assert\Cidr(),
                            ],
                        ]));
                        if ($errors->count() != 0) {
                            $form['admin_deny_hosts']->addError(new FormError(trans('admin.setting.system.security.ip_limit_invalid_ipv4', ['%ip%' => $ip])));
                        }
                    }
                }

                $request = $this->requestStack->getCurrentRequest();
                if ($data['force_ssl'] && !$request->isSecure()) {
                    $form['force_ssl']->addError(new FormError(trans('admin.setting.system.security.ip_limit_invalid_https')));
                }
            })
        ;
    }

    /**
     * フロントURL一覧を取得
     *
     * @return string
     */
    private function getRouteCollection(): string
    {
        $frontRoutesUrlList = [];
        $routes = $this->router->getRouteCollection();
        foreach ($routes as $routeName => $route) {
            $path = $route->getPath();
            // 管理画面以外
            if (false === stripos($routeName, 'admin')
                && false === stripos($path, '/_')
                && false === stripos($path, 'admin')
            ) {
                $arr = explode('/', $path);
                foreach ($arr as $target) {
                    if (!empty($target)) {
                        $target = preg_quote($target);
                        $frontRoutesUrlList[$target] = $target;
                    }
                }
            }
        }

        return implode('|', $frontRoutesUrlList);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'admin_security';
    }
}
