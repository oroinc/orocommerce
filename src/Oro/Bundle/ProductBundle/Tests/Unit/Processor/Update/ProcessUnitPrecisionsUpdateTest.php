<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Processor\Update;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApi;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormContextStub;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitPrecisionRepository;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Processor\Shared\ProcessUnitPrecisions;
use Oro\Bundle\ProductBundle\Tests\Unit\Processor\Shared\ProcessUnitPrecisionsTestHelper;
use Oro\Bundle\ProductBundle\Tests\Unit\Processor\Shared\UpdateContextStub;

class ProcessUnitPrecisionsUpdateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /** @var SingleItemContext|FormContextStub|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;
    /**
     * @var ProcessUnitPrecisionsUpdateStub
     */
    protected $processUnitPrecisionsCreate;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder(MetadataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = new UpdateContextStub($configProvider, $metadataProvider);
        $this->processUnitPrecisionsCreate = new ProcessUnitPrecisionsUpdateStub($this->doctrineHelper);
    }

    public function testHandleUnitPrecisions()
    {
        $requestData = ProcessUnitPrecisionsTestHelper::createRequestData();
        $this->context->set(JsonApi::ID, 1);
        $this->processUnitPrecisionsCreate->setContext($this->context);

        $productUnitPrecisionRepo = $this->getMockBuilder(ProductUnitPrecisionRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productUnitPrecisionRepo->expects($this->once())
            ->method('getProductUnitPrecisionsByProductId')
            ->willReturn(ProcessUnitPrecisionsTestHelper::getProducUnitPrecisions(['each', 'item']));

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->exactly(2))
            ->method('persist');

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(ProductUnitPrecision::class)
            ->willReturn($productUnitPrecisionRepo);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityManagerForClass')
            ->with(ProductUnitPrecision::class)
            ->willReturn($em);

        $result = $this->processUnitPrecisionsCreate->handleUnitPrecisions($requestData);

        $relationships = $result[JsonApi::DATA][JsonApi::RELATIONSHIPS];
        $unitPrecisions = $relationships[ProcessUnitPrecisions::UNIT_PRECISIONS];
        $primaryUnitPrecision = $relationships[ProcessUnitPrecisions::PRIMARY_UNIT_PRECISION];

        foreach ($unitPrecisions[JsonApi::DATA] as $unitPrecision) {
            $this->assertArrayHasKey(JsonApi::ID, $unitPrecision);
            $this->assertArrayHasKey(JsonApi::TYPE, $unitPrecision);
        }

        $this->assertArrayNotHasKey(ProcessUnitPrecisions::ATTR_UNIT_CODE, $primaryUnitPrecision[JsonApi::DATA]);
        $this->assertArrayHasKey(JsonApi::ID, $primaryUnitPrecision[JsonApi::DATA]);
        $this->assertArrayHasKey(JsonApi::TYPE, $primaryUnitPrecision[JsonApi::DATA]);
        $this->assertTrue($this->context->has('addedUnits'));
        $this->assertCount(0, $this->context->get('addedUnits'));
    }

    public function testHandleUnitPrecisionsCreateNewPrecision()
    {
        $requestData = ProcessUnitPrecisionsTestHelper::createUpdateRequestData();
        $this->context->set(JsonApi::ID, 1);
        $this->processUnitPrecisionsCreate->setContext($this->context);

        $productUnitPrecisionRepo = $this->getMockBuilder(ProductUnitPrecisionRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productUnitPrecisionRepo->expects($this->once())
            ->method('getProductUnitPrecisionsByProductId')
            ->willReturn(ProcessUnitPrecisionsTestHelper::getProducUnitPrecisions(['each', 'item']));

        $productUnit = $this->createMock(ProductUnit::class);
        $productUnitRepo = $this->getMockBuilder(ProductUnitRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productUnitRepo->expects($this->once())
            ->method('find')
            ->willReturn($productUnit);

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->exactly(3))
            ->method('persist');
        $em->expects($this->once())
            ->method('flush');

        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityRepositoryForClass')
            ->withConsecutive(
                [ProductUnitPrecision::class],
                [ProductUnit::class]
            )
            ->willReturnOnConsecutiveCalls($productUnitPrecisionRepo, $productUnitRepo);
        $this->doctrineHelper->expects($this->exactly(3))
            ->method('getEntityManagerForClass')
            ->with(ProductUnitPrecision::class)
            ->willReturn($em);

        $result = $this->processUnitPrecisionsCreate->handleUnitPrecisions($requestData);

        $relationships = $result[JsonApi::DATA][JsonApi::RELATIONSHIPS];
        $unitPrecisions = $relationships[ProcessUnitPrecisions::UNIT_PRECISIONS];
        $primaryUnitPrecision = $relationships[ProcessUnitPrecisions::PRIMARY_UNIT_PRECISION];

        foreach ($unitPrecisions[JsonApi::DATA] as $unitPrecision) {
            $this->assertArrayHasKey(JsonApi::ID, $unitPrecision);
            $this->assertArrayHasKey(JsonApi::TYPE, $unitPrecision);
        }

        $this->assertArrayNotHasKey(ProcessUnitPrecisions::ATTR_UNIT_CODE, $primaryUnitPrecision[JsonApi::DATA]);
        $this->assertArrayHasKey(JsonApi::ID, $primaryUnitPrecision[JsonApi::DATA]);
        $this->assertArrayHasKey(JsonApi::TYPE, $primaryUnitPrecision[JsonApi::DATA]);
        $this->assertTrue($this->context->has('addedUnits'));
        $this->assertCount(1, $this->context->get('addedUnits'));
    }
}
