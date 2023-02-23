<?php

namespace Oro\Bundle\ProductBundle\Api;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\Extra\DataTransformersConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * Loads values for specified product attributes.
 */
class ProductAttributeValueLoader
{
    private DoctrineHelper $doctrineHelper;
    private ActionProcessorInterface $configProcessor;
    private EntitySerializer $entitySerializer;
    private MetadataProvider $metadataProvider;
    private EntityIdTransformerRegistry $entityIdTransformerRegistry;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ActionProcessorInterface $configProcessor,
        EntitySerializer $entitySerializer,
        MetadataProvider $metadataProvider,
        EntityIdTransformerRegistry $entityIdTransformerRegistry
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configProcessor = $configProcessor;
        $this->entitySerializer = $entitySerializer;
        $this->metadataProvider = $metadataProvider;
        $this->entityIdTransformerRegistry = $entityIdTransformerRegistry;
    }

    /**
     * @param array       $productIdsPerFamily [family id => [product id, ...], ...]
     * @param array       $attributesPerFamily [family id => [field name => NULL or [target field name, ...], ...], ...]
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return array [product id => [attribute name => attribute value, ...], ...]
     *               If an attribute is an association the attribute value is
     *               ['id' => association id, 'targetValue' => a string that can be used to display association]
     */
    public function loadAttributes(
        array $productIdsPerFamily,
        array $attributesPerFamily,
        string $version,
        RequestType $requestType
    ): array {
        $attributes = [];
        $idTransformer = $this->entityIdTransformerRegistry->getEntityIdTransformer($requestType);
        $normalizationContext = [
            ApiContext::VERSION      => $version,
            ApiContext::REQUEST_TYPE => $requestType
        ];
        foreach ($attributesPerFamily as $familyId => $fields) {
            $productIds = $productIdsPerFamily[$familyId];
            if (!empty($productIds)) {
                $attributesConfig = $this->getLoadAttributesConfig($familyId, $fields, $version, $requestType);
                $attributesData = $this->loadAttributesData($productIds, $attributesConfig, $normalizationContext);
                foreach ($attributesData as $items) {
                    $attributes[$items['id']] = $this->getAttributes(
                        $items,
                        $attributesConfig,
                        $version,
                        $requestType,
                        $idTransformer,
                        $fields
                    );
                }
            }
        }

        return $attributes;
    }

    /**
     * @param int         $familyId
     * @param array       $fields [field name => NULL or [target field name, ...], ...]
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return EntityDefinitionConfig
     */
    private function getLoadAttributesConfig(
        int $familyId,
        array $fields,
        string $version,
        RequestType $requestType
    ): EntityDefinitionConfig {
        $fields['id'] = null;
        $config = new EntityDefinitionConfig();
        foreach ($fields as $fieldName => $targetFields) {
            $fieldConfig = $config->addField($fieldName);
            if ($targetFields) {
                $targetConfig = $fieldConfig->createAndSetTargetEntity();
                foreach ($targetFields as $targetField) {
                    $targetConfig->addField($targetField);
                }
            }
        }

        /** @var ConfigContext $configContext */
        $configContext = $this->configProcessor->createContext();
        $configContext->setClassName(Product::class);
        $configContext->setVersion($version);
        $configContext->getRequestType()->set($requestType);
        $configContext->getRequestType()->add(sprintf('product_attributes_%s', $familyId));
        $configContext->setResult($config);
        $configContext->setExtras([
            new EntityDefinitionConfigExtra(ApiAction::GET_LIST, true),
            new DataTransformersConfigExtra()
        ]);
        $this->configProcessor->process($configContext);

        $fieldConfigs = $config->getFields();
        foreach ($fieldConfigs as $fieldName => $fieldConfig) {
            if ($fieldConfig->hasCollapsed()) {
                $fieldConfig->setCollapsed(false);
            }
            $fieldConfig->setExcluded(!\array_key_exists($fieldName, $fields));
        }

        return $config;
    }

    /**
     * @param int[]                  $productIds
     * @param EntityDefinitionConfig $config
     * @param array                  $normalizationContext
     *
     * @return array
     */
    private function loadAttributesData(
        array $productIds,
        EntityDefinitionConfig $config,
        array $normalizationContext
    ): array {
        $qb = $this->doctrineHelper
            ->createQueryBuilder(Product::class, 'p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $productIds);

        return $this->entitySerializer->serialize($qb, $config, $normalizationContext);
    }

    /**
     * @param array                        $items
     * @param EntityDefinitionConfig       $config
     * @param string                       $version
     * @param RequestType                  $requestType
     * @param EntityIdTransformerInterface $idTransformer
     * @param array                        $fields [field name => NULL or [target field name, ...], ...]
     *
     * @return array
     */
    private function getAttributes(
        array $items,
        EntityDefinitionConfig $config,
        string $version,
        RequestType $requestType,
        EntityIdTransformerInterface $idTransformer,
        array $fields
    ): array {
        $attributes = [];
        foreach ($items as $fieldName => $value) {
            if ('id' === $fieldName) {
                continue;
            }
            if (null !== $value) {
                /** @var EntityDefinitionFieldConfig $attributeConfig */
                $attributeConfig = $config->getField($fieldName);
                $targetConfig = $attributeConfig->getTargetEntity();
                if (null !== $targetConfig) {
                    $value = $this->getAssociationData(
                        $value,
                        $attributeConfig->isCollectionValuedAssociation(),
                        $this->metadataProvider->getMetadata(
                            $attributeConfig->getTargetClass(),
                            $version,
                            $requestType,
                            $targetConfig
                        ),
                        $idTransformer,
                        $fields[$fieldName]
                    );
                }
            }
            $attributes[$fieldName] = $value;
        }

        return $attributes;
    }

    /**
     * @param array                        $data
     * @param bool                         $isCollection
     * @param EntityMetadata               $metadata
     * @param EntityIdTransformerInterface $idTransformer
     * @param string[]                     $targetFieldNames
     *
     * @return array
     */
    private function getAssociationData(
        array $data,
        bool $isCollection,
        EntityMetadata $metadata,
        EntityIdTransformerInterface $idTransformer,
        ?array $targetFieldNames
    ): array {
        if ($isCollection) {
            $collectionItems = [];
            foreach ($data as $collectionItem) {
                $collectionItems[] = $this->getAssociationElementData(
                    $collectionItem,
                    $metadata,
                    $idTransformer,
                    $targetFieldNames
                );
            }

            return $collectionItems;
        }

        return $this->getAssociationElementData(
            $data,
            $metadata,
            $idTransformer,
            $targetFieldNames
        );
    }

    /**
     * @param array                        $data
     * @param EntityMetadata               $metadata
     * @param EntityIdTransformerInterface $idTransformer
     * @param string[]|null                $targetFieldNames
     *
     * @return array
     */
    private function getAssociationElementData(
        array $data,
        EntityMetadata $metadata,
        EntityIdTransformerInterface $idTransformer,
        ?array $targetFieldNames
    ): array {
        $id = [];
        $idFieldNames = $metadata->getIdentifierFieldNames();
        foreach ($idFieldNames as $fieldName) {
            $id[$fieldName] = $data[$fieldName];
        }
        if (\count($id) === 1) {
            $id = reset($id);
        }
        $id = $idTransformer->transform($id, $metadata);

        $targetValues = [];
        foreach ($targetFieldNames as $fieldName) {
            if (!empty($data[$fieldName])) {
                $targetValues[$fieldName] = $data[$fieldName];
            }
        }

        return ['id' => $id, 'targetValue' => implode(' ', $targetValues)];
    }
}
