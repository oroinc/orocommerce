<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Component\ChainProcessor\ProcessorInterface;

abstract class AbstractLoadCategoryForProduct implements ProcessorInterface
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
     * @param array $productInfo
     * @return array
     */
    protected function includeCategoryInResult(array $productInfo)
    {
        if (!isset(
            $productInfo[JsonApiDocumentBuilder::RELATIONSHIPS],
            $productInfo[JsonApiDocumentBuilder::ATTRIBUTES]['sku']
        )) {
            return $productInfo;
        }

        $relations = $productInfo[JsonApiDocumentBuilder::RELATIONSHIPS];

        /** @var Category $category */
        $category = $this->doctrineHelper->getEntityRepository(Category::class)->findOneByProductSku(
            $productInfo[JsonApiDocumentBuilder::ATTRIBUTES]['sku']
        );

        if (!$category) {
            return $productInfo;
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

        $productInfo[JsonApiDocumentBuilder::RELATIONSHIPS] = $relations;

        return $productInfo;
    }
}
