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
     * @var ProcessUnitPrecisionsCreateStub
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
        $configProvider   = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder(MetadataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = new CreateContextStub($configProvider, $metadataProvider);
        $this->processUnitPrecisionsCreate = new ProcessUnitPrecisionsCreateStub($this->doctrineHelper);
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

    /**
     * @dataProvider getUnitPrecisionsProvider
     *
     * @param $requestData
     * @param $isValid
     */
    public function testValidateUnitPrecisions($requestData, $isValid)
    {
        $unitCodes = ['each', 'item', 'piece', 'set', 'kilogram', 'hour'];
        $productUnitRepo = $this->getMockBuilder(ProductUnitRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productUnitRepo->expects($this->once())
            ->method('getAllUnitCodes')
            ->willReturn($unitCodes);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(ProductUnit::class)
            ->willReturn($productUnitRepo);
        $relationships = $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS];
        $pointer = '/' . JsonApi::DATA . '/' . JsonApi::RELATIONSHIPS;
        $this->processUnitPrecisionsCreate->setContext($this->context);
        $result = $this->processUnitPrecisionsCreate->validateUnitPrecisions(
            $relationships[ProcessUnitPrecisions::UNIT_PRECISIONS],
            $pointer
        );

        $this->assertEquals($isValid, $result);
    }

    /**
     * @dataProvider getPrimaryUnitPrecisionProvider
     *
     * @param $requestData
     * @param $isValid
     */
    public function testValidatePrimaryUnitPrecision($requestData, $isValid)
    {
        $unitCodes = ['each', 'item', 'piece', 'set', 'kilogram', 'hour'];
        $productUnitRepo = $this->getMockBuilder(ProductUnitRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productUnitRepo->expects($this->once())
            ->method('getAllUnitCodes')
            ->willReturn($unitCodes);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(ProductUnit::class)
            ->willReturn($productUnitRepo);
        $relationships = $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS];
        $pointer = '/' . JsonApi::DATA . '/' . JsonApi::RELATIONSHIPS;
        $this->processUnitPrecisionsCreate->setContext($this->context);
        $result = $this->processUnitPrecisionsCreate->validatePrimaryUnitPrecision(
            $relationships,
            $pointer
        );

        $this->assertEquals($isValid, $result);
    }

    /**
     * @return array
     */
    public function getUnitPrecisionsProvider()
    {
        $requestData = ProcessUnitPrecisionsTestHelper::createRequestData();
        $wrongUnit = ProcessUnitPrecisionsTestHelper::setWrongUnitCode($requestData, 'test_unit');

        return [
            'valid_unit_precisions' => [
                'requestData' => $requestData,
                'isValid' => true,
            ],
            'wrong_unit' => [
                'requestData' => $wrongUnit,
                'isValid' => false,
            ],
            'unit_non_unique' => [
                'requestData' => ProcessUnitPrecisionsTestHelper::createRequestDataSameUnitCodes(),
                'isValid' => false,
            ],
        ];
    }

    public function getPrimaryUnitPrecisionProvider()
    {
        $requestData = ProcessUnitPrecisionsTestHelper::createRequestData();
        $wrongUnit = ProcessUnitPrecisionsTestHelper::setPrimaryUnitCode($requestData, 'test_item');

        return [
            'valid_primary_unit' => [
                'requestData' => $requestData,
                'isValid' => true,
            ],
            'wrong_primary_unit' => [
                'requestData' => $wrongUnit,
                'isValid' => false,
            ],
            'primary_unit_not_present_in_list' => [
                'requestData' => ProcessUnitPrecisionsTestHelper::createRequestDataWrongPrimaryUnit(),
                'isValid' => false,
            ]
        ];
    }
}
