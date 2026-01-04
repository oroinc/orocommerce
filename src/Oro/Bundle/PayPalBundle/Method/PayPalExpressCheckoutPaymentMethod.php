<?php

namespace Oro\Bundle\PayPalBundle\Method;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Transaction\TransactionOptionProvider;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Executes payment for PayPal Express Checkout.
 */
class PayPalExpressCheckoutPaymentMethod implements PaymentMethodInterface
{
    public const COMPLETE = 'complete';

    // PayPal BN code
    public const BUTTON_SOURCE = 'OroCommerce_SP';

    public const PILOT_REDIRECT_URL =
        'https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&useraction=commit&token=%s';
    public const PRODUCTION_REDIRECT_URL =
        'https://www.paypal.com/webscr?cmd=_express-checkout&useraction=commit&token=%s';

    public const AMOUNT_PRECISION = 2;

    protected Gateway $gateway;
    protected PropertyAccessor $propertyAccessor;
    protected PayPalExpressCheckoutConfigInterface $config;
    protected TransactionOptionProvider $transactionOptionProvider;

    public function __construct(
        Gateway $gateway,
        PayPalExpressCheckoutConfigInterface $config,
        PropertyAccessor $propertyAccessor,
        TransactionOptionProvider $transactionOptionProvider
    ) {
        $this->gateway = $gateway;
        $this->config = $config;
        $this->propertyAccessor = $propertyAccessor;
        $this->transactionOptionProvider = $transactionOptionProvider;
    }

    #[\Override]
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        if (!$this->supports($action)) {
            throw new \InvalidArgumentException(sprintf('Unsupported action "%s"', $action));
        }

        $this->gateway->setTestMode($this->config->isTestMode());

        return $this->{$action}($paymentTransaction) ?: [];
    }

    #[\Override]
    public function getIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }

    #[\Override]
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
    #[\Override]
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
            $this->transactionOptionProvider->getCredentials(),
            $this->transactionOptionProvider->getSetExpressCheckoutOptions($paymentTransaction),
            $this->transactionOptionProvider->getShippingAddressOptions($paymentTransaction),
            $this->transactionOptionProvider->getBillingAddressOptions($paymentTransaction),
            $this->transactionOptionProvider->getCustomerUserOptions($paymentTransaction),
            $this->transactionOptionProvider->getOrderOptions($paymentTransaction),
            $this->transactionOptionProvider->getIPOptions($paymentTransaction),
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
            $this->transactionOptionProvider->getCredentials(),
            $this->getAdditionalOptions(),
            $this->transactionOptionProvider->getDoExpressCheckoutOptions($paymentTransaction),
            $this->transactionOptionProvider->getShippingAddressOptions($paymentTransaction),
            $this->transactionOptionProvider->getBillingAddressOptions($paymentTransaction),
            $this->transactionOptionProvider->getCustomerUserOptions($paymentTransaction),
            $this->transactionOptionProvider->getOrderOptions($paymentTransaction),
            $this->transactionOptionProvider->getIPOptions($paymentTransaction),
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
            $this->transactionOptionProvider->getCredentials(),
            $this->getAdditionalOptions(),
            $this->transactionOptionProvider->getDelayedCaptureOptions($paymentTransaction)
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

    protected function getTransactionType(PaymentTransaction $paymentTransaction): ?string
    {
        $request = $paymentTransaction->getRequest();
        return $request[Option\Transaction::TRXTYPE] ?? null;
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
    protected function getAdditionalOptions()
    {
        return [
            Option\ButtonSource::BUTTONSOURCE => self::BUTTON_SOURCE
        ];
    }
}
