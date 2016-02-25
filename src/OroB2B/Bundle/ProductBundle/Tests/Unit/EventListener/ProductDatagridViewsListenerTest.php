<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\EventListener;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;

use OroB2B\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use OroB2B\Bundle\ProductBundle\EventListener\ProductDatagridViewsListener;

class ProductDatagridViewsListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ProductDatagridViewsListener
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

    public function setUp()
    {
        $this->themeHelper = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\DataGrid\DataGridThemeHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');

        $this->attachmentManager = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->disableOriginalConstructor()->getMock();

        $this->listener = new ProductDatagridViewsListener(
            $this->themeHelper,
            $this->doctrine,
            $this->attachmentManager
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
     * @return array
     */
    public function onPreBuildDataProvider()
    {
        return [
            [
                DataGridThemeHelper::VIEW_GRID,
                [
                    'name' => 'grid-name',
                    'options' => ['theme' => ['rowView' => DataGridThemeHelper::VIEW_GRID]],
                ]
            ],
            [
                DataGridThemeHelper::VIEW_LIST,
                [
                    'name' => 'grid-name',
                    'options' => ['theme' => ['rowView' => DataGridThemeHelper::VIEW_LIST]],
                    'source' => [
                        'query' => [
                            'select' => [
                                'productImage.filename as image',
                                'productDescriptions.string as description'
                            ],
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'product.image',
                                        'alias' => 'productImage'
                                    ]
                                ],
                                'inner' => [
                                    [
                                        'join' => 'product.descriptions',
                                        'alias' => 'productDescriptions',
                                        'conditionType' => 'WITH',
                                        'condition' => 'productDescriptions.locale IS NULL'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                DataGridThemeHelper::VIEW_TILES,
                [
                    'name' => 'grid-name',
                    'options' => ['theme' => ['rowView' => DataGridThemeHelper::VIEW_TILES]],
                    'source' => [
                        'query' => [
                            'select' => [
                                'productImage.filename as image',
                            ],
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'product.image',
                                        'alias' => 'productImage'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider onResultAfterDataProvider
     *
     * @param array $data
     * @param array $productWithImages
     * @param array $expectedData
     */
    public function testOnResultAfter(array $data, array $productWithImages, array $expectedData)
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

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();

        $this->doctrine->expects($this->once())
            ->method('getEntityManagerForClass')
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
                ->method('getAttachment')
                ->with(
                    'OroB2B\Bundle\ProductBundle\Entity\Product',
                    $productId,
                    'image',
                    $image
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
            ]
        ];
    }
}
