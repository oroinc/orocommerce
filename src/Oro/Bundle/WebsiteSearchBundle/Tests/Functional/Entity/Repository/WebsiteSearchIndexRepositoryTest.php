<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\DataFixtures\ReferenceRepository;

use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoPgsql;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
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

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadItemData::class]);
    }

    protected function tearDown()
    {
        $this->truncateIndexTextTable();
    }

    private function skipIfEngineIsNotOrm()
    {
        if ($this->getContainer()->getParameter('oro_search.engine') != 'orm') {
            $this->markTestSkipped('Should be tested only with ORM search engine');
        }
    }

    /**
     * @return \Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository
     */
    private function getRepositoryWithDrivers()
    {
        $repository = $this->getItemRepository();
        $repository->setDriversClasses([
            'pdo_mysql' => PdoMysql::class,
            'pdo_pgsql' => PdoPgsql::class
        ]);

        return $repository;
    }

    public function testSearchDefaultWebsite()
    {
        $this->skipIfEngineIsNotOrm();

        $websiteId = $this
            ->getDoctrine()
            ->getRepository(Website::class)
            ->getDefaultWebsite()
            ->getId();

        $alias = 'oro_product_' . $websiteId;
        $query = new Query();
        $query->from($alias);

        $searchReferenceRepository = $this->getSearchReferenceRepository();

        $goodProductReference = $searchReferenceRepository->getReference(
            LoadItemData::getReferenceName(LoadItemData::REFERENCE_GOOD_PRODUCT, $websiteId)
        );

        $betterProductReference = $searchReferenceRepository->getReference(
            LoadItemData::getReferenceName(LoadItemData::REFERENCE_BETTER_PRODUCT, $websiteId)
        );

        $expectedProducts = [
            [
                'item' => $this->convertItemToArray($goodProductReference),
            ],
            [
                'item' => $this->convertItemToArray($betterProductReference),
            ]
        ];

        $this->assertEquals($expectedProducts, $this->getRepositoryWithDrivers()->search($query));
    }

    /**
     * @param Item $item
     * @return array
     */
    private function convertItemToArray(Item $item)
    {
        return [
            'id' => $item->getId(),
            'entity' => $item->getEntity(),
            'alias' => $item->getAlias(),
            'recordId' => $item->getRecordId(),
            'title' => $item->getTitle(),
            'changed' => $item->getChanged(),
            'createdAt' => $item->getCreatedAt(),
            'updatedAt' => $item->getUpdatedAt()
        ];
    }

    /**
     * @return ReferenceRepository
     */
    private function getSearchReferenceRepository()
    {
        return LoadItemData::getSearchReferenceRepository();
    }

    public function testSearchDefaultWebsiteWithContains()
    {
        $this->skipIfEngineIsNotOrm();

        $websiteId = $this
            ->getDoctrine()
            ->getRepository(Website::class)
            ->getDefaultWebsite()
            ->getId();

        $query = new Query();
        $query->from('oro_product_' . $websiteId);
        $query->getCriteria()->andWhere(Criteria::expr()->contains('long_description', 'Long description'));

        $referenceName = LoadItemData::getReferenceName(LoadItemData::REFERENCE_GOOD_PRODUCT, $websiteId);
        $expectedItem = $this->convertItemToArray(
            $this->getSearchReferenceRepository()->getReference($referenceName)
        );

        $itemResults = $this->getRepositoryWithDrivers()->search($query);
        $itemResult = reset($itemResults);

        $this->assertCount(1, $itemResults);
        $this->assertEquals($expectedItem, $itemResult['item']);
    }

    public function testSearchDefaultWebsiteWithEq()
    {
        $this->skipIfEngineIsNotOrm();

        $websiteId = $this
            ->getDoctrine()
            ->getRepository(Website::class)
            ->getDefaultWebsite()
            ->getId();

        $referenceName = LoadItemData::getReferenceName(LoadItemData::REFERENCE_BETTER_PRODUCT, $websiteId);
        $expectedItem = $this->convertItemToArray(
            $this->getSearchReferenceRepository()->getReference($referenceName)
        );

        $query = new Query();
        $query->from('oro_product_' . $websiteId);
        $query->getCriteria()->andWhere(Criteria::expr()->eq('integer.lucky_number', 777));

        $itemResults = $this->getRepositoryWithDrivers()->search($query);
        $itemResult = reset($itemResults);

        $this->assertCount(1, $itemResults);
        $this->assertEquals($expectedItem, $itemResult['item']);
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
            TestProduct::class,
            'oro_product_1'
        );

        $this->assertEntityCount(2, Item::class);
        $this->assertEntityCount(1, IndexText::class);
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
        $this->assertEntityCount(2, IndexInteger::class);
        $this->assertEntityCount(2, IndexText::class);
        $this->assertEntityCount(2, IndexDatetime::class);
        $this->assertEntityCount(2, IndexDecimal::class);
    }
}
