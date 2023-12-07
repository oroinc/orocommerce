<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Context\OrderContextDataConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Model\MultiShippingPromotionData;
use Oro\Bundle\PromotionBundle\RuleFiltration\MultiShippingFiltrationService;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethodProvider;

class MultiShippingFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    private const MULTI_SHIPPING = MultiShippingMethodProvider::MULTI_SHIPPING_METHOD_IDENTIFIER;

    /** @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $baseFiltrationService;

    /** @var MultiShippingFiltrationService */
    private $filtrationService;

    protected function setUp(): void
    {
        $this->baseFiltrationService = $this->createMock(RuleFiltrationServiceInterface::class);

        $this->filtrationService = new MultiShippingFiltrationService($this->baseFiltrationService);
    }

    private function getRuleOwner(int $id, ?string $type = null): PromotionDataInterface
    {
        $discountConfiguration = new DiscountConfiguration();
        if (null !== $type) {
            $discountConfiguration->setType($type);
        }

        $ruleOwner = $this->createMock(PromotionDataInterface::class);
        $ruleOwner->expects(self::any())
            ->method('getId')
            ->willReturn($id);
        $ruleOwner->expects(self::any())
            ->method('getDiscountConfiguration')
            ->willReturn($discountConfiguration);

        return $ruleOwner;
    }

    private function getLineItem(object $sourceLineItem, ?float $subtotal = null): DiscountLineItem
    {
        $lineItem = new DiscountLineItem();
        $lineItem->setSourceLineItem($sourceLineItem);
        if (null !== $subtotal) {
            $lineItem->setSubtotal($subtotal);
        }

        return $lineItem;
    }

    private function getOrderLineItem(?string $shippingMethod, ?float $shippingAmount = null): OrderLineItem
    {
        $lineItem = new OrderLineItem();
        if (null !== $shippingMethod) {
            $lineItem->setShippingMethod($shippingMethod);
        }
        if (null !== $shippingAmount) {
            $lineItem->setShippingEstimateAmount($shippingAmount);
        }
        $lineItem->setCurrency('USD');

        return $lineItem;
    }

    public function testShouldBeSkippable(): void
    {
        $ruleOwners = [$this->getRuleOwner(1)];

        $this->baseFiltrationService->expects(self::never())
            ->method('getFilteredRuleOwners');

        self::assertSame(
            $ruleOwners,
            $this->filtrationService->getFilteredRuleOwners(
                $ruleOwners,
                ['skip_filters' => [MultiShippingFiltrationService::class => true]]
            )
        );
    }

    public function testGetFilteredRuleOwnersWhenNoShippingMethod(): void
    {
        $ruleOwners = [$this->getRuleOwner(1)];
        $context = [
            ContextDataConverterInterface::LINE_ITEMS => [$this->createMock(DiscountLineItem::class)]
        ];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with($ruleOwners, $context)
            ->willReturn($ruleOwners);

        self::assertSame($ruleOwners, $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context));
    }

    public function testGetFilteredRuleOwnersForNotMultiShipping(): void
    {
        $ruleOwners = [$this->getRuleOwner(1)];
        $context = [
            ContextDataConverterInterface::SHIPPING_METHOD => 'test',
            ContextDataConverterInterface::LINE_ITEMS      => [$this->createMock(DiscountLineItem::class)]
        ];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with($ruleOwners, $context)
            ->willReturn($ruleOwners);

        self::assertSame($ruleOwners, $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context));
    }

    public function testGetFilteredRuleOwnersForMultiShippingWithSubOrders(): void
    {
        $ruleOwners = [$this->getRuleOwner(1)];
        $context = [
            ContextDataConverterInterface::SHIPPING_METHOD => 'test',
            ContextDataConverterInterface::LINE_ITEMS      => [$this->createMock(DiscountLineItem::class)],
            OrderContextDataConverter::SUB_ORDERS          => [new Order()]
        ];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with($ruleOwners, $context)
            ->willReturn($ruleOwners);

        self::assertSame($ruleOwners, $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context));
    }

    public function testGetFilteredRuleOwnersForMultiShippingWhenNoLineItems(): void
    {
        $ruleOwners = [$this->getRuleOwner(1)];
        $context = [
            ContextDataConverterInterface::SHIPPING_METHOD => self::MULTI_SHIPPING,
            ContextDataConverterInterface::LINE_ITEMS      => []
        ];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with($ruleOwners, $context)
            ->willReturn($ruleOwners);

        self::assertSame($ruleOwners, $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context));
    }

    public function testGetFilteredRuleOwnersForMultiShippingWhenLineItemTypeIsNotSupported(): void
    {
        $lineItem1 = $this->getLineItem(new \stdClass());

        $ruleOwners = [$this->getRuleOwner(1), $this->getRuleOwner(2)];
        $context = [
            ContextDataConverterInterface::SHIPPING_METHOD => self::MULTI_SHIPPING,
            ContextDataConverterInterface::LINE_ITEMS      => [$lineItem1],
            ContextDataConverterInterface::APPLIED_COUPONS => new ArrayCollection(),
            'key'                                          => 'val'
        ];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with($ruleOwners, $context)
            ->willReturn($ruleOwners);

        self::assertSame($ruleOwners, $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context));
    }

    public function testGetFilteredRuleOwnersForMultiShippingWhenSomeLineItemsDoNotHaveShippingMethod(): void
    {
        $lineItem1 = $this->getLineItem($this->getOrderLineItem('shipping_method_1'));
        $lineItem2 = $this->getLineItem($this->getOrderLineItem(null));

        $ruleOwners = [$this->getRuleOwner(1), $this->getRuleOwner(2)];
        $context = [
            ContextDataConverterInterface::SHIPPING_METHOD => self::MULTI_SHIPPING,
            ContextDataConverterInterface::LINE_ITEMS      => [$lineItem1, $lineItem2],
            ContextDataConverterInterface::APPLIED_COUPONS => new ArrayCollection(),
            'key'                                          => 'val'
        ];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with($ruleOwners, $context)
            ->willReturn($ruleOwners);

        self::assertSame($ruleOwners, $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context));
    }

    public function testGetFilteredRuleOwnersForMultiShippingWhenAllLineItemsHaveShippingMethod(): void
    {
        $lineItem1 = $this->getLineItem($this->getOrderLineItem('shipping_method_1', 11), 101.0);
        $lineItem2 = $this->getLineItem($this->getOrderLineItem('shipping_method_1', 12), 102.0);
        $lineItem3 = $this->getLineItem($this->getOrderLineItem('shipping_method_2', 13), 103.0);

        $ruleOwners = [
            $this->getRuleOwner(1),
            $this->getRuleOwner(2, 'shipping'),
            $this->getRuleOwner(3, 'shipping'),
            $this->getRuleOwner(4, 'shipping'),
            $this->getRuleOwner(5),
            $this->getRuleOwner(6)
        ];
        $context = [
            ContextDataConverterInterface::SHIPPING_METHOD => self::MULTI_SHIPPING,
            ContextDataConverterInterface::SHIPPING_COST   => Price::create(20, 'USD'),
            ContextDataConverterInterface::LINE_ITEMS      => [$lineItem1, $lineItem2, $lineItem3],
            ContextDataConverterInterface::SUBTOTAL        => 306.0,
            ContextDataConverterInterface::APPLIED_COUPONS => new ArrayCollection(),
            'key'                                          => 'val'
        ];

        $this->baseFiltrationService->expects(self::exactly(3))
            ->method('getFilteredRuleOwners')
            ->withConsecutive(
                [
                    $ruleOwners,
                    [
                        ContextDataConverterInterface::SHIPPING_METHOD => 'shipping_method_1',
                        ContextDataConverterInterface::SHIPPING_COST   => Price::create(11, 'USD'),
                        ContextDataConverterInterface::LINE_ITEMS      => [$lineItem1],
                        ContextDataConverterInterface::SUBTOTAL        => 101.0,
                        'key'                                          => 'val'
                    ]
                ],
                [
                    $ruleOwners,
                    [
                        ContextDataConverterInterface::SHIPPING_METHOD => 'shipping_method_1',
                        ContextDataConverterInterface::SHIPPING_COST   => Price::create(12, 'USD'),
                        ContextDataConverterInterface::LINE_ITEMS      => [$lineItem2],
                        ContextDataConverterInterface::SUBTOTAL        => 102.0,
                        'key'                                          => 'val'
                    ]
                ],
                [
                    $ruleOwners,
                    [
                        ContextDataConverterInterface::SHIPPING_METHOD => 'shipping_method_2',
                        ContextDataConverterInterface::SHIPPING_COST   => Price::create(13, 'USD'),
                        ContextDataConverterInterface::LINE_ITEMS      => [$lineItem3],
                        ContextDataConverterInterface::SUBTOTAL        => 103.0,
                        'key'                                          => 'val'
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                [$ruleOwners[0]],
                [$ruleOwners[0], $ruleOwners[2]],
                [$ruleOwners[1], $ruleOwners[5]]
            );

        self::assertEquals(
            [
                $ruleOwners[0],
                $ruleOwners[5],
                new MultiShippingPromotionData($ruleOwners[2], [$lineItem2]),
                new MultiShippingPromotionData($ruleOwners[1], [$lineItem3]),
            ],
            $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context)
        );
    }
}
