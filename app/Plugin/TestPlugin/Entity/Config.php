<?php

namespace Plugin\TestPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Config
 *
 * @ORM\Table(name="plg_test_plugin_config")
 * @ORM\Entity(repositoryClass="Plugin\TestPlugin\Repository\ConfigRepository")
 */
class Config
{
    const CONFIG_KEY_IS_ENABLED = 'is_enabled';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="config_key", type="string", length=255, nullable=false)
     */
    private $configKey;

    /**
     * @var string
     *
     * @ORM\Column(name="config_value", type="text", nullable=false)
     */
    private $configValue;

    public function getId(): int
    {
        return $this->id;
    }

    public function getConfigKey(): string
    {
        return $this->configKey;
    }

    public function setConfigKey(string $configKey): self
    {
        $this->configKey = $configKey;
        return $this;
    }

    public function getConfigValue(): string
    {
        return $this->configValue;
    }

    public function setConfigValue(string $configValue): self
    {
        $this->configValue = $configValue;
        return $this;
    }
}
