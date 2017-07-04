<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

class LoadCategoryForProduct implements ProcessorInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ValueNormalizer
     */
    protected $valueNormalizer;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ValueNormalizer $valueNormalizer
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        $data = $context->getResult();
        if (!is_array($data) || !isset($data[AbstractDocumentBuilder::DATA][JsonApiDocumentBuilder::ATTRIBUTES]['sku'])
        ) {
            return;
        }
        $relations = $data[AbstractDocumentBuilder::DATA][JsonApiDocumentBuilder::RELATIONSHIPS];

        /** @var Category $category */
        $category = $this->doctrineHelper->getEntityRepository(Category::class)->findOneByProductSku(
            $data[AbstractDocumentBuilder::DATA][JsonApiDocumentBuilder::ATTRIBUTES]['sku']
        );

        if (!$category) {
            return;
        }

        $type = ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            Category::class,
            new RequestType([RequestType::JSON_API]),
            false
        );

        $relations['category'] = [
            'data' => [
                'id' => $category->getId(),
                'type' => $type,
            ],
        ];

        $data[AbstractDocumentBuilder::DATA][JsonApiDocumentBuilder::RELATIONSHIPS] = $relations;

        $context->setResult($data);
    }

    /**
     * @param EntityDefinitionConfig $config
     * @param array $data
     *
     * @return mixed
     */
    protected function getPrimaryValue(EntityDefinitionConfig $config, array $data)
    {
        $result = null;
        $association = $config->getField($this->associationName);
        if (null !== $association) {
            $associationName = $association->getPropertyPath($this->associationName);
            if (!empty($data[$associationName]) && is_array($data[$associationName])) {
                $associationTargetConfig = $association->getTargetEntity();
                if (null !== $associationTargetConfig) {
                    $result = $this->extractPrimaryValue(
                        $data[$associationName],
                        $this->getPropertyPath($associationTargetConfig, $this->associationDataFieldName),
                        $this->getPropertyPath($associationTargetConfig, $this->associationPrimaryFlagFieldName)
                    );
                }
            }
        }

        return $result;
    }

    /**
     * @param array $items
     * @param string $dataFieldName
     * @param string $primaryFlagFieldName
     *
     * @return mixed
     */
    protected function extractPrimaryValue(array $items, $dataFieldName, $primaryFlagFieldName)
    {
        $result = null;
        foreach ($items as $item) {
            if (is_array($item)
                && array_key_exists($primaryFlagFieldName, $item)
                && $item[$primaryFlagFieldName]
                && array_key_exists($dataFieldName, $item)
            ) {
                $result = $item[$dataFieldName];
                break;
            }
        }

        return $result;
    }

    /**
     * @param EntityDefinitionConfig $config
     * @param string $fieldName
     *
     * @return string
     */
    protected function getPropertyPath(EntityDefinitionConfig $config, $fieldName)
    {
        $field = $config->getField($fieldName);
        if (null === $field) {
            return $fieldName;
        }

        return $field->getPropertyPath($fieldName);
    }
}
