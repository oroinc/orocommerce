<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\OrderLineItem;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\EventListener\OrderLineItem\OrderLineItemEventListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ConfigurableProductProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class OrderLineItemEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ConfigurableProductProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configurableProductProvider;

    /** @var OrderLineItemEventListener */
    private $listener;

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

        $this->listener->prePersist($lineItem, $this->createMock(LifecycleEventArgs::class));
        $this->assertEquals([], $lineItem->getProductVariantFields());

        $this->listener->preUpdate($lineItem, $this->createMock(PreUpdateEventArgs::class));
        $this->assertEquals([], $lineItem->getProductVariantFields());

        $lineItem->setProduct(new Product());

        $this->listener->prePersist($lineItem, $this->createMock(LifecycleEventArgs::class));
        $this->assertEquals([], $lineItem->getProductVariantFields());

        $this->listener->preUpdate($lineItem, $this->createMock(PreUpdateEventArgs::class));
        $this->assertEquals([], $lineItem->getProductVariantFields());
    }

    /**
     * @dataProvider methodsDataProvider
     */
    public function testMethods(array $returnResult, array $expectation)
    {
        $this->configurableProductProvider->expects($this->any())
            ->method('getVariantFieldsValuesForLineItem')
            ->willReturn($returnResult);

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getEntity(
            OrderLineItem::class,
            ['product' => $this->getEntity(Product::class, ['id' => 42])]
        );

        $this->listener->prePersist($lineItem, $this->createMock(LifecycleEventArgs::class));
        $this->assertEquals($expectation, $lineItem->getProductVariantFields());

        $this->listener->preUpdate($lineItem, $this->createMock(PreUpdateEventArgs::class));
        $this->assertEquals($expectation, $lineItem->getProductVariantFields());
    }

    public function methodsDataProvider(): array
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
}
