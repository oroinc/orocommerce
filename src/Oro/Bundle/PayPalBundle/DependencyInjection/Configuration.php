<?php

namespace Oro\Bundle\PayPalBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE = 'oro_paypal';
    public const CONFIG_KEY_ALLOWED_IPS = 'allowed_ips';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode(self::CONFIG_KEY_ALLOWED_IPS)
                    ->defaultValue([])
                    ->prototype('scalar')
                ->end()
                ->validate()
                    ->always(function ($ips) {
                        $filteredIps = array_filter($ips, fn ($ip) => !$this->isValidIp($ip));
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
        return self::ROOT_NODE . ConfigManager::SECTION_MODEL_SEPARATOR . $key;
    }
}
