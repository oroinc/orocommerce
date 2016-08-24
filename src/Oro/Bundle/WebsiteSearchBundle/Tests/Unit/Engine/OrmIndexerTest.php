<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebsiteSearchBundle\Engine\OrmIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\Mapper;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository;
use Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\ProductEntity;
use Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\PageEntity;

class OrmIndexerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDispatcher;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var MappingConfigurationProvider|Mapper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mappingConfigurationProvider;

    /**
     * @var WebsiteSearchIndexRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $indexRepository;

    /**
     * @var OrmIndexer
     */
    private $indexer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mappingConfigurationProvider = $this->getMockBuilder(Mapper::class)
            ->setMethods(['getEntityAlias'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexRepository = $this->getMockBuilder(WebsiteSearchIndexRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexer = new OrmIndexer(
            $this->eventDispatcher,
            $this->doctrineHelper,
            $this->mappingConfigurationProvider,
            $this->indexRepository
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->eventDispatcher,
            $this->doctrineHelper,
            $this->mappingConfigurationProvider,
            $this->indexRepository,
            $this->indexer
        );
    }

    public function testDeleteWhenEmptyEntitiesArrayGiven()
    {
        $this->indexRepository
            ->expects($this->never())
            ->method('removeItemEntities');

        $this->indexer->delete([], []);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Entities must be of the same type
     */
    public function testDeleteWhenDifferentEntitiesAreInArray()
    {
        $productEntity = $this->getMockBuilder(ProductEntity::class)
            ->getMock();

        $pageEntity = $this->getMockBuilder(PageEntity::class)
            ->getMock();

        $this->doctrineHelper
            ->expects($this->exactly(3))
            ->method('getEntityClass')
            ->withConsecutive([$productEntity], [$productEntity], [$pageEntity])
            ->will($this->onConsecutiveCalls(
                'Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\ProductEntity',
                'Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\ProductEntity',
                'Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\PageEntity'
            ));

        $this->indexer->delete([$productEntity, $pageEntity], []);
    }

    public function testDeleteWhenNoWebsiteIdGiven()
    {
        $firstProduct = $this->getMockBuilder(ProductEntity::class)
            ->getMock();

        $secondProduct = $this->getMockBuilder(ProductEntity::class)
            ->getMock();

        $this->doctrineHelper
            ->expects($this->exactly(3))
            ->method('getEntityClass')
            ->withConsecutive([$firstProduct], [$firstProduct], [$secondProduct])
            ->will($this->onConsecutiveCalls(
                'Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\ProductEntity',
                'Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\ProductEntity',
                'Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\ProductEntity'
            ));

        $this->doctrineHelper
            ->expects($this->exactly(2))
            ->method('getSingleEntityIdentifier')
            ->withConsecutive([$firstProduct], [$secondProduct])
            ->will($this->onConsecutiveCalls(1, 2));

        $this->indexRepository
            ->expects($this->once())
            ->method('removeItemEntities')
            ->with(
                [1, 2],
                'Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\ProductEntity',
                null
            );

        $this->indexer->delete([$firstProduct, $secondProduct], []);
    }

    public function testDeleteWhenWebsiteIdGiven()
    {
        $firstProduct = $this->getMockBuilder(ProductEntity::class)
            ->getMock();

        $secondProduct = $this->getMockBuilder(ProductEntity::class)
            ->getMock();

        $this->doctrineHelper
            ->expects($this->exactly(3))
            ->method('getEntityClass')
            ->withConsecutive([$firstProduct], [$firstProduct], [$secondProduct])
            ->will($this->onConsecutiveCalls(
                'Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\ProductEntity',
                'Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\ProductEntity',
                'Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\ProductEntity'
            ));

        $this->doctrineHelper
            ->expects($this->exactly(2))
            ->method('getSingleEntityIdentifier')
            ->withConsecutive([$firstProduct], [$secondProduct])
            ->will($this->onConsecutiveCalls(1, 2));

        $this->mappingConfigurationProvider
            ->expects($this->once())
            ->method('getEntityAlias')
            ->willReturn('Product_Entity_Alias_WEBSITE_ID');

        $this->indexRepository
            ->expects($this->once())
            ->method('removeItemEntities')
            ->with(
                [1, 2],
                'Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\ProductEntity',
                'Product_Entity_Alias_777'
            );

        $this->indexer->delete([$firstProduct, $secondProduct], ['website_id' => 777]);
    }
}
