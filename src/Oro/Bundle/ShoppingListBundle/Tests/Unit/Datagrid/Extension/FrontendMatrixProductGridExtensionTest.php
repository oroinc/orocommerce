<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Provider\SystemAwareResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\FrontendMatrixProductGridExtension;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderFormProvider;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderProvider;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Component\Testing\Unit\EntityTrait;

class FrontendMatrixProductGridExtensionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var CurrentShoppingListManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currentShoppingListManager;

    /** @var MatrixGridOrderFormProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $matrixGridOrderFormProvider;

    /** @var ProductFormAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productFormAvailabilityProvider;

    /** @var FrontendProductPricesProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $frontendProductPricesProvider;

    /** @var FrontendMatrixProductGridExtension */
    private $gridExtension;

    /** @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridConfiguration;

    /** @var MatrixGridOrderProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $matrixGridOrderProvider;

    /** @var DataGridThemeHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $dataGridThemeHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->currentShoppingListManager = $this->createMock(CurrentShoppingListManager::class);
        $this->matrixGridOrderFormProvider = $this->createMock(MatrixGridOrderFormProvider::class);
        $this->productFormAvailabilityProvider = $this->createMock(
            ProductFormAvailabilityProvider::class
        );
        $this->frontendProductPricesProvider = $this->createMock(FrontendProductPricesProvider::class);
        $this->datagridConfiguration = $this->createMock(DatagridConfiguration::class);
        $this->matrixGridOrderProvider = $this->createMock(MatrixGridOrderProvider::class);
        $this->dataGridThemeHelper = $this->createMock(DataGridThemeHelper::class);

        $this->dataGridThemeHelper->expects($this->any())
            ->method('getTheme')
            ->willReturn('list-view');

        $this->gridExtension = new FrontendMatrixProductGridExtension(
            $this->doctrineHelper,
            $this->currentShoppingListManager,
            $this->matrixGridOrderFormProvider,
            $this->productFormAvailabilityProvider,
            $this->frontendProductPricesProvider,
            $this->matrixGridOrderProvider,
            $this->dataGridThemeHelper
        );
        $this->gridExtension->setParameters(new ParameterBag());
    }

    public function testGetPriority()
    {
        $this->assertSame(10, $this->gridExtension->getPriority());
    }

    public function testIsApplicable()
    {
        $this->assertFalse($this->gridExtension->isApplicable(DatagridConfiguration::create([
            DatagridConfiguration::NAME_KEY => 'some-unsupported-grid-name'
        ])));

        $this->assertTrue($this->gridExtension->isApplicable(DatagridConfiguration::create([
            DatagridConfiguration::NAME_KEY => FrontendMatrixProductGridExtension::SUPPORTED_GRID
        ])));

        $this->assertFalse($this->gridExtension->isApplicable(DatagridConfiguration::create([
            DatagridConfiguration::NAME_KEY => 'some-unsupported-grid-name',
            SystemAwareResolver::KEY_EXTENDED_FROM => ['some-other-unsupported-datagrid']
        ])));

        $this->assertTrue($this->gridExtension->isApplicable(DatagridConfiguration::create([
            DatagridConfiguration::NAME_KEY => 'some-unsupported-grid-name',
            SystemAwareResolver::KEY_EXTENDED_FROM => [
                'some-other-unsupported-datagrid',
                FrontendMatrixProductGridExtension::SUPPORTED_GRID
            ]
        ])));
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

        $this->currentShoppingListManager->expects($this->once())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $repository->expects($this->exactly(3))
            ->method('find')
            ->willReturnCallback(function ($productId) use ($products) {
                return $products[$productId];
            });

        $this->productFormAvailabilityProvider->expects($this->exactly(1))
            ->method('getAvailableMatrixFormType')
            ->withConsecutive([$product1], [$product2], [$product3])
            ->willReturnOnConsecutiveCalls(
                Configuration::MATRIX_FORM_INLINE,
                Configuration::MATRIX_FORM_NONE,
                Configuration::MATRIX_FORM_NONE
            );

        $this->productFormAvailabilityProvider->expects($this->exactly(3))
            ->method('isMatrixFormAvailable')
            ->withConsecutive([$product1], [$product2], [$product3])
            ->willReturnOnConsecutiveCalls(true, false, false);

        $this->matrixGridOrderProvider->expects($this->once())
            ->method('getTotalQuantity')
            ->with($product1)
            ->willReturn(5);

        $this->matrixGridOrderProvider->expects($this->once())
            ->method('getTotalPriceFormatted')
            ->with($product1)
            ->willReturn('$12.34');

        $this->matrixGridOrderFormProvider->expects($this->once())
            ->method('getMatrixOrderFormHtml')
            ->with($product1, $shoppingList)
            ->willReturn('form html');

        $this->frontendProductPricesProvider->expects($this->once())
            ->method('getVariantsPricesByProduct')
            ->with($product1)
            ->willReturn([
                '1' => ['unit' => 1],
            ]);

        $this->datagridConfiguration->expects($this->exactly(2))
            ->method('offsetAddToArrayByPath');

        $this->gridExtension->visitResult($this->datagridConfiguration, $resultObject);

        $expectedData = $this->getVisitResultExpectedData();

        foreach ($resultObject->getData() as $data) {
            $this->assertEquals($data->getValue('matrixForm'), $expectedData[$data->getValue('id')]['matrixForm']);
            $this->assertEquals($data->getValue('prices'), $expectedData[$data->getValue('id')]['prices']);
        }
    }

    /**
     * @return array
     */
    private function getVisitResultExpectedData()
    {
        return [
            '1' => [
                'matrixForm' => [
                    'type' => 'inline',
                    'form' => 'form html',
                    'totals' => [
                        'quantity' => 5,
                        'price' => '$12.34',
                    ],
                    'rows' => [0, 0],
                ],
                'prices' => ['1' => ['unit' => 1]],
            ],
            '2' => [
                'matrixForm' => [
                    'type' => 'none',
                ],
                'prices' => null,
            ],
            '3' => [
                'matrixForm' => [
                    'type' => 'none',
                ],
                'prices' => null,
            ],
        ];
    }

    public function testVisitResultWhenCanNotGetProduct()
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

        $this->currentShoppingListManager->expects($this->once())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $repository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->productFormAvailabilityProvider->expects($this->never())
            ->method('getAvailableMatrixFormType');

        $this->datagridConfiguration->expects($this->exactly(2))
            ->method('offsetAddToArrayByPath');

        $this->gridExtension->visitResult($this->datagridConfiguration, $resultObject);

        $expectedData = [
            '1' => [
                'matrixForm' => [
                    'type' => 'none',
                ],
                'prices' => null
            ],
        ];

        foreach ($resultObject->getData() as $data) {
            $this->assertEquals(
                $data->getValue('matrixForm'),
                $expectedData[$data->getValue('id')]['matrixForm']
            );
            $this->assertEquals(
                $data->getValue('prices'),
                $expectedData[$data->getValue('id')]['prices']
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

        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);
        $repository = $this->createMock(EntityRepository::class);

        $products = [
            1 => $product1,
            2 => $product2,
            3 => $product3
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(Product::class)
            ->willReturn($repository);

        $this->currentShoppingListManager->expects($this->once())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $repository->expects($this->exactly(3))
            ->method('find')
            ->willReturnCallback(function ($productId) use ($products) {
                return $products[$productId];
            });

        $this->productFormAvailabilityProvider->expects($this->exactly(3))
            ->method('isMatrixFormAvailable')
            ->withConsecutive([$product1], [$product2], [$product3])
            ->willReturnOnConsecutiveCalls(false, false, false);

        $this->matrixGridOrderFormProvider->expects($this->never())
            ->method('getMatrixOrderFormHtml');

        $this->frontendProductPricesProvider->expects($this->never())
            ->method('getByProducts');

        $this->datagridConfiguration->expects($this->exactly(2))
            ->method('offsetAddToArrayByPath');

        $this->gridExtension->visitResult($this->datagridConfiguration, $resultObject);

        $expectedData = [
            '1' => [
                'matrixForm' => [
                    'type' => 'none',
                ],
                'prices' => null
            ],
            '2' => [
                'matrixForm' => [
                    'type' => 'none',
                ],
                'prices' => null
            ],
            '3' => [
                'matrixForm' => [
                    'type' => 'none',
                ],
                'prices' => null
            ],
        ];

        foreach ($resultObject->getData() as $data) {
            $this->assertEquals(
                $data->getValue('matrixForm'),
                $expectedData[$data->getValue('id')]['matrixForm']
            );
            $this->assertEquals(
                $data->getValue('prices'),
                $expectedData[$data->getValue('id')]['prices']
            );
        }
    }
}
