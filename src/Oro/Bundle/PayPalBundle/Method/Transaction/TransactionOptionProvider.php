<?php

namespace Oro\Bundle\PayPalBundle\Method\Transaction;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Provider\SurchargeProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\OptionsProvider\OptionsProviderInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option as GatewayOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Helps to convert payment transaction to request options
 */
class TransactionOptionProvider
{
    private const AMOUNT_PRECISION = 2;

    protected SurchargeProvider $surchargeProvider;
    protected DoctrineHelper $doctrineHelper;
    protected OptionsProviderInterface $optionsProvider;
    protected RouterInterface $router;
    protected PropertyAccessor $propertyAccessor;

    protected PayPalExpressCheckoutConfigInterface $config;

    public function __construct(
        SurchargeProvider $surchargeProvider,
        DoctrineHelper $doctrineHelper,
        OptionsProviderInterface $optionsProvider,
        RouterInterface $router,
        PropertyAccessor $propertyAccessor
    ) {
        $this->surchargeProvider = $surchargeProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->optionsProvider = $optionsProvider;
        $this->router = $router;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function setConfig(PayPalExpressCheckoutConfigInterface $config): self
    {
        $this->config = $config;
        return $this;
    }

    protected function getPropertyAccessor(): PropertyAccessor
    {
        return $this->propertyAccessor;
    }

    public function getShippingAddressOptions(PaymentTransaction $paymentTransaction): array
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
            $billingOptions = $propertyAccessor->getValue($entity, 'billingAddress');
        } catch (NoSuchPropertyException $e) {
            return [];
        }

        if (!$billingOptions instanceof OrderAddress) {
            return [];
        }

        return [
            Option\BillingAddress::BILLTOFIRSTNAME => (string) $billingOptions->getFirstName(),
            Option\BillingAddress::BILLTOMIDDLENAME => (string) $billingOptions->getMiddleName(),
            Option\BillingAddress::BILLTOLASTNAME => (string) $billingOptions->getLastName(),
            Option\BillingAddress::BILLTOSTREET => (string) $billingOptions->getStreet(),
            Option\BillingAddress::BILLTOSTREET2 => (string) $billingOptions->getStreet2(),
            Option\BillingAddress::BILLTOCITY => (string) $billingOptions->getCity(),
            Option\BillingAddress::BILLTOSTATE => (string) $billingOptions->getRegionCode(),
            Option\BillingAddress::BILLTOZIP => (string) $billingOptions->getPostalCode(),
            Option\BillingAddress::BILLTOCOUNTRY => (string) $billingOptions->getCountryIso2(),
            Option\BillingAddress::BILLTOCOMPANY => (string) $billingOptions->getOrganization(),
            Option\BillingAddress::BILLTOPHONENUM => (string) $billingOptions->getPhone(),
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

    public function getSetExpressCheckoutOptions(PaymentTransaction $paymentTransaction): array
    {
        return array_merge(
            $this->getLineItemOptions($paymentTransaction),
            $this->getSurcharges($paymentTransaction),
            [
                ECOption\PaymentType::PAYMENTTYPE => ECOption\PaymentType::INSTANTONLY,
                ECOption\ShippingAddressOverride::ADDROVERRIDE => ECOption\ShippingAddressOverride::TRUE,
                Option\Amount::AMT => round($paymentTransaction->getAmount(), self::AMOUNT_PRECISION),
                Option\Currency::CURRENCY => $paymentTransaction->getCurrency(),
                Option\ReturnUrl::RETURNURL => $this->router->generate(
                    'oro_payment_callback_return',
                    [
                        'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                Option\CancelUrl::CANCELURL => $this->router->generate(
                    'oro_payment_callback_error',
                    [
                        'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ]
        );
    }

    public function getDoExpressCheckoutOptions(PaymentTransaction $paymentTransaction): array
    {
        $options = array_merge(
            $this->getLineItemOptions($paymentTransaction),
            $this->getSurcharges($paymentTransaction),
            [
                Option\Amount::AMT => round($paymentTransaction->getAmount(), self::AMOUNT_PRECISION),
                Option\Transaction::TRXTYPE => $this->getTransactionType($paymentTransaction),
                ECOption\Token::TOKEN => $paymentTransaction->getReference(),
            ]
        );

        $response = $paymentTransaction->getResponse();

        if (isset($response['PayerID'])) {
            $options[ECOption\Payer::PAYERID] = $response['PayerID'];
        }

        return $options;
    }

    public function getDelayedCaptureOptions(PaymentTransaction $paymentTransaction): array
    {
        $sourceTransaction = $paymentTransaction->getSourcePaymentTransaction();

        $options = [
            Option\Amount::AMT => round($paymentTransaction->getAmount(), self::AMOUNT_PRECISION),
            Option\OriginalTransaction::ORIGID => $sourceTransaction->getReference(),
        ];

        $entity = $this->doctrineHelper->getEntityReference(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );

        if ($entity instanceof Order) {
            $options[Option\Order::ORDERID] = (string) $entity->getIdentifier();
        }

        return $options;
    }

    public function getLineItemOptions(PaymentTransaction $paymentTransaction): array
    {
        $entity = $this->loadEntity($paymentTransaction);

        if (!$entity) {
            return [];
        }

        if (!$entity instanceof LineItemsAwareInterface) {
            return [];
        }

        $options = [];
        $lineItemOptions = $this->optionsProvider->getLineItemOptions($entity);

        foreach ($lineItemOptions as $lineItemOption) {
            $options[] = [
                Option\LineItems::NAME => $lineItemOption->getName(),
                Option\LineItems::DESC => $lineItemOption->getDescription(),
                Option\LineItems::COST => $lineItemOption->getCost(),
                // PayPal accepts only integer qty.
                // Float qty could be correctly converted to int in getLineItemOptions
                Option\LineItems::QTY => (int)$lineItemOption->getQty(),
            ];
        }

        return Option\LineItems::prepareOptions($options);
    }

    public function getSurcharges(PaymentTransaction $paymentTransaction): array
    {
        $entity = $this->loadEntity($paymentTransaction);

        if (!$entity) {
            return [];
        }

        $surcharge = $this->surchargeProvider->getSurcharges($entity);

        return [
            Option\Amount::FREIGHTAMT => $surcharge->getShippingAmount(),
            Option\Amount::HANDLINGAMT => $surcharge->getHandlingAmount(),
            Option\Amount::DISCOUNT => -1.0 * $surcharge->getDiscountAmount(),
            Option\Amount::INSURANCEAMT => $surcharge->getInsuranceAmount(),
        ];
    }

    public function getIPOptions(PaymentTransaction $paymentTransaction): array
    {
        return [
            Option\IPAddress::CUSTIP => Request::createFromGlobals()->getClientIp(),
        ];
    }

    public function getCredentials(): array
    {
        return array_merge(
            $this->config->getCredentials(),
            [
                Option\Tender::TENDER => Option\Tender::PAYPAL,
            ]
        );
    }

    private function loadEntity(PaymentTransaction $paymentTransaction): ?object
    {
        return $this->doctrineHelper->getEntityReference(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );
    }

    private function getTransactionType(PaymentTransaction $paymentTransaction): ?string
    {
        $request = $paymentTransaction->getRequest();
        return $request[Option\Transaction::TRXTYPE] ?? null;
    }
}
