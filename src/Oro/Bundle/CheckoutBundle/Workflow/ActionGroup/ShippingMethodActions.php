<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Action\MultiShipping\DefaultMultiShippingGroupMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Action\MultiShipping\DefaultMultiShippingMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemGroupsShippingManager;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemsShippingManager;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;

/**
 * Checkout workflow shipping method-related actions.
 */
class ShippingMethodActions implements ShippingMethodActionsInterface
{
    public function __construct(
        private ActionExecutor $actionExecutor,
        private ConfigProvider $configProvider,
        private DefaultShippingMethodSetterInterface $defaultShippingMethodSetter,
        private DefaultMultiShippingMethodSetterInterface $defaultMultiShippingMethodSetter,
        private DefaultMultiShippingGroupMethodSetterInterface $defaultMultiShippingGroupMethodSetter,
        private CheckoutLineItemsShippingManager $checkoutLineItemsShipping,
        private CheckoutLineItemGroupsShippingManager $checkoutLineItemGroupsShipping,
        private UpdateShippingPriceInterface $updateShippingPrice
    ) {
    }

    public function hasApplicableShippingRules(Checkout $checkout, ?Collection $errors): bool
    {
        return $this->hasEnabledShippingRules($checkout, $errors)
            || $this->hasEnabledShippingRulesForMultiShippingPerLineItem($checkout, $errors)
            || $this->hasEnabledShippingRulesForMultiShippingPerLineItemGroup($checkout, $errors);
    }

    public function updateDefaultShippingMethods(
        Checkout $checkout,
        ?array $lineItemsShippingMethods,
        ?array $lineItemGroupsShippingMethods
    ): void {
        if (!$this->configProvider->isMultiShippingEnabled()) {
            $this->defaultShippingMethodSetter->setDefaultShippingMethod($checkout);
        }

        // Sync actual shipping values for cases if new line items have been added.
        if ($this->configProvider->isShippingSelectionByLineItemEnabled()) {
            $this->defaultMultiShippingMethodSetter->setDefaultShippingMethods(
                $checkout,
                $lineItemsShippingMethods,
                true
            );
        }

        // Sync actual shipping values for cases if new line items have been added.
        if ($this->isMultiShippingEnabledPerLineItemGroup()) {
            $this->defaultMultiShippingGroupMethodSetter->setDefaultShippingMethods(
                $checkout,
                $lineItemGroupsShippingMethods,
                true
            );
        }
    }

    public function actualizeShippingMethods(
        Checkout $checkout,
        ?array $lineItemsShippingMethods,
        ?array $lineItemGroupsShippingMethods
    ): void {
        // Update line items shipping data if customer returns to the checkout on the order payment method step.
        if ($this->isLineItemsShippingMethodsUpdateRequired($checkout, $lineItemsShippingMethods)) {
            $this->defaultMultiShippingMethodSetter->setDefaultShippingMethods(
                $checkout,
                $lineItemsShippingMethods
            );
        }

        // Update line items group shipping data if customer returns to the checkout on the order payment method step.
        if ($this->isLineItemGroupsShippingMethodsUpdateRequired($checkout, $lineItemGroupsShippingMethods)) {
            $this->defaultMultiShippingGroupMethodSetter->setDefaultShippingMethods(
                $checkout,
                $lineItemGroupsShippingMethods
            );
        }
    }

    public function updateCheckoutShippingPrices(Checkout $checkout): void
    {
        if ($this->configProvider->isShippingSelectionByLineItemEnabled()) {
            $this->checkoutLineItemsShipping->updateLineItemsShippingPrices($checkout);
        }

        if ($this->isMultiShippingEnabledPerLineItemGroup()) {
            $this->checkoutLineItemGroupsShipping->updateLineItemGroupsShippingPrices($checkout);
        }

        $this->updateShippingPrice->execute($checkout);
    }

    private function hasEnabledShippingRules(Checkout $checkout, ?Collection $errors): bool
    {
        return !$this->configProvider->isMultiShippingEnabled()
            && $this->actionExecutor->evaluateExpression(
                'shipping_method_has_enabled_shipping_rules',
                ['method_identifier' => $checkout->getShippingMethod()],
                $errors,
                'oro.checkout.workflow.condition.shipping_method_is_not_available.message'
            );
    }

    private function hasEnabledShippingRulesForMultiShippingPerLineItem(Checkout $checkout, ?Collection $errors): bool
    {
        return $this->configProvider->isShippingSelectionByLineItemEnabled()
            && $this->actionExecutor->evaluateExpression(
                'line_items_shipping_methods_has_enabled_shipping_rules',
                ['entity' => $checkout],
                $errors,
                'oro.checkout.workflow.condition.shipping_method_is_not_available.message'
            );
    }

    private function hasEnabledShippingRulesForMultiShippingPerLineItemGroup(
        Checkout $checkout,
        ?Collection $errors
    ): bool {
        return $this->isMultiShippingEnabledPerLineItemGroup()
            && $this->actionExecutor->evaluateExpression(
                'line_item_groups_shipping_methods_has_enabled_shipping_rules',
                ['entity' => $checkout],
                $errors,
                'oro.checkout.workflow.condition.shipping_method_is_not_available.message'
            );
    }

    private function isLineItemsShippingMethodsUpdateRequired(
        Checkout $checkout,
        ?array $lineItemsShippingMethods
    ): bool {
        return $this->configProvider->isShippingSelectionByLineItemEnabled()
            && $this->actionExecutor->evaluateExpression(
                'is_line_items_shipping_methods_update_required',
                [$checkout->getLineItems(), $lineItemsShippingMethods]
            );
    }

    private function isLineItemGroupsShippingMethodsUpdateRequired(
        Checkout $checkout,
        ?array $lineItemGroupsShippingMethods
    ): bool {
        return $this->isMultiShippingEnabledPerLineItemGroup()
            && $this->actionExecutor->evaluateExpression(
                'is_line_item_groups_shipping_methods_update_required',
                [$checkout->getLineItems(), $lineItemGroupsShippingMethods]
            );
    }

    private function isMultiShippingEnabledPerLineItemGroup(): bool
    {
        return $this->configProvider->isLineItemsGroupingEnabled()
            && !$this->configProvider->isShippingSelectionByLineItemEnabled();
    }
}
