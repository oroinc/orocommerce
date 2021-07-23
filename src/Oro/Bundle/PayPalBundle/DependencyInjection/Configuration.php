<?php

namespace Oro\Bundle\PayPalBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration class for OroPayPalBundle.
 */
class Configuration implements ConfigurationInterface
{
    const CONFIG_SECTION = 'oro_paypal';
    const CONFIG_KEY_ALLOWED_IPS = 'allowed_ips';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::CONFIG_SECTION);
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode(self::CONFIG_KEY_ALLOWED_IPS)
                    ->defaultValue([])
                    ->prototype('scalar')
                ->end()
                ->validate()
                    ->always(function ($ips) {
                        $filteredIps = array_filter($ips, function ($ip) {
                            return !$this->isValidIp($ip);
                        });
                        if ($filteredIps) {
                            $message = 'The following IP addresses are invalid: %s';
                            throw new \InvalidArgumentException(sprintf($message, implode(', ', $filteredIps)));
                        }

                        return $ips;
                    })
                ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }

    private function isValidIp(string $address): bool
    {
        $parts = explode('/', $address);
        if (1 === count($parts)) {
            return false !== filter_var($parts[0], FILTER_VALIDATE_IP);
        }

        $ip = $parts[0];
        $netmask = $parts[1];

        if (!ctype_digit($netmask)) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $netmask <= 32;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $netmask <= 128;
        }

        return false;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public static function getConfigKey($key): string
    {
        return sprintf('%s%s%s', static::CONFIG_SECTION, ConfigManager::SECTION_MODEL_SEPARATOR, $key);
    }
}
