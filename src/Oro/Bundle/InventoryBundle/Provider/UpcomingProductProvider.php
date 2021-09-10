<?php

namespace Oro\Bundle\InventoryBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Exception\Fallback\FallbackFieldConfigurationMissingException;
use Oro\Bundle\EntityBundle\Exception\Fallback\InvalidFallbackKeyException;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Provides information on product's upcoming status and availability date.
 * If oro_inventory.hide_labels_past_availability_date config option is on then product looses upcoming status after
 * availability date if it's given.
 */
class UpcomingProductProvider
{
    public const IS_UPCOMING = 'isUpcoming';
    public const AVAILABILITY_DATE = 'availabilityDate';

    /**
     * @var EntityFallbackResolver
     */
    private $entityFallbackResolver;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(
        EntityFallbackResolver $entityFallbackResolver,
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager
    ) {
        $this->entityFallbackResolver = $entityFallbackResolver;
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
    }

    public function isUpcoming(Product $product): bool
    {
        $isUpcoming = $this->getIsUpcomingValue($product);

        if (!$isUpcoming) {
            return false;
        }

        if (!$this->isUpcomingStatusOffPastAvailabilityDate()) {
            return $isUpcoming;
        }

        $availabilityDate = $this->extractDate($product);
        if ($availabilityDate && $availabilityDate < new \DateTime('now', new \DateTimeZone('UTC'))) {
            return false;
        }

        return true;
    }

    /**
     * @throws \LogicException
     */
    public function getAvailabilityDate(Product $product): ?\DateTime
    {
        if (!$this->isUpcoming($product)) {
            throw new \LogicException('You cant get Availability Date for product, which is not upcoming');
        }

        $date = $this->extractDate($product);
        if (!$this->isUpcomingStatusOffPastAvailabilityDate()) {
            return $date && $date >= new \DateTime('now', new \DateTimeZone('UTC')) ? clone $date : null;
        }

        return $date;
    }

    /**
     * @param Product[]|iterable $products
     * @return \DateTime|null
     */
    public function getLatestAvailabilityDate($products): ?\DateTime
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

    private function getIsUpcomingValue(Product $product): bool
    {
        try {
            return (bool)$this->entityFallbackResolver->getFallbackValue($product, self::IS_UPCOMING);
        } catch (FallbackFieldConfigurationMissingException $e) {
            return false;
        } catch (InvalidFallbackKeyException $e) {
            return false;
        }
    }

    private function isUpcomingStatusOffPastAvailabilityDate(): bool
    {
        return $this->configManager->get(
            sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::HIDE_LABELS_PAST_AVAILABILITY_DATE)
        );
    }
}
