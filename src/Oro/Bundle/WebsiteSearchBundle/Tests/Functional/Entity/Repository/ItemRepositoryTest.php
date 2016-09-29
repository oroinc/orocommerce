<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDatetime;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDecimal;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadItemData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadProductsToIndex;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\AbstractSearchWebTestCase;

/**
 * @dbIsolationPerTest
 */
class ItemRepositoryTest extends AbstractSearchWebTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([LoadItemData::class]);
    }

    public function testRemoveIndexByAlias()
    {
        $this->getItemRepository()->removeIndexByAlias('oro_product_1');
        $realAliasesLeft = $this->getItemRepository()->findBy(['alias' => 'oro_product_1']);
        $this->assertEmpty($realAliasesLeft);
    }

    public function testRenameIndexAlias()
    {
        $this->getItemRepository()->renameIndexAlias('oro_product_1', 'oro_product_website_temp_alias_1');
        $realAliasesLeft = $this->getItemRepository()->findBy(['alias' => 'oro_product_website_temp_alias_1']);
        $this->assertCount(2, $realAliasesLeft);
    }

    public function testRemoveEntitiesWhenEmptyIdsArrayGiven()
    {
        $this->getItemRepository()->removeEntities([], TestProduct::class);

        $this->assertEntityCount(8, Item::class);
        $this->assertEntityCount(2, IndexInteger::class);
        $this->assertEntityCount(6, IndexText::class);
        $this->assertEntityCount(2, IndexDatetime::class);
        $this->assertEntityCount(2, IndexDecimal::class);
    }

    public function testRemoveEntitiesForSpecificWebsite()
    {
        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $product2 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2);

        $this->getItemRepository()->removeEntities(
            [
                $product1->getId(),
                $product2->getId(),
            ],
            TestProduct::class,
            'oro_product_1'
        );

        $this->assertEntityCount(6, Item::class);
        $this->assertEntityCount(5, IndexText::class);
        $this->assertEntityCount(1, IndexDecimal::class);
        $this->assertEntityCount(1, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }

    public function testRemoveEntitiesForAllWebsites()
    {
        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $product2 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2);

        $this->getItemRepository()->removeEntities(
            [
                $product1->getId(),
                $product2->getId(),
            ],
            TestProduct::class
        );

        $this->assertEntityCount(4, Item::class);
        $this->assertEntityCount(0, IndexInteger::class);
        $this->assertEntityCount(4, IndexText::class);
        $this->assertEntityCount(0, IndexDecimal::class);
        $this->assertEntityCount(0, IndexDatetime::class);
    }

    public function testRemoveEntitiesForNonExistentEntities()
    {
        $this->getItemRepository()->removeEntities([91, 92], 'SomeClass');

        $this->assertEntityCount(8, Item::class);
        $this->assertEntityCount(2, IndexInteger::class);
        $this->assertEntityCount(6, IndexText::class);
        $this->assertEntityCount(2, IndexDatetime::class);
        $this->assertEntityCount(2, IndexDecimal::class);
    }

    public function testRemoveIndexByClassForAllClasses()
    {
        $this->getItemRepository()->removeIndexByClass();

        $this->assertEntityCount(0, Item::class);
        $this->assertEntityCount(0, IndexInteger::class);
        $this->assertEntityCount(0, IndexText::class);
        $this->assertEntityCount(0, IndexDatetime::class);
        $this->assertEntityCount(0, IndexDecimal::class);
    }

    public function testClearIndexDataForSpecificClass()
    {
        $this->getItemRepository()->removeIndexByClass(TestProduct::class);

        $this->assertEntityCount(4, Item::class);
        $this->assertEntityCount(0, IndexInteger::class);
        $this->assertEntityCount(4, IndexText::class);
        $this->assertEntityCount(0, IndexDatetime::class);
        $this->assertEntityCount(0, IndexDecimal::class);
    }
}
