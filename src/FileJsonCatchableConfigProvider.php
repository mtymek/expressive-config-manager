<?php

namespace Zend\Expressive\ConfigManager;

final class FileJsonCatchableConfigProvider implements ConfigProviderInterface
{
    private $config = [];

    public function __construct(callable $providerProxy, $filename = 'data/cache/app_config.json')
    {
        if (is_file($filename)) {
            // Try to load the cached config
            $this->config = json_decode(file_get_contents($filename), true);
            return;
        }

        $this->config = $providerProxy();

        // Cache config if enabled
        if (isset($this->config['config_cache_enabled']) && $this->config['config_cache_enabled'] === true) {
            file_put_contents($filename, json_encode($this->config));
        }
    }

    public function getConfig()
    {
        return $this->config;
    }
}
