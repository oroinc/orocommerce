<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\OrderLineItem;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\EventListener\OrderLineItem\OrderLineItemEventListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ConfigurableProductProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class OrderLineItemEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ConfigurableProductProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $configurableProductProvider;

    /** @var OrderLineItemEventListener */
    protected $listener;

    protected function setUp(): void
    {
        $this->configurableProductProvider = $this->createMock(ConfigurableProductProvider::class);

        $this->listener = new OrderLineItemEventListener($this->configurableProductProvider);
    }

    public function testMethodsWhenNoProductOrProductId()
    {
        $this->configurableProductProvider->expects($this->never())
            ->method($this->anything());

        $lineItem = new OrderLineItem();

        $this->listener->prePersist($lineItem, $this->getLifecycleEventArgs());
        $this->assertEquals([], $lineItem->getProductVariantFields());

        $this->listener->preUpdate($lineItem, $this->getPreUpdateEventArgs());
        $this->assertEquals([], $lineItem->getProductVariantFields());

        $lineItem->setProduct(new Product());

        $this->listener->prePersist($lineItem, $this->getLifecycleEventArgs());
        $this->assertEquals([], $lineItem->getProductVariantFields());

        $this->listener->preUpdate($lineItem, $this->getPreUpdateEventArgs());
        $this->assertEquals([], $lineItem->getProductVariantFields());
    }

    /**
     * @dataProvider methodsDataProvider
     *
     * @param array $returnResult
     * @param array $expectation
     */
    public function testMethods($returnResult, $expectation)
    {
        $this->configurableProductProvider->expects($this->any())
            ->method('getVariantFieldsValuesForLineItem')
            ->willReturn($returnResult);

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getEntity(
            OrderLineItem::class,
            ['product' => $this->getEntity(Product::class, ['id' => 42])]
        );

        $this->listener->prePersist($lineItem, $this->getLifecycleEventArgs());
        $this->assertEquals($expectation, $lineItem->getProductVariantFields());

        $this->listener->preUpdate($lineItem, $this->getPreUpdateEventArgs());
        $this->assertEquals($expectation, $lineItem->getProductVariantFields());
    }

    /**
     * @return array
     */
    public function methodsDataProvider()
    {
        return [
            'few fields' => [
                'returnResult' => [
                    42 => [
                        'field2' => [
                            'value' => 2,
                            'label' => 'test.label'
                        ],
                        'field1' => [
                            'value' => 'yes',
                            'label' => 'test.label'
                        ],
                    ],
                ],
                'expectation' => [
                    'field2' => [
                        'value' => 2,
                        'label' => 'test.label'
                    ],
                    'field1' => [
                        'value' => 'yes',
                        'label' => 'test.label'
                    ]
                ],
            ],
            'no fields' => [
                'returnResult' => [],
                'expectation' => [],
            ],
            'not product id match' => [
                'returnResult' => [
                    1 => [
                        'field2' => [
                            'value' => 2,
                            'label' => 'test.label'
                        ],
                        'field1' => [
                            'value' => 'yes',
                            'label' => 'test.label'
                        ],
                    ],
                ],
                'expectation' => [],
            ],
        ];
    }

    /**
     * @return LifecycleEventArgs|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getLifecycleEventArgs()
    {
        return $this->createMock(LifecycleEventArgs::class);
    }

    /**
     * @return PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getPreUpdateEventArgs()
    {
        return $this->createMock(PreUpdateEventArgs::class);
    }
}
