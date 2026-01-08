<?php

namespace Oro\Bundle\CMSBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    // Component added back for theme layout BC from version 5.0
    public const ROOT_NAME = 'oro_cms';
    public const DIRECT_EDITING = 'direct_editing';
    public const LOGIN_PAGE_CSS_FIELD_OPTION = 'login_page_css_field';
    public const DIRECT_URL_PREFIX = 'landing_page_direct_url_prefix';
    public const HOME_PAGE = 'home_page';
    // Component added back for theme layout BC from version 5.0
    public const IS_UPDATED_AFTER_507 = 'is_updated_after_507';
    public const string GUEST_ACCESS_ALLOWED_CMS_PAGES = 'guest_access_allowed_cms_pages';

    private array $contentRestrictionModes;

    public function __construct(array $contentRestrictionModes)
    {
        $this->contentRestrictionModes = $contentRestrictionModes;
    }

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('oro_cms');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode(self::DIRECT_EDITING)
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode(self::LOGIN_PAGE_CSS_FIELD_OPTION)->defaultFalse()->end()
                    ->end()
                ->end()
                ->arrayNode('content_restrictions')
                    ->info('Describes the rules how WYSIWYG fields should works with HTMLPurifier')
                    ->children()
                        ->enumNode('mode')
                            ->values($this->contentRestrictionModes)
                            ->defaultValue('secure')
                            ->info(
                                "Configuration setting that defines the overall level of content restrictions:\n" .
                                "\"secure\" - on the secure level there is no way to insert any potentially" .
                                " unsecure content via UI by any users\n" .
                                "\"selective\" - on the less secure level potentially unsecure content" .
                                " can be inserted via UI by some roles into specific fields of specific entities\n" .
                                "\"unsecure\" - on this level any content can be inserted via UI by any user" .
                                ' with edit permission on that WYSIWYG field'
                            )
                        ->end()
                        ->arrayNode('lax_restrictions')
                            ->info(
                                'List of user roles that has edit permission on entity field with selected secure level'
                            )
                            ->useAttributeAsKey('ROLE')
                            ->arrayPrototype()
                                ->useAttributeAsKey('\Entity')
                                ->info('List of roles that has edit permission with selected secure level')
                                    ->arrayPrototype()
                                        ->info('List of entity fields to which selected secure level current apply')
                                        ->scalarPrototype()->end()
                                    ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        SettingsBuilder::append(
            $rootNode,
            [
                self::DIRECT_URL_PREFIX => ['value' => '', 'type' => 'string'],
                self::HOME_PAGE => ['value' => null, 'type' => 'integer'],
                self::GUEST_ACCESS_ALLOWED_CMS_PAGES => ['value' => [], 'type' => 'array'],
            ]
        );

        return $treeBuilder;
    }

    /**
     * Component added back for theme layout BC from version 5.0
     */
    public static function getConfigKeyByName(string $name): string
    {
        return TreeUtils::getConfigKey(self::ROOT_NAME, $name);
    }
}
