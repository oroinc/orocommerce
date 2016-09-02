<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\TestFrameworkBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\OrmIndexer;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDatetime;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDecimal;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadItemData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\SearchTestTrait;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @dbIsolationPerTest
 */
class OrmIndexerTest extends WebTestCase
{
    use SearchTestTrait;

    /** @var AbstractSearchMappingProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $mappingProviderMock;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var OrmIndexer */
    protected $indexer;

    /** @var callable */
    protected $listener;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var array */
    protected $mappingConfig = [
        'Oro\Bundle\TestFrameworkBundle\Entity\Product' => [
            'alias' => 'orob2b_product_WEBSITE_ID',
            'fields' => [
                [
                    'name' => 'title_LOCALIZATION_ID',
                    'type' => 'text'
                ]
            ]
        ]
    ];

    protected function setUp()
    {
        $this->initClient();
        $this->doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $this->mappingProviderMock = $this->getMockBuilder(AbstractSearchMappingProvider::class)->getMock();
        $this->dispatcher = $this->getContainer()->get('event_dispatcher');
        $this->indexer = new OrmIndexer($this->dispatcher, $this->doctrineHelper, $this->mappingProviderMock);

        $this->loadFixtures([LoadItemData::class]);
    }

    protected function tearDown()
    {
        $this->truncateIndexTextTable();

        unset($this->mappingProviderMock, $this->dispatcher, $this->indexer, $this->listener, $this->doctrineHelper);
    }

    public function testCount()
    {
        $this->assertEntityCount(5, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(2, IndexText::class);
        $this->assertEntityCount(1, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }

    public function testRemoveEntitiesWhenNonExistentEntityRemoved()
    {
        $this->indexer->delete([$this->getProductEntity(123456)], ['website_id' => 1]);

        $this->assertEntityCount(5, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(2, IndexText::class);
        $this->assertEntityCount(1, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }

    public function testRemoveEntitiesWhenEntityIdsArrayIsEmpty()
    {
        $this->indexer->delete([], ['website_id' => 1]);

        $this->assertEntityCount(5, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(2, IndexText::class);
        $this->assertEntityCount(1, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }

    public function testRemoveEntitiesWhenProductEntitiesForSpecificWebsiteRemoved()
    {
        $this->mappingProviderMock
            ->expects($this->once())
            ->method('getEntityAlias')
            ->willReturn('orob2b_product_WEBSITE_ID');

        $this->indexer->delete(
            [
                $this->getProductEntity(1),
                $this->getProductEntity(2)
            ],
            ['website_id' => 1]
        );

        $this->assertEntityCount(3, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(1, IndexText::class);
        $this->assertEntityCount(0, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }

    public function testRemoveEntitiesWhenProductEntitiesForAllWebsitesRemoved()
    {
        $this->indexer->delete(
            [
                $this->getProductEntity(1),
                $this->getProductEntity(2)
            ],
            []
        );

        $this->assertEntityCount(1, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(0, IndexText::class);
        $this->assertEntityCount(0, IndexDatetime::class);
        $this->assertEntityCount(0, IndexDecimal::class);
    }

    /**
     * @param int $id
     * @return Product
     */
    private function getProductEntity($id)
    {
        $product = new Product();
        $product->setId($id);

        return $product;
    }
}
