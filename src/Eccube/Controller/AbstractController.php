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

namespace Eccube\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\Constant;
use Eccube\Common\EccubeConfig;
use Eccube\Session\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

class AbstractController extends Controller
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @param EccubeConfig $eccubeConfig
     */
    #[Required]
    public function setEccubeConfig(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * @param EntityManagerInterface $entityManager
     */
    #[Required]
    public function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param TranslatorInterface $translator
     */
    #[Required]
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param Session $session
     */
    #[Required]
    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param FormFactoryInterface $formFactory
     */
    #[Required]
    public function setFormFactory(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    #[Required]
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param RouterInterface $router
     *
     * @return void
     */
    #[Required]
    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function addSuccess($message, $namespace = 'front')
    {
        $this->addFlash('eccube.'.$namespace.'.success', $message);
    }

    public function addSuccessOnce($message, $namespace = 'front')
    {
        $this->addFlashOnce('eccube.'.$namespace.'.success', $message);
    }

    public function addError($message, $namespace = 'front')
    {
        $this->addFlash('eccube.'.$namespace.'.error', $message);
    }

    public function addErrorOnce($message, $namespace = 'front')
    {
        $this->addFlashOnce('eccube.'.$namespace.'.error', $message);
    }

    public function addDanger($message, $namespace = 'front')
    {
        $this->addFlash('eccube.'.$namespace.'.danger', $message);
    }

    public function addDangerOnce($message, $namespace = 'front')
    {
        $this->addFlashOnce('eccube.'.$namespace.'.danger', $message);
    }

    public function addWarning($message, $namespace = 'front')
    {
        $this->addFlash('eccube.'.$namespace.'.warning', $message);
    }

    public function addWarningOnce($message, $namespace = 'front')
    {
        $this->addFlashOnce('eccube.'.$namespace.'.warning', $message);
    }

    public function addInfo($message, $namespace = 'front')
    {
        $this->addFlash('eccube.'.$namespace.'.info', $message);
    }

    public function addInfoOnce($message, $namespace = 'front')
    {
        $this->addFlashOnce('eccube.'.$namespace.'.info', $message);
    }

    public function addRequestError($message, $namespace = 'front')
    {
        $this->addFlash('eccube.'.$namespace.'.request.error', $message);
    }

    public function addRequestErrorOnce($message, $namespace = 'front')
    {
        $this->addFlashOnce('eccube.'.$namespace.'.request.error', $message);
    }

    public function clearMessage()
    {
        $this->session->getFlashBag()->clear();
    }

    public function deleteMessage()
    {
        $this->clearMessage();
        $this->addWarning('admin.common.delete_error_already_deleted', 'admin');
    }

    public function hasMessage(string $type): bool
    {
        return $this->session->getFlashBag()->has($type);
    }

    public function addFlashOnce(string $type, $message): void
    {
        if (!$this->hasMessage($type)) {
            $this->addFlash($type, $message);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addFlash(string $type, $message): void
    {
        try {
            parent::addFlash($type, $message);
        } catch (\LogicException $e) {
            // fallback session
            $this->session->getFlashBag()->add($type, $message);
        }
    }

    /**
     * @param string $targetPath
     */
    public function setLoginTargetPath($targetPath, $namespace = null)
    {
        if (is_null($namespace)) {
            $this->session->getFlashBag()->set('eccube.login.target.path', $targetPath);
        } else {
            $this->session->getFlashBag()->set('eccube.'.$namespace.'.login.target.path', $targetPath);
        }
    }

    /**
     * Forwards the request to another controller.
     *
     * @param string $route The name of the route
     * @param array  $path An array of path parameters
     * @param array  $query An array of query parameters
     *
     * @return \Symfony\Component\HttpFoundation\Response A Response instance
     */
    public function forwardToRoute($route, array $path = [], array $query = [])
    {
        $Route = $this->router->getRouteCollection()->get($route);
        if (!$Route) {
            throw new RouteNotFoundException(sprintf('The named route "%s" as such route does not exist.', $route));
        }

        return $this->forward($Route->getDefault('_controller'), $path, $query);
    }

    /**
     * Checks the validity of a CSRF token.
     *
     * if token is invalid, throws AccessDeniedHttpException.
     *
     * @return bool
     *
     * @throws AccessDeniedHttpException
     */
    protected function isTokenValid()
    {
        /** @var Request $request */
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $token = $request->get(Constant::TOKEN_NAME)
            ? $request->get(Constant::TOKEN_NAME)
            : $request->headers->get('ECCUBE-CSRF-TOKEN');

        if (!$this->isCsrfTokenValid(Constant::TOKEN_NAME, $token)) {
            throw new AccessDeniedHttpException('CSRF token is invalid.');
        }

        return true;
    }
}
