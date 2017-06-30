<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApi;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Processor\Shared\ProcessUnitPrecisions;

class ProcessUnitPrecisionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var CreateContextStub|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var ProcessUnitPrecisions
     */
    protected $processUnitPrecisions;

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

        $this->processUnitPrecisions = new ProcessUnitPrecisionsStub($this->doctrineHelper);
    }

    public function testProcess()
    {
        $this->context->setRequestData(ProcessUnitPrecisionsTestHelper::createRequestData());

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

        $this->processUnitPrecisions->process($this->context);
        $relationships = $this->context->getRequestData()['relationships'];

        $this->assertArrayHasKey(
            JsonApi::ID,
            $relationships[ProcessUnitPrecisions::PRIMARY_UNIT_PRECISION][JsonApi::DATA]
        );
        $this->assertArrayHasKey(
            JsonApi::TYPE,
            $relationships[ProcessUnitPrecisions::PRIMARY_UNIT_PRECISION][JsonApi::DATA]
        );
        $this->assertArrayHasKey(
            JsonApi::ID,
            $relationships[ProcessUnitPrecisions::UNIT_PRECISIONS][JsonApi::DATA][0]
        );
        $this->assertArrayHasKey(
            JsonApi::ID,
            $relationships[ProcessUnitPrecisions::UNIT_PRECISIONS][JsonApi::DATA][1]
        );
    }

    public function testProcessNoUnitPrecisions()
    {
        $requestData = ProcessUnitPrecisionsTestHelper::createRequestData();
        unset($requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS][ProcessUnitPrecisions::UNIT_PRECISIONS]);
        $this->context->setRequestData($requestData);
        $this->processUnitPrecisions->process($this->context);
        $this->assertSame($requestData, $this->context->getRequestData());
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
        $this->processUnitPrecisions->setContext($this->context);
        $result = $this->processUnitPrecisions->validateUnitPrecisions(
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
        $this->processUnitPrecisions->setContext($this->context);
        $result = $this->processUnitPrecisions->validatePrimaryUnitPrecision(
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

    /**
     * @return array
     */
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
