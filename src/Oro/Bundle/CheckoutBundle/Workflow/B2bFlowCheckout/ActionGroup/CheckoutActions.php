<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Checkout workflow Checkout-related actions.
 */
class CheckoutActions
{
    public function __construct(
        private ManagerRegistry $registry,
        private EntityAliasResolver $entityAliasResolver,
        private EntityNameResolver $entityNameResolver,
        private UrlGeneratorInterface $urlGenerator,
        private ActionExecutor $actionExecutor
    ) {
    }

    public function purchase(
        Checkout $checkout,
        Order $order,
        array $transactionOptions = []
    ): array {
        $successUrl = $this->urlGenerator->generate(
            'oro_checkout_frontend_checkout',
            [
                'id' => $checkout->getId(),
                'transition' => 'finish_checkout'
            ]
        );
        $failureUrl = $this->urlGenerator->generate(
            'oro_checkout_frontend_checkout',
            [
                'id' => $checkout->getId(),
                'transition' => 'payment_error'
            ]
        );
        $partiallyPaidUrl = $this->urlGenerator->generate(
            'oro_checkout_frontend_checkout',
            [
                'id' => $checkout->getId(),
                'transition' => 'paid_partially'
            ]
        );

        $paymentTransactionOptions = array_merge(
            [
                'successUrl' => $successUrl,
                'failureUrl' => $failureUrl,
                'partiallyPaidUrl' => $partiallyPaidUrl,
                'failedShippingAddressUrl' => $failureUrl,
                'checkoutId' => $checkout->getId(),
                // email from outer context???
            ],
            $transactionOptions
        );

        $result = $this->actionExecutor->executeAction(
            'payment_purchase',
            [
                'attribute' => new PropertyPath('responseData'),
                'object' => $order,
                'amount' => $order->getTotal(),
                'currency' => $order->getCurrency(),
                'paymentMethod' => $checkout->getPaymentMethod(),
                'transactionOptions' => $paymentTransactionOptions
            ]
        );

        return ['responseData' => $result->get('responseData')];
    }

    public function finishCheckout(
        Checkout $checkout,
        Order $order,
        bool $autoRemoveSource = false,
        bool $allowManualSourceRemove = false,
        bool $removeSource = false,
        bool $clearSource = false
    ): void {
        $this->actualizeAddresses($checkout, $order);
        $this->sendConfirmationEmail($checkout, $order);
        $this->fillCheckoutCompletedData($checkout, $order);
        $this->finalizeSourceEntity(
            $checkout,
            $autoRemoveSource,
            $allowManualSourceRemove,
            $removeSource,
            $clearSource
        );
    }

    public function actualizeAddresses(Checkout $checkout, Order $order): void
    {
        $em = $this->registry->getManagerForClass(CustomerUserAddress::class);

        $customerUserBillingAddress = null;
        if ($checkout->isSaveBillingAddress()) {
            $customerUserBillingAddress = $this->actualizeAddress(
                $order->getBillingAddress(),
                $checkout,
                AddressType::TYPE_BILLING,
                'oro_order_address_billing_allow_manual'
            );
        }

        $customerUserShippingAddress = null;
        if ($checkout->isSaveShippingAddress()) {
            if ($customerUserBillingAddress
                && $checkout->isShipToBillingAddress()
                && $checkout->isSaveBillingAddress()
            ) {
                /** @var AddressType $shippingType */
                $shippingType = $em->getReference(AddressType::class, AddressType::TYPE_SHIPPING);
                $customerUserBillingAddress->addType($shippingType);
            } else {
                $customerUserShippingAddress = $this->actualizeAddress(
                    $order->getShippingAddress(),
                    $checkout,
                    AddressType::TYPE_SHIPPING,
                    'oro_order_address_shipping_allow_manual'
                );
            }
        }

        $needFlush = false;
        if ($customerUserBillingAddress) {
            $checkout->getBillingAddress()->setCustomerUserAddress($customerUserBillingAddress);
            $needFlush = true;
        }
        if ($customerUserShippingAddress) {
            $checkout->getShippingAddress()->setCustomerUserAddress($customerUserShippingAddress);
            $needFlush = true;
        }

        if ($needFlush) {
            $em->flush();
        }
    }

