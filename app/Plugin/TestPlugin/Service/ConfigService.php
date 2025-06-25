<?php

namespace Plugin\TestPlugin\Service;

use Plugin\TestPlugin\Entity\Config;
use Plugin\TestPlugin\Repository\ConfigRepository;

/**
 * Class ConfigService
 * @package Plugin\TestPlugin\Service
 *
 * Handles all configuration related operations
 */
class ConfigService
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * Get a configuration value by key
     *
     * @param string $key
     * @return string|null
     */
    public function getConfigValue(string $key): ?string
    {
        return $this->configRepository->getValueByKey($key);
    }

    /**
     * Set a configuration value by key
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setConfigValue(string $key, string $value): void
    {
        $this->configRepository->set($key, $value);
    }

    /**
     * Get all configuration values
     *
     * @return array
     */
    public function getAllConfigValues(): array
    {
        return $this->configRepository->findAll();
    }

    /**
     * Delete a configuration value by key
     *
     * @param string $key
     * @return void
     */
    public function deleteConfigValue(string $key): void
    {
        $config = $this->configRepository->findOneBy(['configKey' => $key]);
        if ($config) {
            $this->configRepository->delete($config);
        }
    }

    /**
     * Get the Plugin enabled status
     *
     * @return bool
     */
    public function isPluginEnabled(): bool
    {
        return (bool) $this->getConfigValue(Config::CONFIG_KEY_IS_ENABLED);
    }

    /**
     * Set the Plugin enabled status
     *
     * @param string $enabled
     * @return void
     */
    public function setPluginEnabled(string $enabled): void
    {
        $this->setConfigValue(Config::CONFIG_KEY_IS_ENABLED, $enabled);
    }
}
