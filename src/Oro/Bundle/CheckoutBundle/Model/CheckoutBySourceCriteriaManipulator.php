<?php

namespace Oro\Bundle\CheckoutBundle\Model;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Event\CheckoutActualizeEvent;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutLineItemsFactory;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Manipulate Checkout entity by a given Checkout source criteria.
 */
class CheckoutBySourceCriteriaManipulator implements CheckoutBySourceCriteriaManipulatorInterface
{
    public function __construct(
        private ActionExecutor $actionExecutor,
        private CheckoutRepository $checkoutRepository,
        private CheckoutLineItemsFactory $checkoutLineItemsFactory,
        private CheckoutShippingMethodsProviderInterface $shippingMethodsProvider,
        private CheckoutSubtotalUpdater $checkoutSubtotalUpdater,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    #[\Override]
    public function createCheckout(
        Website $website,
        array $sourceCriteria,
        ?UserInterface $customerUser = null,
        ?string $currency = null,
        array $checkoutData = []
    ): Checkout {
        $createEntityResult = $this->actionExecutor->executeAction(
            'create_entity',
            ['class' => CheckoutSource::class, 'data' => $sourceCriteria, 'attribute' => null]
        );

        $createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $checkout = new Checkout();
        $checkout->setSource($createEntityResult['attribute']);
        $checkout->setWebsite($website);
        $checkout->setCreatedAt($createdAt);
        $checkout->setUpdatedAt(clone $createdAt);
        $checkout->setCustomerUser($customerUser);

        $this->actualizeCheckout(
            $checkout,
            $website,
            $sourceCriteria,
            $currency,
            $checkoutData,
            true
        );

        return $checkout;
    }

    #[\Override]
    public function findCheckout(
        array $sourceCriteria,
        ?UserInterface $customerUser,
        ?string $currency,
        ?string $workflowName = null
    ): ?Checkout {
        if ($customerUser instanceof CustomerUser) {
            return $this->checkoutRepository->findCheckoutByCustomerUserAndSourceCriteriaWithCurrency(
                $customerUser,
                $sourceCriteria,
                $workflowName,
                $currency
            );
        }

        return $this->checkoutRepository->findCheckoutBySourceCriteriaWithCurrency(
            $sourceCriteria,
            $workflowName,
            $currency
        );
    }

    #[\Override]
    public function actualizeCheckout(
        Checkout $checkout,
        ?Website $website,
        array $sourceCriteria,
        ?string $currency,
        array $checkoutData = [],
        bool $updateData = false
    ): Checkout {
        $customerUser = $checkout->getCustomerUser();
        if ($customerUser && $updateData) {
            $checkout->setCustomer($customerUser->getCustomer());
            $checkout->setOrganization($customerUser->getOrganization());
            $checkout->setWebsite($website);

            $this->actionExecutor->executeAction(
                'copy_values',
                [$checkout, $checkoutData]
            );
        }

        $checkout->setCurrency($currency);
        $checkout->setLineItems($this->checkoutLineItemsFactory->create($checkout->getSource()?->getEntity()));

        if ($checkout->getShippingMethod()) {
            $checkout->setShippingCost($this->shippingMethodsProvider->getPrice($checkout));
        }

        $this->checkoutSubtotalUpdater->recalculateCheckoutSubtotals($checkout);

        $event = new CheckoutActualizeEvent($checkout, $sourceCriteria, $checkoutData);
        $this->eventDispatcher->dispatch($event, 'oro_checkout.actualize');

        return $checkout;
    }
}
