<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource\GetSubresourceContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Api\Model\AvailablePaymentMethod;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewFrontendApiOptionsInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Loads available payment methods for Checkout entity.
 */
class LoadAvailablePaymentMethodsSubresource implements ProcessorInterface
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly ApplicablePaymentMethodsProvider $paymentMethodProvider,
        private readonly PaymentMethodViewProviderInterface $paymentMethodViewProvider,
        private readonly CheckoutPaymentContextProvider $checkoutPaymentContextProvider
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

        $context->setResult($this->getAvailablePaymentMethods($context->getParentId()));
    }

    private function getAvailablePaymentMethods(int $checkoutId): array
    {
        $checkout = $this->doctrineHelper->getEntity(Checkout::class, $checkoutId);
        if (
            null === $checkout
            || $checkout->isDeleted()
            || !$this->authorizationChecker->isGranted(BasicPermission::VIEW, $checkout)
        ) {
            return [];
        }

        $methods = [];
        $paymentContext = $this->checkoutPaymentContextProvider->getContext($checkout);
        $views = $this->paymentMethodViewProvider->getPaymentMethodViews(
            $this->getApplicablePaymentMethodIds($paymentContext)
        );
        foreach ($views as $view) {
            $id = $view->getPaymentMethodIdentifier();
            $options = [];
            if ($view instanceof PaymentMethodViewFrontendApiOptionsInterface) {
                $options = $view->getFrontendApiOptions($paymentContext);
            }
            $methods[$id] = new AvailablePaymentMethod($id, $view->getLabel(), $options);
        }

        return array_values($methods);
    }

    private function getApplicablePaymentMethodIds(PaymentContextInterface $paymentContext): array
    {
        $methodIds = [];
        $methods = $this->paymentMethodProvider->getApplicablePaymentMethods($paymentContext);
        foreach ($methods as $method) {
            $methodIds[] = $method->getIdentifier();
        }

        return $methodIds;
    }
}
