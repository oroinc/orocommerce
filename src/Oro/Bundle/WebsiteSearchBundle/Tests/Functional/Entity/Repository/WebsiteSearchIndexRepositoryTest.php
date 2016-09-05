<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\TestFrameworkBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\ORM\OrmEngine;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDatetime;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDecimal;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadItemData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadProductsToIndex;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\SearchTestTrait;

/**
 * @dbIsolationPerTest
 */
class WebsiteSearchIndexRepositoryTest extends WebTestCase
{
    use SearchTestTrait;

    /**
     * @var OrmEngine
     */
    protected $ormEngine;

    /**
     * @var ObjectManager
     */
    protected $manager;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadItemData::class]);

        $this->ormEngine = $this->getContainer()->get('oro_website_search.orm.engine');
        $this->manager = $this->getContainer()->get('doctrine')->getManager();
    }

    protected function tearDown()
    {
        $this->truncateIndexTextTable();

        unset($this->ormEngine, $this->manager);
    }

    public function testRemoveIndexByAlias()
    {
        $this->getItemRepository()->removeIndexByAlias('oro_product_website_1');
        $realAliasesLeft = $this->getItemRepository()->findBy(['alias' => 'oro_product_website_1']);
        $this->assertCount(0, $realAliasesLeft);
    }

    public function testRenameIndexAlias()
    {
        $this->getItemRepository()->renameIndexAlias('oro_product_website_1', 'oro_product_website_temp_alias_1');
        $realAliasesLeft = $this->getItemRepository()->findBy(['alias' => 'oro_product_website_temp_alias_1']);
        $this->assertCount(2, $realAliasesLeft);
    }

    public function testRemoveEntitiesWhenEmptyIdsArrayGiven()
    {
        $this->getItemRepository()->removeEntities([], Product::class);

        $this->assertEntityCount(4, Item::class);
        $this->assertEntityCount(2, IndexInteger::class);
        $this->assertEntityCount(2, IndexText::class);
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
            Product::class,
            'oro_product_website_1'
        );

        $this->assertEntityCount(2, Item::class);
        $this->assertEntityCount(1, IndexText::class);
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
            Product::class
        );

        $this->assertEntityCount(0, Item::class);
        $this->assertEntityCount(0, IndexInteger::class);
        $this->assertEntityCount(0, IndexText::class);
        $this->assertEntityCount(0, IndexDecimal::class);
        $this->assertEntityCount(0, IndexDatetime::class);
    }

    public function testRemoveEntitiesForNonExistentEntities()
    {
        $this->getItemRepository()->removeEntities([91, 92], 'SomeClass');

        $this->assertEntityCount(4, Item::class);
    }

    public function testSearchAll()
    {
        $query = new Query();
        $query->from('*');

        $goodProduct = $this->getContainer()->get('doctrine')->getRepository(Product::class)
            ->findOneBy(['name' => 'Product 1']);
        $betterProduct = $this->getContainer()->get('doctrine')->getRepository(Product::class)
            ->findOneBy(['name' => 'Product 2']);

        $expectedResult = new Result(
            $query,
            [
                new Result\Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Product',
                    $goodProduct->getId(),
                    'Good product',
                    null,
                    [],
                    []
                ),
                new Result\Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Product',
                    $betterProduct->getId(),
                    'Better product',
                    null,
                    [],
                    []
                ),
                new Result\Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Product',
                    $goodProduct->getId(),
                    'Good product',
                    null,
                    [],
                    []
                ),
                new Result\Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Product',
                    $betterProduct->getId(),
                    'Better product',
                    null,
                    [],
                    []
                ),
            ],
            4
        );

        $this->assertEquals($expectedResult, $this->ormEngine->search($query));
    }

    public function testSearchByAlias()
    {
        $query = new Query();
        $query->from('oro_product_website_WEBSITE_ID');

        $goodProduct = $this->getContainer()->get('doctrine')->getRepository(Product::class)
            ->findOneBy(['name' => 'Product 1']);
        $betterProduct = $this->getContainer()->get('doctrine')->getRepository(Product::class)
            ->findOneBy(['name' => 'Product 2']);

        $expectedResult = new Result(
            $query,
            [
                new Result\Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Product',
                    $goodProduct->getId(),
                    'Good product',
                    null,
                    [],
                    []
                ),
                new Result\Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Product',
                    $betterProduct->getId(),
                    'Better product',
                    null,
                    [],
                    []
                ),
            ],
            2
        );

        $this->assertEquals($expectedResult, $this->ormEngine->search($query));
    }

    public function testSearchByAliasWithCriteria()
    {
        $query = new Query();
        $query->from('oro_product_website_WEBSITE_ID');
        $expr = new Comparison("long_description", "=", "Long description");
        $criteria = new Criteria();
        $criteria->where($expr);
        $query->setCriteria($criteria);

        $goodProduct = $this->getContainer()->get('doctrine')->getRepository(Product::class)
            ->findOneBy(['name' => 'Product 1']);

        $expectedResult = new Result(
            $query,
            [
                new Result\Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Product',
                    $goodProduct->getId(),
                    'Good product',
                    null,
                    [],
                    []
                ),
            ],
            1
        );

        $this->assertEquals($expectedResult, $this->ormEngine->search($query));
    }
}
