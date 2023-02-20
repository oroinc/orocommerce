<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ProductBundle\Api\Model\ProductSearch;
use Oro\Bundle\SearchBundle\Api\Exception\InvalidSearchQueryException;
use Oro\Bundle\SearchBundle\Api\Model\SearchResult;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads product search data using website search query.
 */
class LoadProductSearchData implements ProcessorInterface
{
    public const SEARCH_RESULT = 'search_result';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        if ($context->hasResult()) {
            // result data are already retrieved
            return;
        }

        $query = $context->getQuery();
        if (!$query instanceof WebsiteSearchQuery) {
            // unsupported query
            return;
        }

        $config = $context->getConfig();
        $this->updateConfigAndMetadata($config, $context->getMetadata());

        $searchResult = new SearchResult($query, $config->getHasMore());
        $context->set(self::SEARCH_RESULT, $searchResult);

        try {
            $searchRecords = $searchResult->getRecords();
        } catch (InvalidSearchQueryException $e) {
            $error = Error::createValidationError(Constraint::FILTER, $e->getMessage());
            $filterValue = $context->getFilterValues()->get('searchQuery');
            if (null !== $filterValue) {
                $error->setSource(ErrorSource::createByParameter($filterValue->getSourceKey()));
            }
            $context->addError($error);

            return;
        }

        $context->setResult($this->loadData($searchRecords, $config));

        // set callback to be used to calculate total count
        $context->setTotalCountCallback(function () use ($searchResult) {
            return $searchResult->getRecordsCount();
        });
    }

    /**
     * Replaces "." delimiter in property_path and depends_on attributes with "_".
     * It is required due to "." is used to specify a path to a property of a nested object.
     */
    private function updateConfigAndMetadata(EntityDefinitionConfig $config, EntityMetadata $metadata): void
    {
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            $propertyPath = $field->getPropertyPath();
            if ($propertyPath && ConfigUtil::IGNORE_PROPERTY_PATH !== $propertyPath) {
                $propertyPath = str_replace(ConfigUtil::PATH_DELIMITER, '_', $propertyPath);
                $field->setPropertyPath($propertyPath);
                $metadataProperty = $metadata->getProperty($fieldName);
                if (null !== $metadataProperty) {
                    $metadataProperty->setPropertyPath($propertyPath);
                }
            }
            $dependsOn = $field->getDependsOn();
            if (!empty($dependsOn)) {
                $updatedDependsOn = [];
                foreach ($dependsOn as $dependsOnFieldName) {
                    $updatedDependsOn[] = str_replace(ConfigUtil::PATH_DELIMITER, '_', $dependsOnFieldName);
                }
                $field->setDependsOn($updatedDependsOn);
            }
        }
        $idFieldName = 'id';
        $config->getField($idFieldName)->setPropertyPath($idFieldName);
        $metadata->getProperty($idFieldName)->setPropertyPath($idFieldName);
    }

    /**
     * @param SearchResultItem[]     $records
     * @param EntityDefinitionConfig $config
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function loadData(array $records, EntityDefinitionConfig $config): array
    {
        $data = [];
        $fieldsThatRequireTypeConversion = $this->getFieldsThatRequireTypeConversion($config);
        $unitsFieldName = 'text_product_units';
        foreach ($records as $key => $record) {
            if (ConfigUtil::INFO_RECORD_KEY === $key) {
                $data[$key] = $record;
                continue;
            }

            $selectedData = $record->getSelectedData();
            $dataItem = new ProductSearch(
                $selectedData[$config->findFieldNameByPropertyPath('integer_system_entity_id')]
            );
            // unserialize product units here to avoid doing it several times
            // in different "customize_loaded_data" processors
            if (\array_key_exists($unitsFieldName, $selectedData)) {
                $serializedUnits = $selectedData[$unitsFieldName];
                $selectedData[$unitsFieldName] = $serializedUnits
                    ? unserialize($serializedUnits, ['allowed_classes' => false])
                    : [];
            }
            foreach ($selectedData as $fieldName => $value) {
                if ('' === $value) {
                    // convert empty string to null for nullable data-types
                    // to avoid exceptions in data transformers
                    $value = null;
                }
                if (null !== $value && isset($fieldsThatRequireTypeConversion[$fieldName])) {
                    $dataType = $fieldsThatRequireTypeConversion[$fieldName];
                    if (DataType::BOOLEAN === $dataType) {
                        // convert boolean values to boolean data-type
                        // because the search index uses integer data-type for boolean values
                        $value = (boolean)$value;
                    } elseif (DataType::DATETIME === $dataType && !$value instanceof \DateTimeInterface) {
                        // convert datetime, date and time values to DateTime object
                        // because elasticsearch storage engine store them as a string
                        $value = \DateTime::createFromFormat('Y-m-d H:i:s', $value, new \DateTimeZone('UTC'));
                    }
                }
                $field = $config->getField($fieldName);
                $propertyPath = null !== $field
                    ? $field->getPropertyPath($fieldName)
                    : $fieldName;
                $dataItem[$propertyPath] = $value;
            }

            $data[] = $dataItem;
        }

        return $data;
    }

    /**
     * @param EntityDefinitionConfig $config
     *
     * @return array [field name => search data type, ...]
     */
    private function getFieldsThatRequireTypeConversion(EntityDefinitionConfig $config): array
    {
        $result = [];
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            switch ($field->getDataType()) {
                case DataType::BOOLEAN:
                    $result[$fieldName] = DataType::BOOLEAN;
                    break;
                case DataType::DATETIME:
                case DataType::DATE:
                case DataType::TIME:
                    $result[$fieldName] = DataType::DATETIME;
                    break;
            }
        }

        return $result;
    }
}
