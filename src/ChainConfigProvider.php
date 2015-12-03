<?php

namespace Zend\Expressive\ConfigManager;

use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\Exception\RuntimeException;

final class ChainConfigProvider implements ConfigProviderInterface
{
    private $config = [];

    public function __construct(array $configManagers)
    {
        foreach ($configManagers as $configManager) {
            if (!$configManager instanceof ConfigProviderInterface) {
                throw new RuntimeException(
                    "Cannot read config from ".  get_class($configManager)." - class does not implement ConfigProviderInterface"
                );
            }
            $this->config = ArrayUtils::merge($this->config, $configManager->getConfig());
        }
    }

    public function getConfig()
    {
        return $this->config;
    }

}
