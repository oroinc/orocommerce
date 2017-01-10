<?php

namespace Oro\Bundle\WebCatalogBundle\ContentVariantProvider;

use Doctrine\ORM\QueryBuilder;

use Oro\Component\WebCatalog\ContentVariantProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

class ContentVariantProvider implements ContentVariantProviderInterface
{
    /**
     * @var ContentVariantProviderRegistry
     */
    protected $providerRegistry;

    /**
     * @param ContentVariantProviderRegistry $providerRegistry
     */
    public function __construct(ContentVariantProviderRegistry $providerRegistry)
    {
        $this->providerRegistry = $providerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function isSupportedClass($className)
    {
        foreach ($this->providerRegistry->getProviders() as $provider) {
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
        foreach ($this->providerRegistry->getProviders() as $provider) {
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
        foreach ($this->providerRegistry->getProviders() as $provider) {
            $values = array_merge(
                $values,
                $provider->getValues($node)
            );
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalizedValues(ContentNodeInterface $node)
    {
        $values = [];
        foreach ($this->providerRegistry->getProviders() as $provider) {
            $values = array_merge(
                $values,
                $provider->getLocalizedValues($node)
            );
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecordId(array $item)
    {
        foreach ($this->providerRegistry->getProviders() as $provider) {
            $recordId = $provider->getRecordId($item);
            if ($recordId) {
                return $recordId;
            }
        }

        return null;
    }
}
