<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

class LoadProductRelatedFallbackValuesData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param string $productName
     * @param string $fieldName
     * @return string
     */
    public static function getReferenceName($productName, $fieldName): string
    {
        return "$productName.entity_field_fallback_value.$fieldName";
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadProductData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($this->getFallbacks() as $fieldName => $fallbackValue) {
            $accessor->setValue($product, $fieldName, $fallbackValue);
            if ($fallbackValue) {
                $this->addReference(self::getReferenceName(LoadProductData::PRODUCT_1, $fieldName), $fallbackValue);
            }
        }
        $manager->flush();
    }

    /**
     * @return EntityFieldFallbackValue[]|null[]
     */
    protected function getFallbacks(): array
    {
        $systemFallback = new EntityFieldFallbackValue();
        $systemFallback->setFallback(SystemConfigFallbackProvider::FALLBACK_ID);

        $scalarValueFallback = new EntityFieldFallbackValue();
        $scalarValueFallback->setScalarValue(false);

        return [
            'manageInventory' => $systemFallback,
            'highlightLowInventory' => clone $systemFallback,
            'inventoryThreshold' => clone $systemFallback,
            'lowInventoryThreshold' => clone $systemFallback,
            'backOrder' => clone $systemFallback,
            'decrementQuantity' => clone $systemFallback,
            'isUpcoming' => $scalarValueFallback,
            'minimumQuantityToOrder' => null,
            'maximumQuantityToOrder' => clone $systemFallback,
        ];
    }
}
