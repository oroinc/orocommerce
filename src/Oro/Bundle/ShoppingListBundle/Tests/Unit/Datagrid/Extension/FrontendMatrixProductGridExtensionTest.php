<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductListMatrixFormAvailabilityProvider;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\FrontendMatrixProductGridExtension;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderFormProvider;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Component\Testing\Unit\EntityTrait;

class FrontendMatrixProductGridExtensionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    /** @var ShoppingListManager|\PHPUnit_Framework_MockObject_MockObject */
    private $shoppingListManager;

    /** @var MatrixGridOrderFormProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $matrixGridOrderFormProvider;

    /** @var ProductListMatrixFormAvailabilityProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $productListMatrixFormAvailabilityProvider;

    /** @var ProductVariantAvailabilityProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $productVariantAvailabilityProvider;

    /** @var FrontendProductPricesProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $frontendProductPricesProvider;

    /** @var FrontendMatrixProductGridExtension */
    private $gridExtension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->shoppingListManager = $this->createMock(ShoppingListManager::class);
        $this->matrixGridOrderFormProvider = $this->createMock(MatrixGridOrderFormProvider::class);
        $this->productListMatrixFormAvailabilityProvider = $this->createMock(
            ProductListMatrixFormAvailabilityProvider::class
        );
        $this->productVariantAvailabilityProvider = $this->createMock(ProductVariantAvailabilityProvider::class);
        $this->frontendProductPricesProvider = $this->createMock(FrontendProductPricesProvider::class);

        $this->gridExtension = new FrontendMatrixProductGridExtension(
            $this->doctrineHelper,
            $this->shoppingListManager,
            $this->matrixGridOrderFormProvider,
            $this->productListMatrixFormAvailabilityProvider,
            $this->productVariantAvailabilityProvider,
            $this->frontendProductPricesProvider
        );
    }

    public function testGetPriority()
    {
        $this->assertSame(10, $this->gridExtension->getPriority());
    }

    public function testIsApplicable()
    {
        $config = DatagridConfiguration::create(
            [
                DatagridConfiguration::NAME_KEY => FrontendMatrixProductGridExtension::SUPPORTED_GRID
            ]
        );
        $this->assertTrue($this->gridExtension->isApplicable($config));
    }

    public function testIsNotApplicable()
    {
        $config = DatagridConfiguration::create(
            [
                DatagridConfiguration::NAME_KEY => 'frontend-product-grid'
            ]
        );
        $this->assertFalse($this->gridExtension->isApplicable($config));
    }

    public function testVisitResult()
    {
        $resultObject = ResultsObject::create([
            'data' => [
                new ResultRecord(['id' => 1]),
                new ResultRecord(['id' => 2]),
                new ResultRecord(['id' => 3]),
            ]
        ]);

        $product1 = $this->getEntity(Product::class, ['id' => 1, 'type' => Product::TYPE_CONFIGURABLE]);
        $product2 = $this->getEntity(Product::class, ['id' => 2, 'type' => Product::TYPE_SIMPLE]);
        $product3 = $this->getEntity(Product::class, ['id' => 3, 'type' => Product::TYPE_SIMPLE]);

        $simpleProduct = $this->getEntity(Product::class, ['id' => 4, 'type' => Product::TYPE_SIMPLE]);
        $simpleProduct2 = $this->getEntity(Product::class, ['id' => 5, 'type' => Product::TYPE_SIMPLE]);

        $products = [
            1 => $product1,
            2 => $product2,
            3 => $product3
        ];

        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);

        $repository = $this->createMock(EntityRepository::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(Product::class)
            ->willReturn($repository);

        $this->shoppingListManager->expects($this->once())
            ->method('getForCurrentUser')
            ->willReturn($shoppingList);

        $repository->expects($this->exactly(3))
            ->method('find')
            ->willReturnCallback(function ($productId) use ($products) {
                return $products[$productId];
            });

        $this->productListMatrixFormAvailabilityProvider->expects($this->exactly(3))
            ->method('isInlineMatrixFormAvailable')
            ->withConsecutive([$product1], [$product2], [$product3])
            ->willReturnOnConsecutiveCalls(true, false, false);

        $this->productVariantAvailabilityProvider->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product1)
            ->willReturn([$simpleProduct, $simpleProduct2]);

        $this->matrixGridOrderFormProvider->expects($this->once())
            ->method('getMatrixOrderFormHtml')
            ->with($product1, $shoppingList)
            ->willReturn('form html');

        $this->frontendProductPricesProvider->expects($this->once())
            ->method('getByProducts')
            ->with([$simpleProduct, $simpleProduct2])
            ->willReturn(['1' => ['unit' => 1]]);

        $this->gridExtension->visitResult(DatagridConfiguration::create([]), $resultObject);

        $expectedData = [
            '1' => [
                'matrixForm' => 'form html',
                'productPrices' => ['1' => ['unit' => 1]]
            ],
            '2' => [
                'matrixForm' => null,
                'productPrices' => null
            ],
            '3' => [
                'matrixForm' => null,
                'productPrices' => null
            ],
        ];

        foreach ($resultObject->getData() as $data) {
            $this->assertEquals($data->getValue('matrixForm'), $expectedData[$data->getValue('id')]['matrixForm']);
            $this->assertEquals(
                $data->getValue('productPrices'),
                $expectedData[$data->getValue('id')]['productPrices']
            );
        }
    }

    public function testVisitResultWhenCanGotProduct()
    {
        $resultObject = ResultsObject::create([
            'data' => [
                new ResultRecord(['id' => 1]),
            ]
        ]);

        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);
        $repository = $this->createMock(EntityRepository::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(Product::class)
            ->willReturn($repository);

        $this->shoppingListManager->expects($this->once())
            ->method('getForCurrentUser')
            ->willReturn($shoppingList);

        $repository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->productListMatrixFormAvailabilityProvider->expects($this->never())
            ->method('isInlineMatrixFormAvailable');

        $this->gridExtension->visitResult(DatagridConfiguration::create([]), $resultObject);

        $expectedData = [
            '1' => [
                'matrixForm' => null,
                'productPrices' => null
            ],
        ];

        foreach ($resultObject->getData() as $data) {
            $this->assertEquals($data->getValue('matrixForm'), $expectedData[$data->getValue('id')]['matrixForm']);
            $this->assertEquals(
                $data->getValue('productPrices'),
                $expectedData[$data->getValue('id')]['productPrices']
            );
        }
    }

    public function testVisitResultWhenAllSimpleProducts()
    {
        $resultObject = ResultsObject::create([
            'data' => [
                new ResultRecord(['id' => 1]),
                new ResultRecord(['id' => 2]),
                new ResultRecord(['id' => 3]),
            ]
        ]);

        $product1 = $this->getEntity(Product::class, ['id' => 1, 'type' => Product::TYPE_SIMPLE]);
        $product2 = $this->getEntity(Product::class, ['id' => 2, 'type' => Product::TYPE_SIMPLE]);
        $product3 = $this->getEntity(Product::class, ['id' => 3, 'type' => Product::TYPE_SIMPLE]);

        $products = [
            1 => $product1,
            2 => $product2,
            3 => $product3
        ];

        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);
        $repository = $this->createMock(EntityRepository::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(Product::class)
            ->willReturn($repository);

        $this->shoppingListManager->expects($this->once())
            ->method('getForCurrentUser')
            ->willReturn($shoppingList);

        $repository->expects($this->exactly(3))
            ->method('find')
            ->willReturnCallback(function ($productId) use ($products) {
                return $products[$productId];
            });

        $this->productListMatrixFormAvailabilityProvider->expects($this->exactly(3))
            ->method('isInlineMatrixFormAvailable')
            ->withConsecutive([$product1], [$product2], [$product3])
            ->willReturnOnConsecutiveCalls(false, false, false);

        $this->productVariantAvailabilityProvider->expects($this->never())
            ->method('getSimpleProductsByVariantFields');

        $this->matrixGridOrderFormProvider->expects($this->never())
            ->method('getMatrixOrderFormHtml');

        $this->frontendProductPricesProvider->expects($this->never())
            ->method('getByProducts');

        $this->gridExtension->visitResult(DatagridConfiguration::create([]), $resultObject);

        $expectedData = [
            '1' => [
                'matrixForm' => null,
                'productPrices' => null
            ],
            '2' => [
                'matrixForm' => null,
                'productPrices' => null
            ],
            '3' => [
                'matrixForm' => null,
                'productPrices' => null
            ],
        ];

        foreach ($resultObject->getData() as $data) {
            $this->assertEquals($data->getValue('matrixForm'), $expectedData[$data->getValue('id')]['matrixForm']);
            $this->assertEquals(
                $data->getValue('productPrices'),
                $expectedData[$data->getValue('id')]['productPrices']
            );
        }
    }
}
