<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;

/**
 * Provides placeholder-resolved field names and entity aliases for website search operations.
 *
 * This provider combines search mapping information with placeholder replacement to generate concrete field names
 * and entity aliases from their placeholder-containing templates. It works with {@see AbstractSearchMappingProvider}
 * to retrieve field configurations and applies {@see PlaceholderInterface} to replace tokens with actual values.
 * This is essential for translating abstract field definitions (e.g., "price_WEBSITE_ID_CURRENCY") into concrete
 * field names (e.g., "price_1_USD") used in search queries and indexation.
 */
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

        foreach ($fields as $value) {
            if ($value['name'] === $fieldName) {
                $name = $value['name'];
            }
        }

        if (!isset($name)) {
            throw new \RuntimeException(sprintf('Cannot find %s field for %s class', $fieldName, $entityClass));
        }

        return $this->placeholder->replace($name, $placeholders);
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
