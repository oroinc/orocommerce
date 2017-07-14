<?php

namespace Oro\Bundle\CatalogBundle\Api\Processor;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApi;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ContextErrorUtilTrait;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class RemoveCategoryFromProductRequest implements ProcessorInterface
{
    use ContextErrorUtilTrait;

    const CATEGORY = 'category';
    const CATEGORY_POINTER = [JsonApi::DATA, JsonApi::RELATIONSHIPS, self::CATEGORY];

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ValueNormalizer
     */
    protected $valueNormalizer;

    /**
     * @var EntityManager|null
     */
    protected $categoryEm;

    /**
     * @var EntityRepository|CategoryRepository
     */
    protected $categoryRepo;

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
        $this->categoryEm = $this->doctrineHelper->getEntityManager(Category::class);
        $this->categoryRepo = $this->categoryEm->getRepository(Category::class);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        $requestData = $context->getRequestData();

        if (!isset($requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS])) {
            return;
        }

        $relationships = $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS];
        if (!isset($relationships[self::CATEGORY])) {
            return;
        }

        $category = $this->validateCategoryRequest($context, $relationships);
        if (null === $category) {
            return;
        }
        // Remember the category, as we should only save it when other form validations passed
        $context->set(self::CATEGORY, $category);

        // Remove category information form the request to avoid form validation error on missing relation from product
        // to category
        unset($relationships[self::CATEGORY]);
        $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS] = $relationships;
        $context->setRequestData($requestData);
    }

    /**
     * @param ContextInterface $context
     * @param array $relationships
     * @return Category|null
     */
    protected function validateCategoryRequest(ContextInterface $context, $relationships)
    {
        if (!isset($relationships[self::CATEGORY][JsonApi::DATA])) {
            $this->addError(
                $this->buildPointer(self::CATEGORY_POINTER),
                sprintf("Category definition must have a '%s' key", JsonApi::DATA),
                $context
            );

            return null;
        }
        $categoryInfo = $relationships[self::CATEGORY][JsonApi::DATA];

        $type = ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            Category::class,
            new RequestType([RequestType::JSON_API]),
            false
        );
        if (!is_array($categoryInfo)
            || !array_key_exists('id', $categoryInfo)
            || !array_key_exists('type', $categoryInfo)
            || $categoryInfo['type'] !== $type
        ) {
            $parentPointer = $this->buildPointer(self::CATEGORY_POINTER);
            $this->addError(
                $this->buildPointer([JsonApi::DATA], $parentPointer),
                'Category definition must have a valid id and type',
                $context
            );

            return null;
        }

        /** @var Category $category */
        $category = $this->doctrineHelper->getEntityRepository(Category::class)->findOneBy(
            ['id' => $categoryInfo['id']]
        );

        if (!$category) {
            $this->addError(
                $this->buildPointer([JsonApi::DATA, 'id'], $this->buildPointer(self::CATEGORY_POINTER)),
                sprintf("Category id %s is not valid", $categoryInfo['id']),
                $context
            );
        }

        return $category;
    }
}
