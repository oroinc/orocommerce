<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Api\Model\CheckoutLineItemGroup;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemGroupsShippingManagerInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Handles changing of checkout line item groups.
 */
class HandleCheckoutLineItemGroupChange implements ProcessorInterface
{
    public function __construct(
        private readonly CheckoutLineItemGroupsShippingManagerInterface $lineItemGroupsShippingManager,
        private readonly DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        if (!$context->getForm()->isValid()) {
            return;
        }

        $group = $context->getData();
        if (!$group instanceof CheckoutLineItemGroup) {
            return;
        }

        $checkout = $this->getCheckout($context, $group->getCheckoutId());
        if (null === $checkout) {
            return;
        }

        $shippingData = $this->lineItemGroupsShippingManager->getCheckoutLineItemGroupsShippingData($checkout);
        if ($this->hasChanges($shippingData, $group)) {
            if (null === $group->getShippingMethod()) {
                unset($shippingData[$group->getGroupKey()]);
            } else {
                $shippingData[$group->getGroupKey()] = [
                    'method' => $group->getShippingMethod(),
                    'type' => $group->getShippingMethodType()
                ];
            }
            $this->lineItemGroupsShippingManager->updateLineItemGroupsShippingMethods($shippingData, $checkout);
            $this->lineItemGroupsShippingManager->updateLineItemGroupsShippingPrices($checkout);
        }
    }

    private function hasChanges(array $shippingData, CheckoutLineItemGroup $group): bool
    {
        $data = $shippingData[$group->getGroupKey()] ?? null;
        if (!$data) {
            return null !== $group->getShippingMethod();
        }

        return
            $group->getShippingMethod() !== ($data['method'] ?? null)
            || $group->getShippingMethodType() !== ($data['type'] ?? null);
    }

    private function getCheckout(CustomizeFormDataContext $context, int $checkoutId): ?Checkout
    {
        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities) {
            return $this->doctrineHelper->getEntity(Checkout::class, $checkoutId);
        }

        $primaryEntity = $includedEntities->getPrimaryEntity();
        if ($primaryEntity instanceof Checkout) {
            if ($primaryEntity->getId() === $checkoutId) {
                return $primaryEntity;
            }
        } elseif ($primaryEntity instanceof CheckoutLineItem) {
            $checkout = $primaryEntity->getCheckout();
            if (null !== $checkout && $checkout->getId() === $checkoutId) {
                return $checkout;
            }
        }

        return $includedEntities->get(Checkout::class, $checkoutId);
    }
}
