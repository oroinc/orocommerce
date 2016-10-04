<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\VisitorReplacePlaceholder;

class PlaceholderFieldsProvider
{
    /**
     * @var VisitorReplacePlaceholder
     */
    private $visitorReplacePlaceholder;

    /**
     * @var AbstractSearchMappingProvider
     */
    private $mappingProvider;

    /**
     * @param VisitorReplacePlaceholder $visitorReplacePlaceholder
     * @param AbstractSearchMappingProvider $mappingProvider
     */
    public function __construct(
        VisitorReplacePlaceholder $visitorReplacePlaceholder,
        AbstractSearchMappingProvider $mappingProvider
    ) {
        $this->visitorReplacePlaceholder = $visitorReplacePlaceholder;
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

        return $this->visitorReplacePlaceholder->replace($fields[$fieldName]['name'], $placeholders);
    }
}
