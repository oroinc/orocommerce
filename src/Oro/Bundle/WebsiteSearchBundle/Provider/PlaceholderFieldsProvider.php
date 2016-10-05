<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderVisitor;

class PlaceholderFieldsProvider
{
    /**
     * @var PlaceholderVisitor
     */
    private $placeholderVisitor;

    /**
     * @var AbstractSearchMappingProvider
     */
    private $mappingProvider;

    /**
     * @param PlaceholderVisitor $placeholderVisitor
     * @param AbstractSearchMappingProvider $mappingProvider
     */
    public function __construct(
        PlaceholderVisitor $placeholderVisitor,
        AbstractSearchMappingProvider $mappingProvider
    ) {
        $this->placeholderVisitor = $placeholderVisitor;
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * @param string $entityClass
     * @param string $fieldName
     * @param array $placeholders
     * @return string
     * @throws \RuntimeException
     */
    public function getPlaceholderFieldName($entityClass, $fieldName, array $placeholders)
    {
        $fields = $this->mappingProvider->getEntityMapParameter($entityClass, 'fields', []);

        if (!isset($fields[$fieldName]['name'])) {
            throw new \RuntimeException(sprintf('Cannot find %s field for %s class', $fieldName, $entityClass));
        }

        return $this->placeholderVisitor->replace($fields[$fieldName]['name'], $placeholders);
    }
}
