<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Processor\Create;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApi;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormContextStub;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Processor\Create\ProcessUnitPrecisionsCreate;
use Oro\Bundle\ProductBundle\Processor\Shared\ProcessUnitPrecisions;
use Oro\Bundle\ProductBundle\Tests\Unit\Processor\Shared\CreateContextStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Processor\Shared\ProcessUnitPrecisionsTestHelper;

class ProcessUnitPrecisionsCreateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /** @var SingleItemContext|FormContextStub|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;
    /**
     * @var ProcessUnitPrecisionsCreate
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
        $this->context = new CreateContextStub($configProvider, $metadataProvider);
        $this->processUnitPrecisionsCreate = new ProcessUnitPrecisionsCreate($this->doctrineHelper);
    }

    public function testHandleUnitPrecisions()
    {
        $requestData = ProcessUnitPrecisionsTestHelper::createRequestData();

        $productUnit = $this->createMock(ProductUnit::class);
        $productUnitRepo = $this->getMockBuilder(ProductUnitRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productUnitRepo->expects($this->exactly(2))
            ->method('find')
            ->willReturn($productUnit);

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->exactly(2))
            ->method('persist');
        $em->expects($this->exactly(2))
            ->method('flush');

        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityRepositoryForClass')
            ->with(ProductUnit::class)
            ->willReturn($productUnitRepo);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityManagerForClass')
            ->with(ProductUnitPrecision::class)
            ->willReturn($em);

        $requestData = $this->processUnitPrecisionsCreate->handleUnitPrecisions($requestData);
        $relationships = $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS];
        $unitPrecisions = $relationships[ProcessUnitPrecisions::UNIT_PRECISIONS];
        $primaryUnitPrecision = $relationships[ProcessUnitPrecisions::PRIMARY_UNIT_PRECISION];

        foreach ($unitPrecisions[JsonApi::DATA] as $unitPrecision) {
            $this->assertArrayHasKey(JsonApi::ID, $unitPrecision);
            $this->assertArrayHasKey(JsonApi::TYPE, $unitPrecision);
        }

        $this->assertArrayNotHasKey(ProcessUnitPrecisions::ATTR_UNIT_CODE, $primaryUnitPrecision[JsonApi::DATA]);
        $this->assertArrayHasKey(JsonApi::ID, $primaryUnitPrecision[JsonApi::DATA]);
        $this->assertArrayHasKey(JsonApi::TYPE, $primaryUnitPrecision[JsonApi::DATA]);
    }
}
