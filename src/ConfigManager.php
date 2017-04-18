<?php
namespace Zend\Expressive\ConfigManager;

use Generator;
use Interop\Container\ServiceProvider;
use Zend\ServiceManager\Interop\ProviderAggregate;
use Zend\Stdlib\ArrayUtils\MergeRemoveKey;
use Zend\Stdlib\ArrayUtils\MergeReplaceKeyInterface;

class ConfigManager
{
    const ENABLE_CACHE = 'config_cache_enabled';

    /**
     * @var array
     */
    private $config;

    /**
     * @var ProviderAggregate
     */
    private $serviceProviders;

    private function resolveProvider($provider)
    {
        if (is_string($provider)) {
            if (!class_exists($provider)) {
                throw new InvalidConfigProviderException("Cannot read config from $provider - class cannot be loaded.");
            }
            $provider = new $provider();
        }

        if (!is_callable($provider) && !$provider instanceof ServiceProvider) {
            throw new InvalidConfigProviderException(
                sprintf("Cannot read config from %s - config provider must be callable or implement ServiceProvider.", get_class($provider))
            );
        }

        return $provider;
    }

    /**
     * Copied from https://github.com/zendframework/zend-stdlib/blob/master/src/ArrayUtils.php#L269
     */
    private function mergeArray(array $a, array $b)
    {
        foreach ($b as $key => $value) {
            if ($value instanceof MergeReplaceKeyInterface) {
                $a[$key] = $value->getData();
            } elseif (isset($a[$key]) || array_key_exists($key, $a)) {
                if ($value instanceof MergeRemoveKey) {
                    unset($a[$key]);
                } elseif (is_int($key)) {
                    $a[] = $value;
                } elseif (is_array($value) && is_array($a[$key])) {
                    $a[$key] = $this->mergeArray($a[$key], $value);
                } else {
                    $a[$key] = $value;
                }
            } else {
                if (!$value instanceof MergeRemoveKey) {
                    $a[$key] = $value;
                }
            }
        }
        return $a;
    }

    private function mergeConfig(&$mergedConfig, $provider, $config)
    {
        if (!is_array($config)) {
            throw new InvalidConfigProviderException(
                sprintf("Cannot read config from %s - it does not return array.", get_class($provider))
            );
        }

        $mergedConfig = $this->mergeArray($mergedConfig, $config);
    }

    private function loadConfigFromProviders(array $providers)
    {
        $mergedConfig = [];
        foreach ($providers as $provider) {
            $provider = $this->resolveProvider($provider);

            if ($provider instanceof ServiceProvider) {
                if (null === $this->serviceProviders) {
                    $this->serviceProviders = new ProviderAggregate();
                }
                $this->serviceProviders->enqueue($provider);
                continue;
            }

            $config = $provider();
            if ($config instanceof Generator) {
                foreach ($config as $cfg) {
                    $this->mergeConfig($mergedConfig, $provider, $cfg);
                }
            } else {
                $this->mergeConfig($mergedConfig, $provider, $config);
            }
        }
        return $mergedConfig;
    }

    public function __construct(
        array $providers = [],
        $cachedConfigFile = null
    ) {
        if (null !== $cachedConfigFile && is_file($cachedConfigFile)) {
            // Try to load the cached config
            $cached = require $cachedConfigFile;
            $this->config = $cached['config'];
            $this->serviceProviders = unserialize($cached['service_providers']);
            return;
        }

        $config = $this->loadConfigFromProviders($providers);

        // Cache config if enabled
        if (null !== $cachedConfigFile
            && isset($config[static::ENABLE_CACHE])
            && $config[static::ENABLE_CACHE] === true
        ) {
            $configToCache = [
                'config' => $config,
                'service_providers' => serialize($this->serviceProviders),
            ];
            file_put_contents($cachedConfigFile, '<?php return ' . var_export($configToCache, true) . ";\n");
        }

        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getMergedConfig()
    {
        return $this->config;
    }

    /**
     * @return ProviderAggregate
     */
    public function getServiceProviders()
    {
        return $this->serviceProviders;
    }
}
