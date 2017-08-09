<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor\Shared;

use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApi;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;

class ProcessUnitPrecisionsTest extends FormProcessorTestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var ProcessUnitPrecisionsStub
     */
    protected $processor;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new ProcessUnitPrecisionsStub($this->doctrineHelper);
    }

    public function testProcess()
    {
        $unitCodes = ['each', 'item', 'piece', 'set', 'kilogram', 'hour'];
        $productUnitRepo = $this->createMock(ProductUnitRepository::class);
        $productUnitRepo->expects($this->once())
            ->method('getAllUnitCodes')
            ->willReturn($unitCodes);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(ProductUnit::class)
            ->willReturn($productUnitRepo);

        $this->context->setRequestData(ProcessUnitPrecisionsTestHelper::createRequestData());
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasErrors());
    }

    public function testProcessNoIncludedData()
    {
        $requestData = ProcessUnitPrecisionsTestHelper::createRequestData();
        unset($requestData[JsonApi::INCLUDED]);

        $this->context->setRequestData($requestData);
        $this->processor->process($this->context);

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
        $pointer = '/' . JsonApi::INCLUDED;

        $this->processor->setContext($this->context);
        $result = $this->processor->validateUnitPrecisions(
            $requestData[JsonApi::INCLUDED],
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
        $sameUnit = ProcessUnitPrecisionsTestHelper::setSameUnit($requestData, 'each');

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
                'requestData' => $sameUnit,
                'isValid' => false,
            ],
        ];
    }
}