    public function sendConfirmationEmail(Checkout $checkout, Order $order): void
    {
        $this->actionExecutor->executeActionGroup(
            'b2b_flow_checkout_send_order_confirmation_email',
            [
                'checkout' => $checkout,
                'order' => $order,
                'workflow' => 'b2b_flow_checkout'
            ]
        );
    }

    public function finalizeSourceEntity(
        Checkout $checkout,
        bool $autoRemoveSource = false,
        bool $allowManualSourceRemove = false,
        bool $removeSource = false,
        bool $clearSource = false
    ): void {
        if (!$autoRemoveSource && !$allowManualSourceRemove && !$removeSource && $clearSource) {
            $this->actionExecutor->executeAction('clear_checkout_source_entity', [$checkout]);
        }
        if ($autoRemoveSource || ($allowManualSourceRemove && $removeSource)) {
            $this->actionExecutor->executeAction('remove_checkout_source_entity', [$checkout]);
        }
    }

    private function actualizeAddress(
        OrderAddress $orderAddress,
        Checkout $checkout,
        string $addressTypeName,
        string $aclResource
    ): ?CustomerUserAddress {
        if ($orderAddress->getCustomerAddress()
            || $orderAddress->getCustomerUserAddress()
            || !$this->isGranted($aclResource)
        ) {
            return null;
        }

        $em = $this->registry->getManagerForClass(CustomerUserAddress::class);
        /** @var AddressType $addressType */
        $addressType = $em->getReference(AddressType::class, $addressTypeName);
        $customerUserAddress = new CustomerUserAddress();
        $this->fillAddressFieldsByAddress(
            $orderAddress,
            $customerUserAddress,
            $checkout
        );
        $customerUserAddress->addType($addressType);

        $em->persist($customerUserAddress);

        $orderAddress->setCustomerUserAddress($customerUserAddress);

        return $customerUserAddress;
    }

    private function fillCheckoutCompletedData(Checkout $checkout, Order $order): void
    {
        $checkout->setCompleted(true);
        $checkout->getCompletedData()->offsetSet(
            'itemsCount',
            count($order->getLineItems())
        );
        $checkout->getCompletedData()->offsetSet(
            'orders',
            [
                [
                    'entityAlias' => $this->entityAliasResolver->getAlias(Order::class),
                    'entityId' => ['id' => $order->getId()]
                ]
            ]
        );
        $checkout->getCompletedData()->offsetSet(
            'currency',
            $order->getCurrency()
        );
        $checkout->getCompletedData()->offsetSet(
            'subtotal',
            $order->getSubtotalObject()->getValue()
        );
        $checkout->getCompletedData()->offsetSet(
            'total',
            $order->getTotalObject()->getValue()
        );

        if ($checkout->getSourceEntity()) {
            $checkout->getCompletedData()->offsetSet(
                'startedFrom',
                $this->entityNameResolver->getName($checkout->getSourceEntity()->getSourceDocument())
            );
        }
    }

    private function fillAddressFieldsByAddress(
        AbstractAddress $sourceAddress,
        AbstractAddress $destinationAddress,
        Checkout $checkout
    ): void {
        $destinationAddress
            ->setFrontendOwner($checkout->getCustomerUser())
            ->setOwner($checkout->getOwner())
            ->setSystemOrganization($checkout->getOrganization())
            ->setLabel($sourceAddress->getLabel())
            ->setOrganization($sourceAddress->getOrganization())
            ->setStreet($sourceAddress->getStreet())
            ->setStreet2($sourceAddress->getStreet2())
            ->setCity($sourceAddress->getCity())
            ->setPostalCode($sourceAddress->getPostalCode())
            ->setCountry($sourceAddress->getCountry())
            ->setRegion($sourceAddress->getRegion())
            ->setRegionText($sourceAddress->getRegionText())
            ->setNamePrefix($sourceAddress->getNamePrefix())
            ->setFirstName($sourceAddress->getFirstName())
            ->setMiddleName($sourceAddress->getMiddleName())
            ->setLastName($sourceAddress->getLastName())
            ->setNameSuffix($sourceAddress->getNameSuffix())
            ->setPhone($sourceAddress->getPhone());
    }

    private function isGranted(string $attribute): bool
    {
        return $this->actionExecutor->evaluateExpression('acl_granted', [$attribute]);
    }
}
