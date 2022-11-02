<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Async\Topic;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to initiate web catalog content node tree cache calculation.
 */
class WebCatalogCalculateContentNodeTreeCacheTopic extends AbstractTopic
{
    public const JOB_ID = 'jobId';
    public const CONTENT_NODE = 'contentNode';
    public const SCOPE = 'scope';

    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public static function getName(): string
    {
        return 'oro.web_catalog.calculate_cache.content_node_tree';
    }

    public static function getDescription(): string
    {
        return 'Initiate web catalog content node tree cache calculation.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $entityOptionNormalizer = function (string $className, string $option) {
            return function (Options $options, int $id) use ($className, $option) {
                $entity = $this->managerRegistry
                    ->getManagerForClass($className)
                    ?->find($className, $id);

                if (!$entity) {
                    throw new InvalidOptionsException(
                        sprintf(
                            'The option "%s" could not be normalized: the entity %s #%s is not found',
                            $option,
                            $className,
                            $id
                        )
                    );
                }

                return $entity;
            };
        };

        $resolver
            ->define(self::JOB_ID)
            ->required()
            ->allowedTypes('int');

        $resolver
            ->define(self::SCOPE)
            ->required()
            ->allowedTypes('int')
            ->normalize($entityOptionNormalizer(Scope::class, self::SCOPE));

        $resolver
            ->define(self::CONTENT_NODE)
            ->required()
            ->allowedTypes('int')
            ->normalize($entityOptionNormalizer(ContentNode::class, self::CONTENT_NODE));
    }
}
