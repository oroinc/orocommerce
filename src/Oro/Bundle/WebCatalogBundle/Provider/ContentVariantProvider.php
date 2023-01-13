<?php

namespace Oro\Bundle\WebCatalogBundle\Provider;

use Doctrine\ORM\QueryBuilder;
use Oro\Component\WebCatalog\ContentVariantProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

/**
 * Delegates the getting information about a content variant to child providers.
 */
class ContentVariantProvider implements ContentVariantProviderInterface
{
    /** @var iterable|ContentVariantProviderInterface[] */
    private $providers;

    /**
     * @param iterable|ContentVariantProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritdoc}
     */
    public function isSupportedClass($className)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isSupportedClass($className)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyNodeQueryBuilderByEntities(QueryBuilder $queryBuilder, $entityClass, array $entities)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isSupportedClass($entityClass)) {
                $provider->modifyNodeQueryBuilderByEntities($queryBuilder, $entityClass, $entities);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getValues(ContentNodeInterface $node)
    {
        $values = [];
        foreach ($this->providers as $provider) {
            $values[] = $provider->getValues($node);
        }
        if ($values) {
            $values = array_merge(...$values);
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalizedValues(ContentNodeInterface $node)
    {
        $values = [];
        foreach ($this->providers as $provider) {
            $values[] = $provider->getLocalizedValues($node);
        }
        if ($values) {
            $values = array_merge(...$values);
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecordId(array $item)
    {
        foreach ($this->providers as $provider) {
            $recordId = $provider->getRecordId($item);
            if ($recordId) {
                return $recordId;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecordSortOrder(array $item)
    {
        foreach ($this->providers as $provider) {
            $recordSortOrder = $provider->getRecordSortOrder($item);
            if (!is_null($recordSortOrder)) {
                return $recordSortOrder;
            }
        }

        return null;
    }
}
