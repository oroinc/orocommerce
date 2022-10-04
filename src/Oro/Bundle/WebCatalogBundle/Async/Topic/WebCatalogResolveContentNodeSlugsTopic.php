<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to generate slugs for the specified web catalog content node.
 */
class WebCatalogResolveContentNodeSlugsTopic extends AbstractTopic
{
    public const ID = 'id';
    public const CREATE_REDIRECT = 'createRedirect';

    public static function getName(): string
    {
        return 'oro_web_catalog.resolve_node_slugs';
    }

    public static function getDescription(): string
    {
        return 'Generate slugs for the specified web catalog content node.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->define(self::ID)
            ->required()
            ->allowedTypes('int');

        $resolver
            ->define(self::CREATE_REDIRECT)
            ->required()
            ->allowedTypes('bool');
    }
}
