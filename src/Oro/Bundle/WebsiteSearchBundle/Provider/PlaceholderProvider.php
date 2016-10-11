<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;

class PlaceholderProvider
{
    /**
     * @var PlaceholderInterface
     */
    private $placeholder;

    /**
     * @var AbstractSearchMappingProvider
     */
    private $mappingProvider;

    /**
     * @param PlaceholderInterface $placeholder
     * @param AbstractSearchMappingProvider $mappingProvider
     */
    public function __construct(
        PlaceholderInterface $placeholder,
        AbstractSearchMappingProvider $mappingProvider
    ) {
        $this->placeholder = $placeholder;
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

        return $this->placeholder->replace($fields[$fieldName]['name'], $placeholders);
    }

    /**
     * @param $entityClass
     * @param array $placeholders
     * @return null|string
     */
    public function getPlaceholderEntityAlias($entityClass, array $placeholders)
    {
        $entityAlias = $this->mappingProvider->getEntityAlias($entityClass);

        return $this->placeholder->replace($entityAlias, $placeholders);
    }
}
