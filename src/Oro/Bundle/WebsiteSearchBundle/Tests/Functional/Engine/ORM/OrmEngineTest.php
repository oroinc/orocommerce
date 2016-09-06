<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\TestFrameworkBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\ORM\OrmEngine;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadItemData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\SearchTestTrait;

/**
 * @dbIsolationPerTest
 */
class OrmEngineTest extends WebTestCase
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
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Product',
                    $goodProduct->getId(),
                    'Good product',
                    null,
                    [],
                    []
                ),
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Product',
                    $betterProduct->getId(),
                    'Better product',
                    null,
                    [],
                    []
                ),
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Product',
                    $goodProduct->getId(),
                    'Good product',
                    null,
                    [],
                    []
                ),
                new Item(
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
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Product',
                    $goodProduct->getId(),
                    'Good product',
                    null,
                    [],
                    []
                ),
                new Item(
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
                new Item(
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
