<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor\Update;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApi;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormContextStub;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Update\UpdateProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitPrecisionRepository;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor\Shared\ProcessUnitPrecisionsTestHelper;

class ProcessUnitPrecisionsUpdateTest extends UpdateProcessorTestCase
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
    protected $processor;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new ProcessUnitPrecisionsUpdateStub($this->doctrineHelper);
    }

    /**
     *
     * @param $requestData
     * @param $isValid
     */
    public function testValidateUnitPrecisions()
    {
        $unitCodes = ['each', 'item', 'piece', 'set', 'kilogram', 'hour'];
        $productUnitPrecisions = $this->getProductUnitPrecisions(['item', 'set', 'each']);
        $requestData = ProcessUnitPrecisionsTestHelper::createUpdateRequestData();
        $productUnitRepo = $this->createMock(ProductUnitRepository::class);
        $productUnitRepo->expects($this->once())
            ->method('getAllUnitCodes')
            ->willReturn($unitCodes);
        $productUnitPrecisionRepo = $this->createMock(ProductUnitPrecisionRepository::class);
        $productUnitPrecisionRepo->expects($this->once())
            ->method('getProductUnitPrecisionsByProductId')
            ->willReturn($productUnitPrecisions);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityRepositoryForClass')
            ->withConsecutive([ProductUnit::class], [ProductUnitPrecision::class])
            ->willReturnOnConsecutiveCalls($productUnitRepo, $productUnitPrecisionRepo);
        $pointer = '/' . JsonApi::INCLUDED;
        $this->processor->setContext($this->context);
        $result = $this->processor->validateUnitPrecisions(
            $requestData[JsonApi::INCLUDED],
            $pointer
        );

        $this->assertEquals(true, $result);
    }

    /**
     * @param array $codes
     * @return array
     */
    private function getProductUnitPrecisions($codes)
    {
        $productUnitPrecisions = [];
        $i = 0;
        foreach ($codes as $unitCode) {
            $productUnitPrecisions[] = $this->createProductUnitPrecision(++$i, $unitCode);
        }

        return $productUnitPrecisions;
    }

    /**
     * @param int $id
     * @param string $code
     * @return ProductUnitPrecisionStub
     */
    private function createProductUnitPrecision($id, $code)
    {
        $productUnitPrecision = new ProductUnitPrecisionStub();
        $productUnit = new ProductUnit();
        $product = new Product();
        $productUnit->setCode($code);
        $productUnitPrecision->setId($id);
        $productUnitPrecision->setUnit($productUnit);
        $productUnitPrecision->setProduct($product);
        $productUnitPrecision->setConversionRate(3);
        $productUnitPrecision->setSell(true);

        return $productUnitPrecision;
    }
}
