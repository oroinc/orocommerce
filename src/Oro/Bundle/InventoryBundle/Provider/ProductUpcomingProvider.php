<?php

namespace Oro\Bundle\InventoryBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Exception\Fallback\FallbackFieldConfigurationMissingException;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ProductUpcomingProvider
{
    const IS_UPCOMING = 'isUpcoming';
    const AVAILABILITY_DATE = 'availabilityDate';

    /**
     * @var EntityFallbackResolver
     */
    protected $entityFallbackResolver;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param EntityFallbackResolver $entityFallbackResolver
     */
    public function __construct(EntityFallbackResolver $entityFallbackResolver, DoctrineHelper $doctrineHelper)
    {
        $this->entityFallbackResolver = $entityFallbackResolver;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isUpcoming(Product $product)
    {
        try {
            return (bool)$this->entityFallbackResolver->getFallbackValue($product, self::IS_UPCOMING);
        } catch (FallbackFieldConfigurationMissingException $e) {
            return false;
        }
    }

    /**
     * @param Product $product
     * @throws \LogicException
     * @return \DateTime|null
     */
    public function getAvailabilityDate(Product $product)
    {
        if (!$this->isUpcoming($product)) {
            throw new \LogicException('You cant get Availability Date for product, which is not upcoming');
        }

        $date = $this->extractDate($product);
        return $date && $date >= new \DateTime('now', new \DateTimeZone('UTC')) ? clone $date : null;
    }

    /**
     * @param Product[]|iterable $products
     * @return \DateTime|null
     */
    public function getLatestAvailabilityDate($products)
    {
        $latestDate = null;
        foreach ($products as $product) {
            if ($this->isUpcoming($product)) {
                $date = $this->getAvailabilityDate($product);
                if ($date && (!$latestDate || $date > $latestDate)) {
                    $latestDate = $date;
                }
            }
        }
        return $latestDate ? clone $latestDate : null;
    }

    /**
     * @param Product $product
     * @return null|\DateTime
     */
    protected function extractDate(Product $product)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        /** @var EntityFieldFallbackValue|null $fallbackValue */
        $fallbackValue = $accessor->getValue($product, self::IS_UPCOMING);
        if ($fallbackValue && !$fallbackValue->getFallback()) {
            return $accessor->getValue($product, self::AVAILABILITY_DATE);
        }

        /** @var CategoryRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepositoryForClass(Category::class);
        $category = $repo->findOneByProduct($product);
        if (!$category instanceof Category) {
            return null;
        }

        do {
            /** @var EntityFieldFallbackValue|null $fallbackValue */
            $fallbackValue = $accessor->getValue($category, self::IS_UPCOMING);
            if ($fallbackValue && !$fallbackValue->getFallback()) {
                return $accessor->getValue($category, self::AVAILABILITY_DATE);
            }
        } while ($category = $category->getParentCategory());

        return null;
    }
}
