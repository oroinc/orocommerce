<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\ProductGridEventListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductGridEventListenerTest extends TestCase
{
    use EntityTrait;

    private ProductRepository|MockObject $repository;

    private SelectedFieldsProviderInterface|MockObject $selectedFieldsProvider;

    private ProductGridEventListener $listener;

    private ConfigManager|MockObject $configManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProductRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($this->repository);

        $this->selectedFieldsProvider = $this->createMock(SelectedFieldsProviderInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->listener = new ProductGridEventListener($doctrine, $this->selectedFieldsProvider);
        $this->listener->setConfigManager($this->configManager);
    }

    public function testOnResultAfterWithoutSelectedField(): void
    {
        $config = DatagridConfiguration::create([]);
        $params = new ParameterBag();

        $this->selectedFieldsProvider->expects($this->once())
            ->method('getSelectedFields')
            ->with($config, $params)
            ->willReturn([]);

        $this->repository->expects($this->never())
            ->method($this->anything());

        $event = new OrmResultAfter(
            new Datagrid('test-product-grid', $config, $params),
            [
                new ResultRecord(['id' => 1001]),
                new ResultRecord(['id' => 2002]),
            ]
        );

        $this->listener->onResultAfter($event);

        $this->assertEquals(
            [
                new ResultRecord(['id' => 1001]),
                new ResultRecord(['id' => 2002]),
            ],
            $event->getRecords()
        );
    }

    public function testOnResultAfter(): void
    {
        $config = DatagridConfiguration::create([]);
        $params = new ParameterBag();

        $this->selectedFieldsProvider->expects($this->once())
            ->method('getSelectedFields')
            ->with($config, $params)
            ->willReturn(['productImage']);

        $this->repository->expects($this->once())
            ->method('getListingAndMainImagesFilesByProductIds')
            ->with([1001, 2002, 3003, 4004])
            ->willReturn(
                [
                    1001 => [],
                    2002 => [
                        ProductImageType::TYPE_LISTING => $this->getEntity(ProductImage::class, ['id' => 22]),
                    ],
                    3003 => [
                        ProductImageType::TYPE_MAIN => $this->getEntity(ProductImage::class, ['id' => 33]),
                    ],
                    4004 => [
                        ProductImageType::TYPE_LISTING => $this->getEntity(ProductImage::class, ['id' => 44]),
                        ProductImageType::TYPE_MAIN => $this->getEntity(ProductImage::class, ['id' => 45]),
                    ],
                ]
            );

        $event = new OrmResultAfter(
            new Datagrid('test-product-grid', $config, $params),
            [
                new ResultRecord(['id' => 1001]),
                new ResultRecord(['id' => 2002]),
                new ResultRecord(['id' => 3003]),
                new ResultRecord(['id' => 4004]),
            ]
        );

        $this->listener->onResultAfter($event);

        $this->assertEquals(
            [
                new ResultRecord(['id' => 1001]),
                new ResultRecord(
                    [
                        'id' => 2002,
                        'productImageListing' => $this->getEntity(ProductImage::class, ['id' => 22])
                    ]
                ),
                new ResultRecord(
                    [
                        'id' => 3003,
                        'productImageMain' => $this->getEntity(ProductImage::class, ['id' => 33])
                    ]
                ),
                new ResultRecord(
                    [
                        'id' => 4004,
                        'productImageListing' => $this->getEntity(ProductImage::class, ['id' => 44]),
                        'productImageMain' => $this->getEntity(ProductImage::class, ['id' => 45])
                    ]
                ),
            ],
            $event->getRecords()
        );
    }

    /** @dataProvider onBuildAfterDataProvider */
    public function testOnBuildAfter(string $columnName, array $fieldConfiguration, bool $expected): void
    {
        $entityClassName = Product::class;
        $config = DatagridConfiguration::create([
            DatagridConfiguration::EXTENDED_ENTITY_NAME => $entityClassName,
            'filters' => [
                'columns' => [
                    $columnName => [],
                ]
            ]
        ]);
        $params = new ParameterBag();
        $event = new BuildAfter(new Datagrid('test-product-grid', $config, $params));

        $fieldConfigModel = new FieldConfigModel($entityClassName, $columnName);
        $fieldConfigModel->fromArray('datagrid', $fieldConfiguration);

        $this->configManager
            ->expects(self::once())
            ->method('getConfigFieldModel')
            ->with($entityClassName, $columnName)
            ->willReturn($fieldConfigModel);

        $this->listener->onBuildAfter($event);

        self::assertEquals(
            $expected,
            array_key_exists($columnName, $event->getDatagrid()->getConfig()->offsetGetByPath('[filters][columns]'))
        );
    }

    public function onBuildAfterDataProvider(): array
    {
        return [
            [
                'columnName' => 'id',
                'fieldConfiguration' => [],
                'expectedResult' => true
            ],
            [
                'columnName' => 'testField1',
                'fieldConfiguration' => ['show_filter' => true],
                'expectedResult' => true
            ],
            [
                'columnName' => 'testField2',
                'fieldConfiguration' => ['show_filter' => false],
                'expectedResult' => false
            ],
        ];
    }
}
