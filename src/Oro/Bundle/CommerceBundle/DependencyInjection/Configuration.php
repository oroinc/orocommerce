<?php

namespace Oro\Bundle\CommerceBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const string ROOT_NODE = 'oro_commerce';

    public const string COMPANY_NAME = 'company_name';
    public const string BUSINESS_ADDRESS = 'business_address';
    public const string PHONE_NUMBER = 'phone_number';
    public const string CONTACT_EMAIL = 'contact_email';
    public const string WEBSITE = 'website';
    public const string TAX_ID = 'tax_id';

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append($rootNode, [
            self::COMPANY_NAME => ['value' => null, 'type' => 'string'],
            self::BUSINESS_ADDRESS => ['value' => null, 'type' => 'string'],
            self::PHONE_NUMBER => ['value' => null, 'type' => 'string'],
            self::CONTACT_EMAIL => ['value' => null, 'type' => 'string'],
            self::WEBSITE => ['value' => null, 'type' => 'string'],
            self::TAX_ID => ['value' => null, 'type' => 'string'],
        ]);

        return $treeBuilder;
    }

    public static function getConfigKeyByName(string $name): string
    {
        return TreeUtils::getConfigKey(self::ROOT_NODE, $name);
    }
}
