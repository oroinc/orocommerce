<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Doctrine\DBAL\Exception\DriverException;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ProductBundle\Api\Model\ProductSearch;
use Oro\Bundle\SearchBundle\Query\Result as SearchResult;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads product search data using website search query.
 */
class LoadProductSearchData implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
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

        try {
            $searchResult = $query->getResult();
            $data = $this->loadData($searchResult, $config);
        } catch (\Exception $e) {
            if ($e instanceof DriverException
                || (
                    class_exists('Elasticsearch\Common\Exceptions\BadRequest400Exception')
                    && $e instanceof \Elasticsearch\Common\Exceptions\BadRequest400Exception
                )
            ) {
                $context->addError(
                    Error::createValidationError(Constraint::FILTER, 'Invalid search query.')
                        ->setSource(ErrorSource::createByParameter(
                            $this->getSearchQueryFilterName($context->getFilterValues())
                        ))
                );

                return;
            }

            throw $e;
        }

        $context->setResult($data);

        // set callback to be used to calculate total count
        $context->setTotalCountCallback(
            function () use ($searchResult) {
                return $searchResult->getRecordsCount();
            }
        );
    }

    /**
     * @param FilterValueAccessorInterface $filterValues
     *
     * @return string
     */
    private function getSearchQueryFilterName(FilterValueAccessorInterface $filterValues): string
    {
        $searchQueryFilterName = 'searchQuery';
        $searchQueryFilterValue = $filterValues->get($searchQueryFilterName);
        if (null === $searchQueryFilterValue) {
            return $searchQueryFilterName;
        }

        return $searchQueryFilterValue->getSourceKey();
    }

    /**
     * Replaces "." delimiter in property_path and depends_on attributes with "_".
     * It is required due to "." is used to specify a path to a property of a nested object.
     *
     * @param EntityDefinitionConfig $config
     * @param EntityMetadata         $metadata
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
     * @param SearchResult           $searchResult
     * @param EntityDefinitionConfig $config
     *
     * @return array
     */
    private function loadData(SearchResult $searchResult, EntityDefinitionConfig $config): array
    {
        $data = [];
        $booleanFieldNames = $this->getBooleanFieldNames($config);
        $unitsFieldName = 'text_product_units';
        /** @var SearchResultItem[] $items */
        $items = $searchResult->toArray();
        foreach ($items as $item) {
            $selectedData = $item->getSelectedData();
            $dataItem = new ProductSearch(
                $selectedData[$config->findFieldNameByPropertyPath('integer_product_id')]
            );
            // unserialize product units here to avoid doing it several times
            // in different "customize_loaded_data" processors
            if (array_key_exists($unitsFieldName, $selectedData)) {
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
                if (null !== $value && in_array($fieldName, $booleanFieldNames, true)) {
                    // convert boolean values to boolean data-type
                    // because the search index uses integer data-type for boolean values
                    $value = (boolean)$value;
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
     * @return string[]
     */
    private function getBooleanFieldNames(EntityDefinitionConfig $config): array
    {
        $booleanFieldNames = [];
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->getDataType() === DataType::BOOLEAN) {
                $booleanFieldNames[] = $fieldName;
            }
        }

        return $booleanFieldNames;
    }
}
