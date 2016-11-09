<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Api\Processor;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\InventoryBundle\Api\Processor\NormalizeInventoryLevelRequestData;

class NormalizeInventoryLevelRequestDataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var NormalizeInventoryLevelRequestData
     */
    protected $normalizeInventoryLevelRequestData;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->normalizeInventoryLevelRequestData = new NormalizeInventoryLevelRequestData(
            $this->doctrineHelper
        );
    }

    public function testProcessNoRequestData()
    {
        /** @var FormContext|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock(FormContext::class);
        $context->expects($this->once())->method('getRequestData')->willReturn(null);
        $context->expects($this->never())->method('setRequestData');

        $this->normalizeInventoryLevelRequestData->process($context);
    }

    public function testProcessNoDataOnRequestData()
    {
        /** @var FormContext|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock(FormContext::class);
        $context->expects($this->once())->method('getRequestData')->willReturn([]);
        $context->expects($this->never())->method('setRequestData');

        $this->normalizeInventoryLevelRequestData->process($context);
    }

    public function testProcessNoRelationshipsOnRequestData()
    {
        /** @var FormContext|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock(FormContext::class);
        $context->expects($this->once())->method('getRequestData')->willReturn(['data' => []]);

        $this->normalizeInventoryLevelRequestData->process($context);

        $context->expects($this->never())->method('setRequestData');
    }

    public function testProcessNoProductOnRequestData()
    {
        /** @var FormContext|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock(FormContext::class);
        $context->expects($this->once())->method('getRequestData')->willReturn(['data' => ['relationships' => []]]);
        $context->expects($this->never())->method('setRequestData');

        $this->normalizeInventoryLevelRequestData->process($context);
    }

    public function testProcessProductNotFount()
    {
        /** @var FormContext|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock(FormContext::class);
        $productRepository = $this->getMockBuilder(ProductRepository::class)->disableOriginalConstructor()->getMock();
        $data = [
            'data' => [
                'relationships' => [
                    'product' => ['data' => ['id' => 'product.1', 'type' => ProductUnit::class]],
                ]
            ]
        ];

        $context->expects($this->once())->method('getRequestData')->willReturn($data);
        $this
            ->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with($this->equalTo(Product::class))
            ->willReturn($productRepository);
        $productRepository
            ->expects($this->once())
            ->method('getProductsIdsBySku')
            ->with($this->equalTo(['product.1']))
            ->willReturn([null]);
        $context->expects($this->never())->method('setRequestData');

        $this->normalizeInventoryLevelRequestData->process($context);
    }

    public function testProcessNoUnitOnRequestData()
    {
        /** @var FormContext|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock(FormContext::class);
        $productRepository = $this->getMockBuilder(ProductRepository::class)->disableOriginalConstructor()->getMock();
        $product = $this->getMock(Product::class);
        $data = [
            'data' => [
                'relationships' => [
                    'product' => ['data' => ['id' => 'product.1', 'type' => ProductUnit::class]],
                ]
            ]
        ];

        $context->expects($this->once())->method('getRequestData')->willReturn($data);
        $this
            ->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with($this->equalTo(Product::class))
            ->willReturn($productRepository);
        $productRepository
            ->expects($this->once())
            ->method('getProductsIdsBySku')
            ->with($this->equalTo(['product.1']))
            ->willReturn([1]);
        $this
            ->doctrineHelper
            ->expects($this->once())
            ->method('getEntity')
            ->with($this->equalTo(Product::class), $this->equalTo(1))
            ->willReturn($product);
        $context->expects($this->never())->method('setRequestData');

        $this->normalizeInventoryLevelRequestData->process($context);
    }

    public function testProcessPrimaryUnitOnRequestData()
    {
        /** @var FormContext|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock(FormContext::class);
        $productRepository = $this->getMockBuilder(ProductRepository::class)->disableOriginalConstructor()->getMock();
        $product = $this->getMock(Product::class);
        $unitPrecision = $this->getMock(ProductUnitPrecision::class);
        $data = [
            'data' => [
                'relationships' => [
                    'product' => ['data' => ['id' => 'product.1', 'type' => ProductUnit::class]],
                ]
            ]
        ];
        $requestData = [
            'data' => [
                'relationships' => [
                    'productUnitPrecision' => ['data' => ['type' => ProductUnitPrecision::class, 'id' => 10]],
                ]
            ]
        ];

        $context->expects($this->once())->method('getRequestData')->willReturn($data);
        $this
            ->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with($this->equalTo(Product::class))
            ->willReturn($productRepository);
        $productRepository
            ->expects($this->once())
            ->method('getProductsIdsBySku')
            ->with($this->equalTo(['product.1']))
            ->willReturn([1]);
        $this
            ->doctrineHelper
            ->expects($this->once())
            ->method('getEntity')
            ->with($this->equalTo(Product::class), $this->equalTo(1))
            ->willReturn($product);
        $product->expects($this->once())->method('getPrimaryUnitPrecision')->willReturn($unitPrecision);
        $unitPrecision->expects($this->once())->method('getId')->willReturn(10);

        $context->expects($this->once())->method('setRequestData')->with($requestData);

        $this->normalizeInventoryLevelRequestData->process($context);
    }

    public function testProcessUnitOnRequestData()
    {
        /** @var FormContext|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock(FormContext::class);
        $productRepository = $this->getMockBuilder(ProductRepository::class)->disableOriginalConstructor()->getMock();
        $unitRepository = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $unitPrecision = $this->getMock(ProductUnitPrecision::class);
        $data = [
            'data' => [
                'relationships' => [
                    'product' => ['data' => ['id' => 'product.1', 'type' => Product::class]],
                    'unit' => ['data' => ['id' => 'liter', 'type' => ProductUnit::class]],
                ]
            ]
        ];
        $requestData = [
            'data' => [
                'relationships' => [
                    'productUnitPrecision' => ['data' => ['type' => ProductUnitPrecision::class, 'id' => 10]],
                ]
            ]
        ];

        $context->expects($this->once())->method('getRequestData')->willReturn($data);
        $this
            ->doctrineHelper
            ->expects($this->at(0))
            ->method('getEntityRepository')
            ->with($this->equalTo(Product::class))
            ->willReturn($productRepository);
        $productRepository
            ->expects($this->once())
            ->method('getProductsIdsBySku')
            ->with($this->equalTo(['product.1']))
            ->willReturn([1]);
        $this
            ->doctrineHelper
            ->expects($this->at(1))
            ->method('getEntityRepository')
            ->with($this->equalTo(ProductUnitPrecision::class))
            ->willReturn($unitRepository);
        $unitRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(['product' => 1, 'unit' => 'liter']))
            ->willReturn($unitPrecision);
        $unitPrecision->expects($this->once())->method('getId')->willReturn(10);

        $context->expects($this->once())->method('setRequestData')->with($requestData);

        $this->normalizeInventoryLevelRequestData->process($context);
    }

    public function testProcessWarehouseOnRequestData()
    {
        /** @var FormContext|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock(FormContext::class);
        $productRepository = $this->getMockBuilder(ProductRepository::class)->disableOriginalConstructor()->getMock();
        $unitRepository = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $unitPrecision = $this->getMock(ProductUnitPrecision::class);
        $data = [
            'data' => [
                'relationships' => [
                    'product' => ['data' => ['id' => 'product.1', 'type' => Product::class]],
                    'unit' => ['data' => ['id' => 'liter', 'type' => ProductUnit::class]],
                ]
            ]
        ];

        $context->expects($this->once())->method('getRequestData')->willReturn($data);
        $this
            ->doctrineHelper
            ->expects($this->at(0))
            ->method('getEntityRepository')
            ->with($this->equalTo(Product::class))
            ->willReturn($productRepository);
        $productRepository
            ->expects($this->once())
            ->method('getProductsIdsBySku')
            ->with($this->equalTo(['product.1']))
            ->willReturn([1]);
        $this
            ->doctrineHelper
            ->expects($this->at(1))
            ->method('getEntityRepository')
            ->with($this->equalTo(ProductUnitPrecision::class))
            ->willReturn($unitRepository);
        $unitRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(['product' => 1, 'unit' => 'liter']))
            ->willReturn($unitPrecision);
        $unitPrecision->expects($this->once())->method('getId')->willReturn(10);

        $context->expects($this->once())->method('setRequestData')->with(
            $this->equalTo([
                'data' => [
                    'relationships' => [
                        'productUnitPrecision' => ['data' => ['id' => 10, 'type' => ProductUnitPrecision::class]],
                    ]
                ]
            ])
        );

        $this->normalizeInventoryLevelRequestData->process($context);
    }
}
