<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\PromotionBundle\EventListener\ShippingMethodsListener;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\ShippingBundle\Event\ApplicableMethodsEvent;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

class ShippingMethodsListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var PromotionExecutor|\PHPUnit\Framework\MockObject\MockObject */
    private $promotionExecutor;

    /** @var ShippingMethodsListener */
    private $shippingMethodsListener;

    #[\Override]
    protected function setUp(): void
    {
        $this->promotionExecutor = $this->createMock(PromotionExecutor::class);

        $this->shippingMethodsListener = new ShippingMethodsListener($this->promotionExecutor);
    }

    private function getDiscountContext(?float $shippingAmount = null): DiscountContext
    {
        $context = new DiscountContext();
        if (null !== $shippingAmount) {
            $context->addShippingDiscountInformation(new DiscountInformation(new ShippingDiscount(), $shippingAmount));
        }

        return $context;
    }

    private function getShippingMethodViewCollection(array $data): ShippingMethodViewCollection
    {
        $collection = new ShippingMethodViewCollection();
        foreach ($data['methodViews'] as $methodId => $methodView) {
            $collection->addMethodView($methodId, $methodView);
        }
        foreach ($data['methodTypesViews'] as $methodId => $methodTypes) {
            foreach ($methodTypes as $methodTypeId => $methodTypeView) {
                $collection->addMethodTypeView($methodId, $methodTypeId, $methodTypeView);
            }
        }

        return $collection;
    }

    public function testModifyPricesNotSupportedSourceEntity(): void
    {
        $shippingMethodViewCollection = new ShippingMethodViewCollection();
        $sourceEntity = new \stdClass();

        $event = new ApplicableMethodsEvent($shippingMethodViewCollection, $sourceEntity);

        $this->shippingMethodsListener->modifyPrices($event);
        $this->assertEquals($shippingMethodViewCollection, $event->getMethodCollection());
    }

    public function testModifyPricesCheckoutFromNotSupportedSource(): void
    {
        $shippingMethodViewCollection = new ShippingMethodViewCollection();
        $sourceEntity = $this->createMock(Checkout::class);
        $sourceEntity->expects($this->any())
            ->method('getSourceEntity')
            ->willReturn($this->createMock(CheckoutSourceEntityInterface::class));

        $event = new ApplicableMethodsEvent($shippingMethodViewCollection, $sourceEntity);

        $this->shippingMethodsListener->modifyPrices($event);
        $this->assertEquals($shippingMethodViewCollection, $event->getMethodCollection());
    }

    /**
     * @dataProvider modifyPricesDataProvider
     */
    public function testModifyPrices(
        ShippingMethodViewCollection $shippingMethodViewCollection,
        array $discountContexts,
        ShippingMethodViewCollection $modifiedShippingMethodViewCollection
    ): void {
        $sourceEntity = $this->createMock(Checkout::class);
        $sourceEntity->expects($this->any())
            ->method('getSourceEntity')
            ->willReturn(new ShoppingList());

        $event = new ApplicableMethodsEvent($shippingMethodViewCollection, $sourceEntity);

        $this->promotionExecutor->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive([$sourceEntity], [$sourceEntity], [$sourceEntity])
            ->willReturnOnConsecutiveCalls(
                $discountContexts['Discount50USD'],
                $discountContexts['Discount100USD'],
                $discountContexts['WithoutShippingDiscount']
            );

        $this->shippingMethodsListener->modifyPrices($event);
        $this->assertEquals($modifiedShippingMethodViewCollection, $event->getMethodCollection());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function modifyPricesDataProvider(): array
    {
        return [
            [
                'shippingMethodViewCollection' => $this->getShippingMethodViewCollection([
                    'methodViews' => [
                        'flat_rate_3' => [
                            'identifier' => 'flat_rate_3',
                            'isGrouped' => false,
                            'label' => 'Flat Rate',
                            'sortOrder' => 10
                        ],
                        'ups_4' => [
                            'identifier' => 'ups_4',
                            'isGrouped' => true,
                            'label' => 'UPS shipping',
                            'sortOrder' => 20
                        ]
                    ],
                    'methodTypesViews' => [
                        'flat_rate_3' => [
                            'primary' => [
                                'identifier' => 'primary',
                                'label' => 'Flat Rate',
                                'sortOrder' => 0,
                                'price' => Price::create(100, 'USD')
                            ]
                        ],
                        'ups_4' => [
                            '02' => [
                                'identifier' => '02',
                                'label' => 'UPS 2nd Day Air',
                                'sortOrder' => 0,
                                'price' => Price::create(200, 'USD')
                            ],
                            '59' => [
                                'identifier' => '59',
                                'label' => 'UPS 2nd Day Air A.M.',
                                'sortOrder' => 0,
                                'price' => Price::create(300, 'USD')
                            ]
                        ]
                    ]
                ]),
                'discountContexts' => [
                    'Discount50USD' => $this->getDiscountContext(50.0),
                    'Discount100USD' => $this->getDiscountContext(100.0),
                    'WithoutShippingDiscount' => $this->getDiscountContext()
                ],
                'modifiedShippingMethodViewCollection' => $this->getShippingMethodViewCollection([
                    'methodViews' => [
                        'flat_rate_3' => [
                            'identifier' => 'flat_rate_3',
                            'isGrouped' => false,
                            'label' => 'Flat Rate',
                            'sortOrder' => 10
                        ],
                        'ups_4' => [
                            'identifier' => 'ups_4',
                            'isGrouped' => true,
                            'label' => 'UPS shipping',
                            'sortOrder' => 20
                        ]
                    ],
                    'methodTypesViews' => [
                        'flat_rate_3' => [
                            'primary' => [
                                'identifier' => 'primary',
                                'label' => 'Flat Rate',
                                'sortOrder' => 0,
                                'price' => Price::create(50, 'USD')
                            ]
                        ],
                        'ups_4' => [
                            '02' => [
                                'identifier' => '02',
                                'label' => 'UPS 2nd Day Air',
                                'sortOrder' => 0,
                                'price' => Price::create(100, 'USD')
                            ],
                            '59' => [
                                'identifier' => '59',
                                'label' => 'UPS 2nd Day Air A.M.',
                                'sortOrder' => 0,
                                'price' => Price::create(300, 'USD')
                            ]
                        ]
                    ]
                ])
            ]
        ];
    }
}
