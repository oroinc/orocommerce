<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\EventListener;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductFormAvailabilityProvider;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\ShoppingListBundle\Datagrid\EventListener\FrontendMatrixProductGridEventListener;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderFormProvider;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderProvider;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Component\Testing\Unit\EntityTrait;

class FrontendMatrixProductGridEventListenerTest extends \PHPUnit\Framework\TestCase
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

    /** @var MatrixGridOrderProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $matrixGridOrderProvider;

    /** @var DataGridThemeHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $dataGridThemeHelper;

    /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $datagrid;

    /** @var FrontendMatrixProductGridEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->currentShoppingListManager = $this->createMock(CurrentShoppingListManager::class);
        $this->matrixGridOrderFormProvider = $this->createMock(MatrixGridOrderFormProvider::class);
        $this->productFormAvailabilityProvider = $this->createMock(ProductFormAvailabilityProvider::class);
        $this->frontendProductPricesProvider = $this->createMock(FrontendProductPricesProvider::class);
        $this->matrixGridOrderProvider = $this->createMock(MatrixGridOrderProvider::class);
        $this->dataGridThemeHelper = $this->createMock(DataGridThemeHelper::class);
        $this->datagrid = $this->createMock(DatagridInterface::class);

        $this->listener = new FrontendMatrixProductGridEventListener(
            $this->doctrineHelper,
            $this->currentShoppingListManager,
            $this->matrixGridOrderFormProvider,
            $this->productFormAvailabilityProvider,
            $this->frontendProductPricesProvider,
            $this->matrixGridOrderProvider,
            $this->dataGridThemeHelper
        );
    }

    public function testOnPreBuild(): void
    {
        $config = DatagridConfiguration::createNamed('grid-name', []);
        $event = new PreBuild($config, new ParameterBag());

        $this->listener->onPreBuild($event);

        $this->assertEquals(
            [
                'name'       => 'grid-name',
                'properties' => [
                    'matrixForm' => [
                        'type'          => 'field',
                        'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY
                    ],
                    'prices'     => [
                        'type'          => 'field',
                        'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY
                    ],
                ],
            ],
            $config->toArray()
        );
    }

    public function testOnResultAfter(): void
    {
        $datagridName = 'some-grid-name';
        $datagridTheme = 'some-grid-theme';

        $records = [
            new ResultRecord([
                'id'                   => 1,
                'type'                 => Product::TYPE_CONFIGURABLE,
                'unit'                 => 'each',
                'variant_fields_count' => 3
            ]),
            new ResultRecord(['id' => 2, 'type' => Product::TYPE_SIMPLE, 'unit' => 'each']),
            new ResultRecord(['id' => 3, 'type' => Product::TYPE_SIMPLE, 'unit' => 'each'])
        ];

        $product1 = $this->getEntity(Product::class, ['id' => 1, 'type' => Product::TYPE_CONFIGURABLE]);

        $configurableProductData = [
            1 => ['each', 3]
        ];
        $matrixFormTypes = [
            1 => Configuration::MATRIX_FORM_INLINE
        ];

        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);

        $this->datagrid->expects(self::any())
            ->method('getName')
            ->willReturn($datagridName);
        $this->dataGridThemeHelper->expects(self::any())
            ->method('getTheme')
            ->with($datagridName)
            ->willReturn($datagridTheme);

        $repository = $this->createMock(EntityRepository::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with(Product::class)
            ->willReturn($repository);

        $this->currentShoppingListManager->expects(self::once())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $repository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($product1);

        $this->productFormAvailabilityProvider->expects(self::once())
            ->method('getAvailableMatrixFormTypes')
            ->with($configurableProductData, $datagridTheme)
            ->willReturn($matrixFormTypes);

        $this->matrixGridOrderProvider->expects(self::once())
            ->method('getTotalQuantity')
            ->with($product1)
            ->willReturn(5);

        $this->matrixGridOrderProvider->expects(self::once())
            ->method('getTotalPriceFormatted')
            ->with($product1)
            ->willReturn('$12.34');

        $this->matrixGridOrderFormProvider->expects(self::once())
            ->method('getMatrixOrderFormHtml')
            ->with($product1, $shoppingList)
            ->willReturn('form html');

        $this->frontendProductPricesProvider->expects(self::once())
            ->method('getVariantsPricesByProduct')
            ->with($product1)
            ->willReturn([
                '1' => ['unit' => 1],
            ]);

        $event = new SearchResultAfter(
            $this->datagrid,
            $this->createMock(SearchQueryInterface::class),
            $records
        );
        $this->listener->onResultAfter($event);

        $expectedData = $this->getOnResultAfterExpectedData();

        foreach ($records as $record) {
            self::assertEquals($record->getValue('matrixForm'), $expectedData[$record->getValue('id')]['matrixForm']);
            self::assertEquals($record->getValue('prices'), $expectedData[$record->getValue('id')]['prices']);
        }
    }

    private function getOnResultAfterExpectedData(): array
    {
        return [
            '1' => [
                'matrixForm' => [
                    'type'   => 'inline',
                    'form'   => 'form html',
                    'totals' => [
                        'quantity' => 5,
                        'price'    => '$12.34',
                    ],
                    'rows'   => [0, 0],
                ],
                'prices'     => ['1' => ['unit' => 1]],
            ],
            '2' => [
                'matrixForm' => [
                    'type' => 'none',
                ],
                'prices'     => null,
            ],
            '3' => [
                'matrixForm' => [
                    'type' => 'none',
                ],
                'prices'     => null,
            ],
        ];
    }

    public function testOnResultAfterWhenCanNotGetProduct(): void
    {
        $records = [
            new ResultRecord(['id' => 1])
        ];

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityRepositoryForClass');

        $this->currentShoppingListManager->expects(self::never())
            ->method('getCurrent');

        $event = new SearchResultAfter(
            $this->datagrid,
            $this->createMock(SearchQueryInterface::class),
            $records
        );
        $this->listener->onResultAfter($event);

        $expectedData = [
            '1' => [
                'matrixForm' => [
                    'type' => 'none',
                ],
                'prices'     => null
            ],
        ];

        foreach ($records as $record) {
            self::assertEquals($record->getValue('matrixForm'), $expectedData[$record->getValue('id')]['matrixForm']);
            self::assertEquals($record->getValue('prices'), $expectedData[$record->getValue('id')]['prices']);
        }
    }

    public function testOnResultAfterWhenAllSimpleProducts(): void
    {
        $records = [
            new ResultRecord(['id' => 1]),
            new ResultRecord(['id' => 2]),
            new ResultRecord(['id' => 3])
        ];

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityRepositoryForClass');

        $this->currentShoppingListManager->expects(self::never())
            ->method('getCurrent');

        $this->matrixGridOrderFormProvider->expects(self::never())
            ->method('getMatrixOrderFormHtml');

        $this->frontendProductPricesProvider->expects(self::never())
            ->method('getByProducts');

        $event = new SearchResultAfter(
            $this->datagrid,
            $this->createMock(SearchQueryInterface::class),
            $records
        );
        $this->listener->onResultAfter($event);

        $expectedData = [
            '1' => [
                'matrixForm' => [
                    'type' => 'none',
                ],
                'prices'     => null
            ],
            '2' => [
                'matrixForm' => [
                    'type' => 'none',
                ],
                'prices'     => null
            ],
            '3' => [
                'matrixForm' => [
                    'type' => 'none',
                ],
                'prices'     => null
            ],
        ];

        foreach ($records as $record) {
            self::assertEquals($record->getValue('matrixForm'), $expectedData[$record->getValue('id')]['matrixForm']);
            self::assertEquals($record->getValue('prices'), $expectedData[$record->getValue('id')]['prices']);
        }
    }
}
