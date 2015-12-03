<?php

namespace Zend\Expressive\ConfigManager;

use Zend\Stdlib\ArrayUtils;

final class ConfigFileProvider implements ConfigProviderInterface
{
    private $config = [];

    public function __construct(array $configFiles)
    {
        // Load configuration from autoload path
        foreach ($configFiles as $file) {
            $this->config = ArrayUtils::merge($this->config, include $file);
        }
    }

    public function getConfig()
    {
        return $this->config;
    }
}
