<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource\GetSubresourceContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Api\Model\AvailableShippingMethod;
use Oro\Bundle\CheckoutBundle\Api\Model\AvailableShippingMethodType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Loads available shipping methods for Checkout entity.
 */
class LoadAvailableShippingMethodsSubresource implements ProcessorInterface
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly CheckoutShippingMethodsProviderInterface $shippingMethodsProvider
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var GetSubresourceContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $context->setResult($this->getAvailableShippingMethods($context->getParentId()));
    }

    private function getAvailableShippingMethods(int $checkoutId): array
    {
        $checkout = $this->doctrineHelper->getEntity(Checkout::class, $checkoutId);
        if (null === $checkout
            || $checkout->isDeleted()
            || !$this->authorizationChecker->isGranted(BasicPermission::VIEW, $checkout)
        ) {
            return [];
        }

        $methodTypes = [];
        $data = $this->shippingMethodsProvider->getApplicableMethodsViews($checkout);
        $allTypesViews = $data->getAllMethodsTypesViews();
        foreach ($allTypesViews as $shippingMethod => $typesViews) {
            $types = [];
            foreach ($typesViews as $typeView) {
                /** @var Price $price */
                $price = $typeView['price'];
                $types[$typeView['sortOrder']][] = new AvailableShippingMethodType(
                    $typeView['identifier'],
                    $typeView['label'],
                    $price->getValue(),
                    $price->getCurrency()
                );
            }
            $methodTypes[$shippingMethod] = $this->sortAndFlatten($types);
        }

        $methods = [];
        $allViews = $data->getAllMethodsViews();
        foreach ($allViews as $view) {
            $shippingMethod = $view['identifier'];
            $types = $methodTypes[$shippingMethod] ?? [];
            if ($types) {
                $methods[$view['sortOrder']][] = new AvailableShippingMethod(
                    $shippingMethod,
                    $view['label'],
                    $types
                );
            }
        }

        return $this->sortAndFlatten($methods);
    }

    private function sortAndFlatten(array $items): array
    {
        if ($items) {
            ksort($items);
            $items = array_merge(...array_values($items));
        }

        return $items;
    }
}
