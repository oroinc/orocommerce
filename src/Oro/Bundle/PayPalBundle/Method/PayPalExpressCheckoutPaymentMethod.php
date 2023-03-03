<?php

namespace Oro\Bundle\PayPalBundle\Method;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\SurchargeProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\OptionsProvider\OptionsProviderInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Executes payment for PayPal Express Checkout.
 */
class PayPalExpressCheckoutPaymentMethod implements PaymentMethodInterface
{
    const COMPLETE = 'complete';

    // PayPal BN code
    const BUTTON_SOURCE = 'OroCommerce_SP';

    const PILOT_REDIRECT_URL = 'https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&useraction=commit&token=%s';
    const PRODUCTION_REDIRECT_URL = 'https://www.paypal.com/webscr?cmd=_express-checkout&useraction=commit&token=%s';

    const AMOUNT_PRECISION = 2;

    /** @var Gateway */
    protected $gateway;

    /** @var RouterInterface */
    protected $router;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var PayPalExpressCheckoutConfigInterface */
    protected $config;

    /** @var OptionsProviderInterface */
    protected $optionsProvider;

    /** @var SurchargeProvider */
    protected $surchargeProvider;

    public function __construct(
        Gateway $gateway,
        PayPalExpressCheckoutConfigInterface $config,
        RouterInterface $router,
        DoctrineHelper $doctrineHelper,
        OptionsProviderInterface $optionsProvider,
        SurchargeProvider $surchargeProvider,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->gateway = $gateway;
        $this->config = $config;
        $this->router = $router;
        $this->doctrineHelper = $doctrineHelper;
        $this->optionsProvider = $optionsProvider;
        $this->surchargeProvider = $surchargeProvider;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        if (!$this->supports($action)) {
            throw new \InvalidArgumentException(sprintf('Unsupported action "%s"', $action));
        }

        $this->gateway->setTestMode($this->config->isTestMode());

        return $this->{$action}($paymentTransaction) ?: [];
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(PaymentContextInterface $context)
    {
        $amount = round($context->getTotal(), self::AMOUNT_PRECISION);
        $zeroAmount = round(0, self::AMOUNT_PRECISION);

        return !($amount === $zeroAmount);
    }

    /**
     * @param string $actionName
     * @return bool
     */
    public function supports($actionName)
    {
        return in_array(
            $actionName,
            [self::PURCHASE, self::AUTHORIZE, self::CHARGE, self::CAPTURE, self::COMPLETE],
            true
        );
    }

    protected function purchase(PaymentTransaction $paymentTransaction): array
    {
        $options = array_merge(
            $this->getCredentials(),
            $this->getSetExpressCheckoutOptions($paymentTransaction),
            $this->getShippingAddressOptions($paymentTransaction)
        );

        $paymentTransaction->setRequest($options);
        $paymentTransaction->setAction($this->config->getPurchaseAction());

        $this->execute($paymentTransaction->getAction(), $paymentTransaction);

        if (!$paymentTransaction->isActive()) {
            return [];
        }

        return [
            'purchaseRedirectUrl' => $this->getRedirectUrl($paymentTransaction->getReference()),
        ];
    }

    protected function complete(PaymentTransaction $paymentTransaction): void
    {
        $options = array_merge(
            $this->getCredentials(),
            $this->getAdditionalOptions(),
            $this->getDoExpressCheckoutOptions($paymentTransaction)
        );

        $paymentTransaction->setRequest($options);
        $response = $this->actionRequest($paymentTransaction, ECOption\Action::DO_EC);

        $data = $response->getData();
        $paymentTransaction
            ->setSuccessful($response->isSuccessful())
            ->setActive(false)
            ->setResponse($data)
            ->setReference($response->getReference());

        // Payment with non-complete pending reason should be marked as pending
        if ($paymentTransaction->isSuccessful()
            && isset($data['PENDINGREASON'])
            && !in_array($data['PENDINGREASON'], ['none', 'completed', 'authorization'])
        ) {
            $paymentTransaction
                ->setActive(true)
                ->setSuccessful(false);
        }

        if ($paymentTransaction->getAction() === self::AUTHORIZE && $paymentTransaction->isSuccessful()) {
            $paymentTransaction->setActive(true);
        }
    }

    protected function authorize(PaymentTransaction $paymentTransaction): void
    {
        $options = array_merge(
            $paymentTransaction->getRequest(),
            $this->getAdditionalOptions(),
            [Option\Transaction::TRXTYPE => Option\Transaction::AUTHORIZATION]
        );

        $paymentTransaction->setRequest($options);
        $this->setExpressCheckoutRequest($paymentTransaction);
    }

    protected function charge(PaymentTransaction $paymentTransaction): void
    {
        $options = array_merge(
            $paymentTransaction->getRequest(),
            $this->getAdditionalOptions(),
            [Option\Transaction::TRXTYPE => Option\Transaction::SALE]
        );

        $paymentTransaction->setRequest($options);
        $this->setExpressCheckoutRequest($paymentTransaction);
    }

    protected function capture(PaymentTransaction $paymentTransaction): array
    {
        $sourcePaymentTransaction = $paymentTransaction->getSourcePaymentTransaction();
        if (!$sourcePaymentTransaction) {
            $paymentTransaction
                ->setSuccessful(false)
                ->setActive(false);

            return ['successful' => false];
        }

        $options = array_merge(
            $this->getCredentials(),
            $this->getAdditionalOptions(),
            $this->getDelayedCaptureOptions($paymentTransaction)
        );

        $response = $this->gateway->request(Option\Transaction::DELAYED_CAPTURE, $options);

        $paymentTransaction
            ->setRequest($options)
            ->setSuccessful($response->isSuccessful())
            ->setActive(false)
            ->setReference($response->getReference())
            ->setResponse($response->getData());

        $sourcePaymentTransaction->setActive(!$paymentTransaction->isSuccessful());

        return [
            'message' => $response->getMessage() ?: $response->getErrorMessage(),
            'successful' => $response->isSuccessful(),
        ];
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return null
     */
    protected function setExpressCheckoutRequest(PaymentTransaction $paymentTransaction)
    {
        $response = $this->actionRequest($paymentTransaction, ECOption\Action::SET_EC);

        $paymentTransaction
            ->setSuccessful(false)
            ->setActive($response->isSuccessful())
            ->setResponse($response->getData());

        $data = $response->getData();

        if (!isset($data[ECOption\Token::TOKEN])) {
            $paymentTransaction->setActive(false);

            return;
        }

        $paymentTransaction->setReference($data[ECOption\Token::TOKEN]);
    }

    protected function getRedirectUrl(string $token): string
    {
        $redirectUrl = $this->config->isTestMode() ? self::PILOT_REDIRECT_URL : self::PRODUCTION_REDIRECT_URL;

        return sprintf($redirectUrl, $token);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function getSetExpressCheckoutOptions(PaymentTransaction $paymentTransaction)
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

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function getDoExpressCheckoutOptions(PaymentTransaction $paymentTransaction)
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

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function getDelayedCaptureOptions(PaymentTransaction $paymentTransaction)
    {
        $sourceTransaction = $paymentTransaction->getSourcePaymentTransaction();

        return [
            Option\Amount::AMT => round($paymentTransaction->getAmount(), self::AMOUNT_PRECISION),
            Option\OriginalTransaction::ORIGID => $sourceTransaction->getReference(),
        ];
    }

    /**
     * @param $paymentTransaction
     * @return array
     */
    protected function getShippingAddressOptions(PaymentTransaction $paymentTransaction)
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
            Option\ShippingAddress::SHIPTOLASTNAME => $addressOption->getLastName(),
            Option\ShippingAddress::SHIPTOSTREET => $addressOption->getStreet(),
            Option\ShippingAddress::SHIPTOSTREET2 => $addressOption->getStreet2(),
            Option\ShippingAddress::SHIPTOCITY => $addressOption->getCity(),
            Option\ShippingAddress::SHIPTOSTATE => $addressOption->getRegionCode(),
            Option\ShippingAddress::SHIPTOZIP => $addressOption->getPostalCode(),
            Option\ShippingAddress::SHIPTOCOUNTRY => $addressOption->getCountryIso2()
        ];
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function getLineItemOptions(PaymentTransaction $paymentTransaction)
    {
        $entity = $this->doctrineHelper->getEntityReference(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );

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

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function getSurcharges(PaymentTransaction $paymentTransaction)
    {
        $entity = $this->doctrineHelper->getEntityReference(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );

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

    /**
     * @param PaymentTransaction $paymentTransaction
     * @param string $requestAction
     * @return ResponseInterface
     */
    protected function actionRequest(PaymentTransaction $paymentTransaction, $requestAction)
    {
        $options = $paymentTransaction->getRequest();
        $transactionType = $this->getTransactionType($paymentTransaction);

        $options[ECOption\Action::ACTION] = $requestAction;
        unset($options[Option\Transaction::TRXTYPE]);

        return $this->gateway->request($transactionType, $options);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return null|string
     */
    protected function getTransactionType(PaymentTransaction $paymentTransaction)
    {
        $request = $paymentTransaction->getRequest();

        if (isset($request[Option\Transaction::TRXTYPE])) {
            return $request[Option\Transaction::TRXTYPE];
        }

        return null;
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        return $this->propertyAccessor;
    }

    /**
     * @return array
     */
    protected function getCredentials()
    {
        return array_merge(
            $this->config->getCredentials(),
            [
                Option\Tender::TENDER => Option\Tender::PAYPAL,
            ]
        );
    }

    /**
     * @return array
     */
    protected function getAdditionalOptions()
    {
        return [
            Option\ButtonSource::BUTTONSOURCE => self::BUTTON_SOURCE
        ];
    }
}
