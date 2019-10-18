<?php

namespace Oro\Bundle\CMSBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This class merge configuration from your config files
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @var string
     */
    public const DIRECT_EDITING = 'direct_editing';

    /**
     * @var string
     */
    public const LOGIN_PAGE_CSS_FIELD_OPTION = 'login_page_css_field';

    /**
     * @var string
     */
    public const DIRECT_URL_PREFIX = 'landing_page_direct_url_prefix';

    /** @var array */
    private $contentRestrictionModes = [];

    /**
     * @param array $contentRestrictionModes
     */
    public function __construct(array $contentRestrictionModes)
    {
        $this->contentRestrictionModes = $contentRestrictionModes;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(OroCMSExtension::ALIAS);
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
                            ->performNoDeepMerging()
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
                self::DIRECT_URL_PREFIX => ['value' => '']
            ]
        );

        return $treeBuilder;
    }
}
