<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\EventListener;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;

use OroB2B\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use OroB2B\Bundle\ProductBundle\EventListener\FrontendProductDatagridListener;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class FrontendProductDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var FrontendProductDatagridListener
     */
    protected $listener;

    /**
     * @var DataGridThemeHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $themeHelper;

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    /**
     * @var AttachmentManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attachmentManager;

    /**
     * @var ProductUnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $unitFormatter;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    public function setUp()
    {
        $this->themeHelper = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\DataGrid\DataGridThemeHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');

        $this->attachmentManager = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->disableOriginalConstructor()->getMock();

        $this->unitFormatter = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter')
            ->disableOriginalConstructor()->getMock();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans')
            ->with($this->isType('string'))
            ->willReturnCallback(
                function ($id, array $params = []) {
                    $id = str_replace(array_keys($params), array_values($params), $id);

                    return $id . '.trans';
                }
            );

        $this->listener = new FrontendProductDatagridListener(
            $this->themeHelper,
            $this->doctrine,
            $this->attachmentManager,
            $this->unitFormatter,
            $this->translator
        );
    }

    /**
     * @dataProvider onPreBuildDataProvider
     *
     * @param string $themeName
     * @param array $expectedConfig
     */
    public function testOnPreBuild($themeName, array $expectedConfig)
    {
        $gridName = 'grid-name';
        $this->themeHelper->expects($this->any())
            ->method('getTheme')
            ->willReturn($themeName);

        $config = DatagridConfiguration::createNamed($gridName, []);
        $params = new ParameterBag();
        $event = new PreBuild($config, $params);
        $this->listener->onPreBuild($event);
        $this->assertEquals($expectedConfig, $config->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function onPreBuildDataProvider()
    {
        return [
            'list' => [
                DataGridThemeHelper::VIEW_LIST,
                [
                    'name' => 'grid-name',
                    'source' => [
                        'query' => [
                            'select' => [
                                'GROUP_CONCAT(IDENTITY(unit_precisions.unit) SEPARATOR \'{sep}\') as product_units',
                            ],
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'product.unitPrecisions',
                                        'alias' => 'unit_precisions',
                                        'conditionType' => 'WITH',
                                        'condition' =>'unit_precisions.sell=true'
                                    ]
                                ],
                            ],
                        ],
                    ],
                    'properties' => [
                        'product_units' => [
                            'type' => 'field',
                            'frontend_type' => 'row_array',
                        ]
                    ],
                ],
            ],
            'grid' => [
                DataGridThemeHelper::VIEW_GRID,
                [
                    'name' => 'grid-name',
                    'source' => [
                        'query' => [
                            'select' => [
                                'GROUP_CONCAT(IDENTITY(unit_precisions.unit) SEPARATOR \'{sep}\') as product_units',
                                'productImage.filename as image',
                                'productShortDescriptions.text as shortDescription'
                            ],
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'product.unitPrecisions',
                                        'alias' => 'unit_precisions',
                                         'conditionType' => 'WITH',
                                         'condition' =>'unit_precisions.sell=true'
                                    ],
                                    [
                                        'join' => 'product.image',
                                        'alias' => 'productImage'
                                    ]
                                ],
                                'inner' => [
                                    [
                                        'join' => 'product.shortDescriptions',
                                        'alias' => 'productShortDescriptions',
                                        'conditionType' => 'WITH',
                                        'condition' => 'productShortDescriptions.locale IS NULL'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'properties' => [
                        'product_units' => [
                            'type' => 'field',
                            'frontend_type' => 'row_array',
                        ]
                    ],
                    'columns' => [
                        'image' => ['label' => 'orob2b.product.image.label.trans'],
                        'shortDescription' => ['label' => 'orob2b.product.short_descriptions.label.trans'],
                    ]
                ]
            ],
            'tiles' => [
                DataGridThemeHelper::VIEW_TILES,
                [
                    'name' => 'grid-name',
                    'source' => [
                        'query' => [
                            'select' => [
                                'GROUP_CONCAT(IDENTITY(unit_precisions.unit) SEPARATOR \'{sep}\') as product_units',
                                'productImage.filename as image',
                            ],
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'product.unitPrecisions',
                                        'alias' => 'unit_precisions',
                                        'conditionType' => 'WITH',
                                        'condition' =>'unit_precisions.sell=true'
                                    ],
                                    [
                                        'join' => 'product.image',
                                        'alias' => 'productImage'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'properties' => [
                        'product_units' => [
                            'type' => 'field',
                            'frontend_type' => 'row_array',
                        ]
                    ],
                    'columns' => [
                        'image' => ['label' => 'orob2b.product.image.label.trans'],
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider onResultAfterDataProvider
     *
     * @param string $themeName
     * @param array $data
     * @param array $productWithImages
     * @param array $expectedData
     */
    public function testOnResultAfter($themeName, array $data, array $productWithImages, array $expectedData)
    {
        $ids = [];
        $records = [];
        foreach ($data as $record) {
            $ids[] = $record['id'];
            $records[] = new ResultRecord($record);
        }
        /** @var OrmResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\OrmResultAfter')
            ->disableOriginalConstructor()->getMock();
        $event->expects($this->once())
            ->method('getRecords')
            ->willReturn($records);

        /** @var Datagrid|\PHPUnit_Framework_MockObject_MockObject $datagrid */
        $datagrid = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Datagrid')
            ->disableOriginalConstructor()->getMock();

        $gridName = 'grid-name';
        $datagrid->expects($this->once())
            ->method('getName')
            ->willReturn($gridName);

        $this->themeHelper->expects($this->any())
            ->method('getTheme')
            ->willReturn($themeName);

        $event->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($datagrid);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroB2BProductBundle:Product')
            ->willReturn($em);

        $repository = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository')
            ->disableOriginalConstructor()->getMock();

        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BProductBundle:Product')
            ->willReturn($repository);

        $products = [];
        foreach ($productWithImages as $index => $productId) {
            $product = $this->getMock('OroB2B\Bundle\ProductBundle\Entity\Product', ['getId', 'getImage']);
            $product->expects($this->any())
                ->method('getId')
                ->willReturn($productId);
            $image = $this->getMock('Oro\Bundle\AttachmentBundle\Entity\File');
            $product->expects($this->once())
                ->method('getImage')
                ->willReturn($image);
            $products[] = $product;

            $this->attachmentManager->expects($this->at($index))
                ->method('getFilteredImageUrl')
                ->with(
                    $image,
                    'product_large'
                )
                ->willReturn($productId);
        }

        $repository->expects($this->once())
            ->method('getProductsWithImage')
            ->with($ids)
            ->willReturn($products);

        $this->listener->onResultAfter($event);
        foreach ($expectedData as $expectedRecord) {
            $record = current($records);
            $this->assertEquals($expectedRecord['id'], $record->getValue('id'));
            $this->assertEquals($expectedRecord['image'], $record->getValue('image'));
            next($records);
        }
    }

    /**
     * @return array
     */
    public function onResultAfterDataProvider()
    {
        return [
            [
                'themeName' => DataGridThemeHelper::VIEW_TILES,
                'records' => [
                    ['id' => 1],
                    ['id' => 2],
                    ['id' => 3],
                ],
                'productWithImages' => [1, 3],
                'expectedData' => [
                    [
                        'id' => 1,
                        'image' => 1,
                    ],
                    [
                        'id' => 2,
                        'image' => null,
                    ],
                    [
                        'id' => 3,
                        'image' => 3,
                    ],
                ],
            ],
            [
                'themeName' => DataGridThemeHelper::VIEW_TILES,
                'records' => [
                    ['id' => 1],
                    ['id' => 2],
                    ['id' => 3],
                ],
                'productWithImages' => [1, 2, 3],
                'expectedData' => [
                    [
                        'id' => 1,
                        'image' => 1,
                    ],
                    [
                        'id' => 2,
                        'image' => 2,
                    ],
                    [
                        'id' => 3,
                        'image' => 3,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider onResultAfterViewWithImageDataProvider
     *
     * @param string $themeName
     */
    public function testOnResultAfterViewWithImage($themeName)
    {
        /** @var OrmResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\OrmResultAfter')
            ->disableOriginalConstructor()->getMock();
        $event->expects($this->once())
            ->method('getRecords')
            ->willReturn([]);

        /** @var Datagrid|\PHPUnit_Framework_MockObject_MockObject $datagrid */
        $datagrid = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Datagrid')
            ->disableOriginalConstructor()->getMock();

        $gridName = 'grid-name';
        $datagrid->expects($this->once())
            ->method('getName')
            ->willReturn($gridName);

        $this->themeHelper->expects($this->any())
            ->method('getTheme')
            ->willReturn($themeName);

        $event->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($datagrid);

        $this->doctrine->expects($this->never())
            ->method('getEntityManagerForClass');

        $this->listener->onResultAfter($event);
    }

    /**
     * @return array
     */
    public function onResultAfterViewWithImageDataProvider()
    {
        return [
            ['themeName' => DataGridThemeHelper::VIEW_LIST],
        ];
    }
}
