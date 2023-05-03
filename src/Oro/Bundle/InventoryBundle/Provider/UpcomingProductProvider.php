<?php

namespace Oro\Bundle\InventoryBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Exception\Fallback\FallbackFieldConfigurationMissingException;
use Oro\Bundle\EntityBundle\Exception\Fallback\InvalidFallbackKeyException;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\InventoryBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Provides information on product's upcoming status and availability date.
 * If oro_inventory.hide_labels_past_availability_date config option is on then product looses upcoming status after
 * availability date if it's given.
 */
class UpcomingProductProvider
{
    public const IS_UPCOMING = 'isUpcoming';
    public const AVAILABILITY_DATE = 'availabilityDate';

    private EntityFallbackResolver $entityFallbackResolver;
    private ManagerRegistry $doctrine;
    private ConfigManager $configManager;
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        EntityFallbackResolver $entityFallbackResolver,
        ManagerRegistry $doctrine,
        ConfigManager $configManager,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->entityFallbackResolver = $entityFallbackResolver;
        $this->doctrine = $doctrine;
        $this->configManager = $configManager;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function isUpcoming(Product $product): bool
    {
        if (!$this->getIsUpcomingValue($product)) {
            return false;
        }

        if (!$this->isUpcomingStatusOffPastAvailabilityDate()) {
            return true;
        }

        $availabilityDate = $this->extractDate($product);

        return !($availabilityDate && $availabilityDate < new \DateTime('now', new \DateTimeZone('UTC')));
    }

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
     * @param iterable<Product> $products
     *
     * @return \DateTime|null
     */
    public function getLatestAvailabilityDate(iterable $products): ?\DateTime
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

    protected function extractDate(Product $product): ?\DateTime
    {
        /** @var EntityFieldFallbackValue|null $fallbackValue */
        $fallbackValue = $this->propertyAccessor->getValue($product, self::IS_UPCOMING);
        if ($fallbackValue && !$fallbackValue->getFallback()) {
            return $this->propertyAccessor->getValue($product, self::AVAILABILITY_DATE);
        }

        $category = $this->doctrine->getRepository(Category::class)->findOneByProduct($product);
        while (null !== $category) {
            /** @var EntityFieldFallbackValue|null $fallbackValue */
            $fallbackValue = $this->propertyAccessor->getValue($category, self::IS_UPCOMING);
            if ($fallbackValue && !$fallbackValue->getFallback()) {
                return $this->propertyAccessor->getValue($category, self::AVAILABILITY_DATE);
            }
            $category = $category->getParentCategory();
        }

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
