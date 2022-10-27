<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\EventListener\Datagrid;

use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\EventListener\Datagrid\ProductShippingOptionsDatagridListener;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductShippingOptionsDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const PRODUCT_SHIPPING_OPTIONS_CLASS = ProductShippingOptions::class;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var DatagridConfiguration */
    private $config;

    /** @var ProductShippingOptionsDatagridListener */
    private $listener;

    protected function setUp(): void
    {
        $this->config = DatagridConfiguration::create([]);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->listener = new ProductShippingOptionsDatagridListener($this->doctrineHelper);
    }

    public function testSetProductShippingOptionsClass()
    {
        $this->assertNull(ReflectionUtil::getPropertyValue($this->listener, 'productShippingOptionsClass'));

        $this->listener->setProductShippingOptionsClass('TestClass');
        $this->assertEquals(
            'TestClass',
            ReflectionUtil::getPropertyValue($this->listener, 'productShippingOptionsClass')
        );
    }

    public function testOnBuildBefore()
    {
        $datagrid = $this->createMock(DatagridInterface::class);

        $this->listener->setProductShippingOptionsClass(self::PRODUCT_SHIPPING_OPTIONS_CLASS);
        $this->listener->onBuildBefore(new BuildBefore($datagrid, $this->config));

        $this->assertEquals(
            [
                'columns' => [
                    'product_shipping_options' => [
                        'label' => 'oro.shipping.datagrid.shipping_options.column.label',
                        'type' => 'twig',
                        'template' => '@OroShipping/Datagrid/Column/productShippingOptions.html.twig',
                        'frontend_type' => 'html',
                        'renderable' => false,
                    ]
                ]
            ],
            $this->config->toArray()
        );
    }

    /**
     * @dataProvider onResultAfterDataProvider
     */
    public function testOnResultAfter(array $sourceResults = [], array $expectedResults = [])
    {
        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['product' => [42, 100]], ['productUnit' => 'ASC'])
            ->willReturn(
                [
                    $this->createShippingOptions(10, 42),
                    $this->createShippingOptions(11, 42),
                    $this->createShippingOptions(12, 42),
                    $this->createShippingOptions(13, 42),
                    $this->createShippingOptions(14, 42),
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(self::PRODUCT_SHIPPING_OPTIONS_CLASS)
            ->willReturn($repository);

        $datagrid = $this->createMock(DatagridInterface::class);

        $event = new OrmResultAfter($datagrid, $sourceResults);

        $this->listener->setProductShippingOptionsClass(self::PRODUCT_SHIPPING_OPTIONS_CLASS);
        $this->listener->onResultAfter($event);

        $this->assertEquals($expectedResults, $event->getRecords());
    }

    public function onResultAfterDataProvider(): array
    {
        return [
            [
                'sourceResults' => [
                    $this->getResultRecord([['id' => 42]]),
                    $this->getResultRecord([['id' => 100]])
                ],
                'expectedResults' => [
                    $this->getResultRecord([
                        [
                            'id' => 42
                        ],
                        [
                            ProductShippingOptionsDatagridListener::SHIPPING_OPTIONS_COLUMN => [
                                $this->createShippingOptions(10, 42),
                                $this->createShippingOptions(11, 42),
                                $this->createShippingOptions(12, 42),
                                $this->createShippingOptions(13, 42),
                                $this->createShippingOptions(14, 42)
                            ]
                        ]
                    ]),
                    $this->getResultRecord([
                        [
                            'id' => 100
                        ],
                        [
                            ProductShippingOptionsDatagridListener::SHIPPING_OPTIONS_COLUMN => []
                        ]
                    ])
                ],
            ],
        ];
    }

    private function createShippingOptions(int $id, int $productId): ProductShippingOptions
    {
        return $this->getEntity(
            ProductShippingOptions::class,
            [
                'id' => $id,
                'product' => $this->getEntity(Product::class, ['id' => $productId])
            ]
        );
    }

    private function getResultRecord(array $data = []): ResultRecord
    {
        $resultRecord = new ResultRecord([]);
        foreach ($data as $value) {
            $resultRecord->addData($value);
        }

        return $resultRecord;
    }
}
