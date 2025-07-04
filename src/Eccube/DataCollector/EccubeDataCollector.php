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

namespace Eccube\DataCollector;

use Eccube\Common\Constant;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Plugin;
use Eccube\Repository\PluginRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * EccubeDataCollector.
 *
 * @see https://github.com/Sylius/SyliusCoreBundle/blob/master/Collector/SyliusCollector.php
 */
class EccubeDataCollector extends DataCollector
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var PluginRepository
     */
    protected $pluginRepository;

    /**
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig, PluginRepository $pluginRepository)
    {
        $this->data = [
            'version' => Constant::VERSION,
            'base_currency_code' => null,
            'currency_code' => null,
            'default_locale_code' => null,
            'locale_code' => null,
            'plugins' => [],
        ];
        $this->eccubeConfig = $eccubeConfig;
        $this->pluginRepository = $pluginRepository;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->data['version'];
    }

    /**
     * @return array
     */
    public function getPlugins()
    {
        return $this->data['plugins'];
    }

    /**
     * @return string
     */
    public function getCurrencyCode()
    {
        return $this->data['currency_code'];
    }

    /**
     * @return string
     */
    public function getLocaleCode()
    {
        return $this->data['locale_code'];
    }

    /**
     * @return string
     */
    public function getDefaultCurrencyCode()
    {
        return $this->data['base_currency_code'];
    }

    /**
     * @return string
     */
    public function getDefaultLocaleCode()
    {
        return $this->data['default_locale_code'];
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, ?\Throwable $exception = null)
    {
        $this->data['base_currency_code'] = $this->eccubeConfig->get('currency');
        $this->data['currency_code'] = $this->eccubeConfig->get('currency');

        try {
            $this->data['locale_code'] = $this->eccubeConfig->get('locale');
        } catch (\Exception $exception) {
        }

        try {
            $enabled = $this->eccubeConfig->get('eccube.plugins.enabled');
            $disabled = $this->eccubeConfig->get('eccube.plugins.disabled');

            $Plugins = $this->pluginRepository->findAll();
            foreach (array_merge($enabled, $disabled) as $code) {
                $Plugin = null;

                /* @var Plugin $Plugin */
                foreach ($Plugins as $p) {
                    if ($code == $p->getCode()) {
                        $Plugin = $p;
                        break;
                    }
                }

                if (!$Plugin) {
                    $Plugin = new Plugin();
                    $Plugin->setCode($code);
                    $Plugin->setName($code);
                    $Plugin->setEnabled(false);
                }
                $this->data['plugins'][$code] = $Plugin->toArray();
            }
        } catch (\Exception $exception) {
        }
    }

    public function reset()
    {
        $this->data = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'eccube_core';
    }
}
