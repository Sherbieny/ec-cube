<?php

namespace Plugin\TestPlugin\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\TestPlugin\Entity\Config;
use Plugin\TestPlugin\Form\Type\Admin\ConfigType;
use Plugin\TestPlugin\Service\ConfigService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ConfigController extends AbstractController
{
    /**
    * @Route("/%eccube_admin_route%/test_plugin/config", name="test_plugin_admin_config")
    * @Template("@TestPlugin/admin/config.twig")
    */
    public function index(Request $request, ConfigService $configService)
    {
        $form = $this->createForm(ConfigType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Save configuration values using ConfigService
            $configService->setConfigValue(Config::CONFIG_KEY_IS_ENABLED, $form->get(Config::CONFIG_KEY_IS_ENABLED)->getData());

            $this->entityManager->flush();
            $this->addSuccess('登録しました。', 'admin');

            return $this->redirectToRoute('test_plugin_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
