<?php

declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

final class ProductEntityFallbackFieldEventListenerTest extends WebTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initClient();
        $this->loadFixtures([LoadOrganization::class, LoadAttributeFamilyData::class]);
        $this->em = self::getContainer()->get('doctrine')->getManagerForClass(Product::class);
    }

    public function testOnPrePersist(): void
    {
        $product = new Product();
        $name = new ProductName();
        $name->setString('name');
        $product->setNames([$name]);
        $product->setSku('test-product-123');
        $product->setOrganization($this->getReference('organization'));
        $product->setStatus(Product::STATUS_ENABLED);
        $product->setAttributeFamily($this->getReference('attribute_family_1'));

        $this->em->persist($product);
        $this->em->flush();

        $this->em->clear();

        $persistedProduct = $this->em->find(Product::class, $product->getId());

        $fallbackFields = [
            'manageInventory',
            'highlightLowInventory',
            'inventoryThreshold',
            'lowInventoryThreshold',
            'backOrder',
            'decrementQuantity',
            UpcomingProductProvider::IS_UPCOMING,
            'minimumQuantityToOrder',
            'maximumQuantityToOrder',
        ];

        $propertyAccessor = self::getContainer()->get('property_accessor');
        foreach ($fallbackFields as $fieldName) {
            $fallbackValue = $propertyAccessor->getValue($persistedProduct, $fieldName);
            self::assertNotNull($fallbackValue);
            self::assertEquals(CategoryFallbackProvider::FALLBACK_ID, $fallbackValue->getFallback());
        }
    }
}
