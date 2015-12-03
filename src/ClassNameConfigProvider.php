<?php

namespace Zend\Expressive\ConfigManager;

use RuntimeException;
use Zend\Stdlib\ArrayUtils;

final class ClassNameConfigProvider implements ConfigProviderInterface
{
    private $config = [];

    public function __construct(array $providers)
    {
        foreach ($providers as $providerClass) {
            if (!class_exists($providerClass)) {
                throw new RuntimeException("Cannot read config from $providerClass - class cannot be loaded.");
            }
            $provider = new $providerClass();
            if (!$provider instanceof ConfigProviderInterface) {
                throw new RuntimeException(
                    "Cannot read config from $providerClass - class does not implement ConfigProviderInterface"
                );
            }
            $this->config = ArrayUtils::merge($this->config, $provider->getConfig());
        }
    }

    public function getConfig()
    {
        return $this->config;
    }
}
