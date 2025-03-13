<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\ActionGroup;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Action\MultiShipping\DefaultMultiShippingGroupMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Action\MultiShipping\DefaultMultiShippingMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemGroupsShippingManagerInterface;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemsShippingManagerInterface;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ShippingMethodActions;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateShippingPriceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ShippingMethodActionsTest extends TestCase
{
    private ConfigProvider&MockObject $configProvider;
    private ActionExecutor&MockObject $actionExecutor;
    private DefaultShippingMethodSetterInterface&MockObject $defaultShippingMethodSetter;
    private DefaultMultiShippingMethodSetterInterface&MockObject $defaultMultiShippingMethodSetter;
    private DefaultMultiShippingGroupMethodSetterInterface&MockObject $defaultMultiShippingGroupMethodSetter;
    private CheckoutLineItemsShippingManagerInterface&MockObject $checkoutLineItemsShipping;
    private CheckoutLineItemGroupsShippingManagerInterface&MockObject $checkoutLineItemGroupsShipping;
    private UpdateShippingPriceInterface&MockObject $updateShippingPrice;
    private ManagerRegistry&MockObject $doctrine;
    private ShippingMethodActions $shippingMethodActions;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->defaultShippingMethodSetter = $this->createMock(DefaultShippingMethodSetterInterface::class);
        $this->defaultMultiShippingMethodSetter = $this->createMock(DefaultMultiShippingMethodSetterInterface::class);
        $this->defaultMultiShippingGroupMethodSetter = $this->createMock(
            DefaultMultiShippingGroupMethodSetterInterface::class
        );
        $this->checkoutLineItemsShipping = $this->createMock(CheckoutLineItemsShippingManagerInterface::class);
        $this->checkoutLineItemGroupsShipping = $this->createMock(
            CheckoutLineItemGroupsShippingManagerInterface::class
        );
        $this->updateShippingPrice = $this->createMock(UpdateShippingPriceInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->shippingMethodActions = new ShippingMethodActions(
            $this->actionExecutor,
            $this->configProvider,
            $this->defaultShippingMethodSetter,
            $this->defaultMultiShippingMethodSetter,
            $this->defaultMultiShippingGroupMethodSetter,
            $this->checkoutLineItemsShipping,
            $this->checkoutLineItemGroupsShipping,
            $this->updateShippingPrice,
            $this->doctrine
        );
    }

    private function expectFlushData(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Checkout::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('flush');
    }

    public function testHasApplicableShippingRulesHasEnabledShippingRules(): void
    {
        $errors = new ArrayCollection();
        $checkout = new Checkout();
        $checkout->setShippingMethod('flat_rate');

        $this->configProvider->expects(self::once())
            ->method('isMultiShippingEnabled')
            ->willReturn(false);

        $this->actionExecutor->expects(self::once())
            ->method('evaluateExpression')
            ->with(
                'shipping_method_has_enabled_shipping_rules',
                ['method_identifier' => 'flat_rate'],
                $errors,
                'oro.checkout.validator.has_applicable_shipping_rules.message'
            )
            ->willReturn(true);

        self::assertTrue($this->shippingMethodActions->hasApplicableShippingRules($checkout, $errors));
    }

    public function testHasApplicableShippingRulesHasEnabledShippingRulesForMultiShippingPerLineItem(): void
    {
        $errors = new ArrayCollection();
        $checkout = new Checkout();
        $checkout->setShippingMethod('flat_rate');

        $this->configProvider->expects(self::once())
            ->method('isMultiShippingEnabled')
            ->willReturn(true);
        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(true);

        $this->actionExecutor->expects(self::once())
            ->method('evaluateExpression')
            ->with(
                'line_items_shipping_methods_has_enabled_shipping_rules',
                ['entity' => $checkout],
                $errors,
                'oro.checkout.validator.has_applicable_shipping_rules.message'
            )
            ->willReturn(true);

        self::assertTrue($this->shippingMethodActions->hasApplicableShippingRules($checkout, $errors));
    }

    public function testHasApplicableShippingRulesHasEnabledShippingRulesForMultiShippingPerLineItemGroup(): void
    {
        $errors = new ArrayCollection();
        $checkout = new Checkout();
        $checkout->setShippingMethod('flat_rate');

        $this->configProvider->expects(self::once())
            ->method('isMultiShippingEnabled')
            ->willReturn(true);
        $this->configProvider->expects(self::exactly(2))
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);
        $this->configProvider->expects(self::once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(true);

        $this->actionExecutor->expects(self::once())
            ->method('evaluateExpression')
            ->with(
                'line_item_groups_shipping_methods_has_enabled_shipping_rules',
                ['entity' => $checkout],
                $errors,
                'oro.checkout.validator.has_applicable_shipping_rules.message'
            )
            ->willReturn(true);

        self::assertTrue($this->shippingMethodActions->hasApplicableShippingRules($checkout, $errors));
    }

    public function testUpdateDefaultShippingMethodsWithoutMultiShipping(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);
        $this->configProvider->expects(self::once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(false);

        $this->defaultMultiShippingMethodSetter->expects(self::never())
            ->method('setDefaultShippingMethods');
        $this->defaultMultiShippingGroupMethodSetter->expects(self::never())
            ->method('setDefaultShippingMethods');
        $this->defaultShippingMethodSetter->expects(self::once())
            ->method('setDefaultShippingMethod')
            ->with($checkout);

        $this->shippingMethodActions->updateDefaultShippingMethods($checkout, [], []);
    }

    public function testUpdateDefaultShippingMethodsWithMultiShippingPerLineItem(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $lineItemsShippingMethods = ['flat_rate_1'];
        $lineItemGroupsShippingMethods = ['flat_rate_2'];

        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(true);
        $this->configProvider->expects(self::never())
            ->method('isLineItemsGroupingEnabled');

        $this->defaultMultiShippingMethodSetter->expects(self::once())
            ->method('setDefaultShippingMethods')
            ->with($checkout, $lineItemsShippingMethods, true);
        $this->defaultMultiShippingGroupMethodSetter->expects(self::never())
            ->method('setDefaultShippingMethods');
        $this->defaultShippingMethodSetter->expects(self::never())
            ->method('setDefaultShippingMethod');

        $this->expectFlushData();

        $this->shippingMethodActions->updateDefaultShippingMethods(
            $checkout,
            $lineItemsShippingMethods,
            $lineItemGroupsShippingMethods
        );
    }

    public function testUpdateDefaultShippingMethodsWithMultiShippingPerLineItemGroup(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $lineItemsShippingMethods = ['flat_rate_1'];
        $lineItemGroupsShippingMethods = ['flat_rate_2'];

        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);
        $this->configProvider->expects(self::once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(true);

        $this->defaultMultiShippingMethodSetter->expects(self::never())
            ->method('setDefaultShippingMethods');
        $this->defaultMultiShippingGroupMethodSetter->expects(self::once())
            ->method('setDefaultShippingMethods')
            ->with($checkout, $lineItemGroupsShippingMethods, true);
        $this->defaultShippingMethodSetter->expects(self::never())
            ->method('setDefaultShippingMethod');

        $this->expectFlushData();

        $this->shippingMethodActions->updateDefaultShippingMethods(
            $checkout,
            $lineItemsShippingMethods,
            $lineItemGroupsShippingMethods
        );
    }

    public function testUpdateCheckoutShippingPricesNoMultiShipping(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);
        $this->configProvider->expects(self::once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(false);

        $this->checkoutLineItemsShipping->expects(self::never())
            ->method('updateLineItemsShippingPrices');
        $this->checkoutLineItemGroupsShipping->expects(self::never())
            ->method('updateLineItemGroupsShippingPrices');

        $this->updateShippingPrice->expects(self::once())
            ->method('execute')
            ->with($checkout);

        $this->shippingMethodActions->updateCheckoutShippingPrices($checkout);
    }

    public function testUpdateCheckoutShippingPricesShippingSelectionByLineItemEnabled(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(true);
        $this->configProvider->expects(self::never())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(false);

        $this->checkoutLineItemsShipping->expects(self::once())
            ->method('updateLineItemsShippingPrices')
            ->with($checkout);
        $this->checkoutLineItemGroupsShipping->expects(self::never())
            ->method('updateLineItemGroupsShippingPrices');

        $this->updateShippingPrice->expects(self::once())
            ->method('execute')
            ->with($checkout);

        $this->shippingMethodActions->updateCheckoutShippingPrices($checkout);
    }

    public function testUpdateCheckoutShippingPricesMultiShippingEnabledPerLineItemGroup(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);
        $this->configProvider->expects(self::once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(true);

        $this->checkoutLineItemsShipping->expects(self::never())
            ->method('updateLineItemsShippingPrices');

        $this->checkoutLineItemGroupsShipping->expects(self::once())
            ->method('updateLineItemGroupsShippingPrices')
            ->with($checkout);

        $this->updateShippingPrice->expects(self::once())
            ->method('execute')
            ->with($checkout);

        $this->shippingMethodActions->updateCheckoutShippingPrices($checkout);
    }

    public function testActualizeShippingMethodsForLineItemsLineItemsShippingMethodsUpdateRequired(): void
    {
        $lineItems = new ArrayCollection([$this->createMock(CheckoutLineItem::class)]);
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $lineItemsShippingMethods = ['flat_rate_1'];
        $lineItemGroupsShippingMethods = ['flat_rate_2'];

        $this->configProvider->expects(self::once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(true);
        $this->configProvider->expects(self::once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(false);

        $this->actionExecutor->expects(self::once())
            ->method('evaluateExpression')
            ->with(
                'is_line_items_shipping_methods_update_required',
                [$lineItems, $lineItemsShippingMethods]
            )
            ->willReturn(true);

        $this->defaultMultiShippingMethodSetter->expects(self::once())
            ->method('setDefaultShippingMethods')
            ->with($checkout, $lineItemsShippingMethods);
        $this->defaultMultiShippingGroupMethodSetter->expects(self::never())
            ->method('setDefaultShippingMethods');

        $this->expectFlushData();

        $this->shippingMethodActions->actualizeShippingMethods(
            $checkout,
            $lineItemsShippingMethods,
            $lineItemGroupsShippingMethods
        );
    }

    public function testActualizeShippingMethodsForisLineItemGroupsShippingMethodsUpdateRequired(): void
    {
        $lineItems = new ArrayCollection([$this->createMock(CheckoutLineItem::class)]);
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $lineItemsShippingMethods = ['flat_rate_1'];
        $lineItemGroupsShippingMethods = ['flat_rate_2'];

        $this->configProvider->expects(self::exactly(2))
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);
        $this->configProvider->expects(self::once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(true);

        $this->actionExecutor->expects(self::once())
            ->method('evaluateExpression')
            ->with(
                'is_line_item_groups_shipping_methods_update_required',
                [$lineItems, $lineItemGroupsShippingMethods]
            )
            ->willReturn(true);

        $this->defaultMultiShippingMethodSetter->expects(self::never())
            ->method('setDefaultShippingMethods');
        $this->defaultMultiShippingGroupMethodSetter->expects(self::once())
            ->method('setDefaultShippingMethods')
            ->with($checkout, $lineItemGroupsShippingMethods);

        $this->expectFlushData();

        $this->shippingMethodActions->actualizeShippingMethods(
            $checkout,
            $lineItemsShippingMethods,
            $lineItemGroupsShippingMethods
        );
    }
}
