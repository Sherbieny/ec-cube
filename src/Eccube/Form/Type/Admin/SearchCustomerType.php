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
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Form\Type\Master\CustomerStatusType;
use Eccube\Form\Type\Master\PrefType;
use Eccube\Form\Type\Master\SexType;
use Eccube\Form\Type\PhoneNumberType;
use Eccube\Form\Type\PriceType;
use Eccube\Repository\Master\CustomerStatusRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;

class SearchCustomerType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var CustomerStatusRepository
     */
    protected $customerStatusRepository;

    /**
     * SearchCustomerType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     * @param CustomerStatusRepository $customerStatusRepository
     */
    public function __construct(
        CustomerStatusRepository $customerStatusRepository,
        EccubeConfig $eccubeConfig,
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->customerStatusRepository = $customerStatusRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $months = range(1, 12);
        $builder
            // 会員ID・メールアドレス・名前・名前(フリガナ)
            ->add('multi', TextType::class, [
                'label' => 'admin.customer.multi_search_label',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                ],
            ])
            ->add('customer_status', CustomerStatusType::class, [
                'label' => 'admin.customer.customer_status',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'placeholder' => false,
                'data' => $this->customerStatusRepository->findBy([
                    'id' => [
                        CustomerStatus::PROVISIONAL,
                        CustomerStatus::REGULAR,
                    ],
                ]),
            ])
            ->add('sex', SexType::class, [
                'label' => 'admin.common.gender',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('birth_month', ChoiceType::class, [
                'label' => 'admin.customer.birth_month',
                'placeholder' => 'admin.common.select',
                'required' => false,
                'choices' => array_combine($months, $months),
            ])
            ->add('birth_start', BirthdayType::class, [
                'label' => 'admin.common.birth_day__start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'constraints' => [
                    new Assert\Range([
                        'min' => '0003-01-01',
                        'minMessage' => 'form_error.out_of_range',
                    ]),
                ],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_birth_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('birth_end', BirthdayType::class, [
                'label' => 'admin.common.birth_day__end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'constraints' => [
                    new Assert\Range([
                        'min' => '0003-01-01',
                        'minMessage' => 'form_error.out_of_range',
                    ]),
                ],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_birth_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('pref', PrefType::class, [
                'label' => 'admin.common.pref',
                'required' => false,
            ])
            ->add('phone_number', PhoneNumberType::class, [
                'label' => 'admin.common.phone_number',
                'required' => false,
            ])
            ->add('buy_product_name', TextType::class, [
                'label' => 'admin.order.purchase_product',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                ],
            ])
            ->add('buy_total_start', PriceType::class, [
                'label' => 'admin.order.purchase_price__start',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_price_len']]),
                ],
            ])
            ->add('buy_total_end', PriceType::class, [
                'label' => 'admin.order.purchase_price__end',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_price_len']]),
                ],
            ])
            ->add('buy_times_start', IntegerType::class, [
                'label' => 'admin.order.purchase_count__start',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_int_len']]),
                ],
            ])
            ->add('buy_times_end', IntegerType::class, [
                'label' => 'admin.order.purchase_count__end',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_int_len']]),
                ],
            ])
            ->add('create_date_start', DateType::class, [
                'label' => 'admin.common.create_date__start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'constraints' => [
                    new Assert\Range([
                        'min' => '0003-01-01',
                        'minMessage' => 'form_error.out_of_range',
                    ]),
                ],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_create_date_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('create_datetime_start', DateTimeType::class, [
                'label' => 'admin.common.create_date__start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\Range([
                        'min' => '0003-01-01',
                        'minMessage' => 'form_error.out_of_range',
                    ]),
                ],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_create_datetime_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('create_date_end', DateType::class, [
                'label' => 'admin.common.create_date__end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'constraints' => [
                    new Assert\Range([
                        'min' => '0003-01-01',
                        'minMessage' => 'form_error.out_of_range',
                    ]),
                ],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_create_date_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('create_datetime_end', DateTimeType::class, [
                'label' => 'admin.common.create_date__end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\Range([
                        'min' => '0003-01-01',
                        'minMessage' => 'form_error.out_of_range',
                    ]),
                ],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_create_datetime_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('update_date_start', DateType::class, [
                'label' => 'admin.common.update_date__start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'constraints' => [
                    new Assert\Range([
                        'min' => '0003-01-01',
                        'minMessage' => 'form_error.out_of_range',
                    ]),
                ],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_update_date_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('update_datetime_start', DateTimeType::class, [
                'label' => 'admin.common.update_date__start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\Range([
                        'min' => '0003-01-01',
                        'minMessage' => 'form_error.out_of_range',
                    ]),
                ],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_update_datetime_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('update_date_end', DateType::class, [
                'label' => 'admin.common.update_date__end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'constraints' => [
                    new Assert\Range([
                        'min' => '0003-01-01',
                        'minMessage' => 'form_error.out_of_range',
                    ]),
                ],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_update_date_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('update_datetime_end', DateTimeType::class, [
                'label' => 'admin.common.update_date__end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\Range([
                        'min' => '0003-01-01',
                        'minMessage' => 'form_error.out_of_range',
                    ]),
                ],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_update_datetime_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('last_buy_start', DateType::class, [
                'label' => 'admin.order.last_buy_date__start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'constraints' => [
                    new Assert\Range([
                        'min' => '0003-01-01',
                        'minMessage' => 'form_error.out_of_range',
                    ]),
                ],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_last_buy_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('last_buy_datetime_start', DateTimeType::class, [
                'label' => 'admin.order.last_buy_date__start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\Range([
                        'min' => '0003-01-01',
                        'minMessage' => 'form_error.out_of_range',
                    ]),
                ],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_last_buy_datetime_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('last_buy_end', DateType::class, [
                'label' => 'admin.order.last_buy_date__end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'constraints' => [
                    new Assert\Range([
                        'min' => '0003-01-01',
                        'minMessage' => 'form_error.out_of_range',
                    ]),
                ],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_last_buy_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('last_buy_datetime_end', DateTimeType::class, [
                'label' => 'admin.order.last_buy_date__end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\Range([
                        'min' => '0003-01-01',
                        'minMessage' => 'form_error.out_of_range',
                    ]),
                ],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_last_buy_datetime_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            // ソート用
            ->add('sortkey', HiddenType::class, [
                'label' => 'admin.list.sort.key',
                'required' => false,
            ])
            ->add('sorttype', HiddenType::class, [
                'label' => 'admin.list.sort.type',
                'required' => false,
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();

                // 登録日
                $create_datetime_start = $form['create_datetime_start']->getData();
                $create_datetime_end = $form['create_datetime_end']->getData();

                if (!empty($create_datetime_start) && !empty($create_datetime_end)) {
                    if ($create_datetime_start > $create_datetime_end) {
                        $form['create_datetime_end']->addError(new FormError(trans('admin.product.date_range_error')));
                    }
                }

                // 更新日
                $update_datetime_start = $form['update_datetime_start']->getData();
                $update_datetime_end = $form['update_datetime_end']->getData();

                if (!empty($update_datetime_start) && !empty($update_datetime_end)) {
                    if ($update_datetime_start > $update_datetime_end) {
                        $form['update_datetime_end']->addError(new FormError(trans('admin.product.date_range_error')));
                    }
                }

                // 最終購入日
                $last_buy_start = $form['last_buy_start']->getData();
                $last_buy_end = $form['last_buy_end']->getData();

                if (!empty($last_buy_start) && !empty($last_buy_end)) {
                    if ($last_buy_start > $last_buy_end) {
                        $form['last_buy_end']->addError(new FormError(trans('admin.product.date_range_error')));
                    }
                }
            })
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'admin_search_customer';
    }
}
