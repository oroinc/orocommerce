<?php

namespace Oro\Bundle\PayPalBundle\Method\Transaction;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalBundle\OptionsProvider\OptionsProviderInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option as GatewayOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Helps to convert payment transaction to request options
 */
class TransactionOptionProvider
{
    protected DoctrineHelper $doctrineHelper;
    protected OptionsProviderInterface $optionsProvider;
    protected PropertyAccessor $propertyAccessor;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        OptionsProviderInterface $optionsProvider,
        PropertyAccessor $propertyAccessor
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->optionsProvider = $optionsProvider;
        $this->propertyAccessor = $propertyAccessor;
    }

    protected function getPropertyAccessor(): PropertyAccessor
    {
        return $this->propertyAccessor;
    }

    public function getBillingAddressOptions(PaymentTransaction $paymentTransaction)
    {
        $entity = $this->doctrineHelper->getEntityReference(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );

        if (!$entity) {
            return [];
        }

        $propertyAccessor = $this->getPropertyAccessor();

        try {
            $billingAddress = $propertyAccessor->getValue($entity, 'billingAddress');
        } catch (NoSuchPropertyException $e) {
            return [];
        }

        if (!$billingAddress instanceof OrderAddress) {
            return [];
        }

        return [
            Option\BillingAddress::BILLTOCOMPANY => (string) $billingAddress->getOrganization(),
            Option\BillingAddress::BILLTOPHONENUM => (string) $billingAddress->getPhone(),
        ];
    }

    public function getShippingAddressOptions(PaymentTransaction $paymentTransaction)
    {
        $entity = $this->doctrineHelper->getEntityReference(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );

        if (!$entity) {
            return [];
        }

        $propertyAccessor = $this->getPropertyAccessor();

        try {
            $shippingAddress = $propertyAccessor->getValue($entity, 'shippingAddress');
        } catch (NoSuchPropertyException $e) {
            return [];
        }

        if (!$shippingAddress instanceof AbstractAddress) {
            return [];
        }

        $addressOption = $this->optionsProvider->getShippingAddressOptions($shippingAddress);

        return [
            Option\ShippingAddress::SHIPTOFIRSTNAME => $addressOption->getFirstName(),
            Option\ShippingAddress::SHIPTOMIDDLENAME => $addressOption->getMiddleName(),
            Option\ShippingAddress::SHIPTOLASTNAME => $addressOption->getLastName(),
            Option\ShippingAddress::SHIPTOSTREET => $addressOption->getStreet(),
            Option\ShippingAddress::SHIPTOSTREET2 => $addressOption->getStreet2(),
            Option\ShippingAddress::SHIPTOCITY => $addressOption->getCity(),
            Option\ShippingAddress::SHIPTOSTATE => $addressOption->getRegionCode(),
            Option\ShippingAddress::SHIPTOZIP => $addressOption->getPostalCode(),
            Option\ShippingAddress::SHIPTOCOUNTRY => $addressOption->getCountryIso2(),
            Option\ShippingAddress::SHIPTOCOMPANY => $addressOption->getOrganization(),
            Option\ShippingAddress::SHIPTOPHONE => $addressOption->getPhone(),
        ];
    }

    public function getCustomerUserOptions(PaymentTransaction $paymentTransaction)
    {
        if (!$customerUser = $paymentTransaction->getFrontendOwner()) {
            return [];
        }

        $emailFromOptions = $paymentTransaction->getTransactionOptions()['email'] ?? null;
        $email = (string) ($emailFromOptions ?: $customerUser->getEmail());

        return [
            Option\ShippingAddress::SHIPTOEMAIL => $email,
            Option\BillingAddress::BILLTOEMAIL => $email,
            GatewayOption\Customer::EMAIL => $email,
            GatewayOption\Customer::CUSTCODE => (string) $customerUser->getId(),
        ];
    }

    public function getOrderOptions(PaymentTransaction $paymentTransaction)
    {
        $entity = $this->doctrineHelper->getEntityReference(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );

        if (!$entity instanceof Order) {
            return [];
        }

        return [
            GatewayOption\Comment::COMMENT1 => (string) $entity->getCustomerNotes(),
            GatewayOption\Purchase::PONUM => (string) $entity->getPoNumber(),
        ];
    }

    public function getIPOptions(PaymentTransaction $paymentTransaction): array
    {
        return [
            Option\IPAddress::CUSTIP => Request::createFromGlobals()->getClientIp(),
        ];
    }
}
