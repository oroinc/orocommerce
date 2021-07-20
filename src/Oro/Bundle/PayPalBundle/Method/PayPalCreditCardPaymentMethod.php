<?php

namespace Oro\Bundle\PayPalBundle\Method;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option as GatewayOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\Response;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Implements interaction with PayPal credit card gateway. Proceed and set status of PaymentTransaction entity.
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PayPalCreditCardPaymentMethod implements PaymentMethodInterface
{
    const COMPLETE = 'complete';

    const ZERO_AMOUNT = 0;
    const AMOUNT_PRECISION = 2;

    // PayPal BN code
    const BUTTON_SOURCE = 'OroCommerce_SP';

    /** @var Gateway */
    protected $gateway;

    /** @var RouterInterface */
    protected $router;

    /** @var PayPalCreditCardConfigInterface */
    protected $config;

    public function __construct(Gateway $gateway, PayPalCreditCardConfigInterface $config, RouterInterface $router)
    {
        $this->gateway = $gateway;
        $this->config = $config;
        $this->router = $router;
    }

    /** {@inheritdoc} */
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        if (!$this->supports($action)) {
            throw new \InvalidArgumentException(sprintf('Unsupported action "%s"', $action));
        }

        $this->gateway->setTestMode($this->config->isTestMode());
        $this->gateway->setSslVerificationEnabled($this->config->isSslVerificationEnabled());

        if ($this->config->isUseProxyEnabled()) {
            $this->gateway->setProxySettings($this->config->getProxyHost(), $this->config->getProxyPort());
        }

        return $this->{$action}($paymentTransaction) ?: [];
    }

    public function authorize(PaymentTransaction $paymentTransaction)
    {
        $sourcePaymentTransaction = $paymentTransaction->getSourcePaymentTransaction();
        if ($sourcePaymentTransaction && !$this->config->isAuthorizationForRequiredAmountEnabled()) {
            $this->useValidateTransactionData($paymentTransaction, $sourcePaymentTransaction);

            return;
        }

        $response = $this->gateway
            ->request(
                Option\Transaction::AUTHORIZATION,
                $this->combineOptions((array)$paymentTransaction->getRequest())
            );

        $paymentTransaction
            ->setSuccessful($response->isSuccessful())
            ->setActive($response->isSuccessful())
            ->setReference($response->getReference())
            ->setResponse($response->getData());
    }

    protected function useValidateTransactionData(
        PaymentTransaction $paymentTransaction,
        PaymentTransaction $sourcePaymentTransaction
    ) {
        $paymentTransaction
            ->setCurrency($sourcePaymentTransaction->getCurrency())
            ->setReference($sourcePaymentTransaction->getReference())
            ->setSuccessful($sourcePaymentTransaction->isSuccessful())
            ->setActive($sourcePaymentTransaction->isActive())
            ->setRequest()
            ->setResponse();
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    public function charge(PaymentTransaction $paymentTransaction)
    {
        $response = $this->gateway
            ->request(
                Option\Transaction::SALE,
                $this->combineOptions((array)$paymentTransaction->getRequest())
            );

        $paymentTransaction
            ->setSuccessful($response->isSuccessful())
            ->setActive($response->isSuccessful())
            ->setReference($response->getReference())
            ->setResponse($response->getData());

        $sourcePaymentTransaction = $paymentTransaction->getSourcePaymentTransaction();

        if ($sourcePaymentTransaction) {
            $paymentTransaction->setActive(false);
        }

        if ($sourcePaymentTransaction && $sourcePaymentTransaction->getAction() !== self::VALIDATE) {
            $sourcePaymentTransaction->setActive(!$paymentTransaction->isSuccessful());
        }

        return [
            'message' => $response->getMessage() ?: $response->getErrorMessage(),
            'successful' => $response->isSuccessful(),
        ];
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    public function capture(PaymentTransaction $paymentTransaction)
    {
        $options = $this->getPaymentOptions($paymentTransaction);
        $paymentTransaction->setRequest($options);

        $sourcePaymentTransaction = $paymentTransaction->getSourcePaymentTransaction();
        if (!$sourcePaymentTransaction) {
            $paymentTransaction
                ->setSuccessful(false)
                ->setActive(false);

            return ['successful' => false];
        }

        if ($sourcePaymentTransaction->isClone()) {
            return $this->charge($paymentTransaction);
        }

        unset($options[Option\Currency::CURRENCY]);

        $response = $this->gateway
            ->request(Option\Transaction::DELAYED_CAPTURE, $this->combineOptions($options));

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
     * @return array
     */
    public function purchase(PaymentTransaction $paymentTransaction)
    {
        $options = $this->getPaymentOptions($paymentTransaction);

        $sourcePaymentTransaction = $paymentTransaction->getSourcePaymentTransaction();
        if (!$sourcePaymentTransaction) {
            $options = array_merge($options, $this->getSecureTokenOptions($paymentTransaction));
        }

        $paymentTransaction
            ->setRequest($options)
            ->setAction($this->config->getPurchaseAction());

        $response = $this->execute($paymentTransaction->getAction(), $paymentTransaction);

        if (!$sourcePaymentTransaction) {
            return $this->secureTokenResponse($paymentTransaction);
        }

        return $response;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    public function validate(PaymentTransaction $paymentTransaction)
    {
        $paymentTransaction
            ->setAmount(self::ZERO_AMOUNT)
            ->setCurrency(Option\Currency::US_DOLLAR);

        $options = array_merge(
            $this->getPaymentOptions($paymentTransaction),
            $this->getSecureTokenOptions($paymentTransaction)
        );

        $paymentTransaction
            ->setRequest($options)
            ->setAction(PaymentMethodInterface::VALIDATE);

        $this->authorize($paymentTransaction);

        return $this->secureTokenResponse($paymentTransaction);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function secureTokenResponse(PaymentTransaction $paymentTransaction)
    {
        // Mark successful false for generate token transaction
        // PayPal callback should update transaction
        $paymentTransaction->setSuccessful(false);

        $keys = [GatewayOption\SecureToken::SECURETOKEN, GatewayOption\SecureTokenIdentifier::SECURETOKENID];

        $response = array_intersect_key($paymentTransaction->getResponse(), array_flip($keys));

        $response['formAction'] = $this->gateway->getFormAction();

        return $response;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function getPaymentOptions(PaymentTransaction $paymentTransaction)
    {
        $options = [
            Option\Amount::AMT => round($paymentTransaction->getAmount(), self::AMOUNT_PRECISION),
            Option\Tender::TENDER => Option\Tender::CREDIT_CARD,
            Option\Currency::CURRENCY => $paymentTransaction->getCurrency(),
        ];

        if ($paymentTransaction->getSourcePaymentTransaction()) {
            $options[Option\OriginalTransaction::ORIGID] =
                $paymentTransaction->getSourcePaymentTransaction()->getReference();
        }

        return $options;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function getSecureTokenOptions(PaymentTransaction $paymentTransaction)
    {
        return [
            GatewayOption\SecureTokenIdentifier::SECURETOKENID => UUIDGenerator::v4(),
            GatewayOption\CreateSecureToken::CREATESECURETOKEN => true,
            GatewayOption\TransparentRedirect::SILENTTRAN => true,
            Option\ReturnUrl::RETURNURL => $this->router->generate(
                'oro_payment_callback_return',
                ['accessIdentifier' => $paymentTransaction->getAccessIdentifier()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            GatewayOption\ErrorUrl::ERRORURL => $this->router->generate(
                'oro_payment_callback_error',
                ['accessIdentifier' => $paymentTransaction->getAccessIdentifier()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            GatewayOption\SilentPost::SILENTPOSTURL => $this->router->generate(
                'oro_payment_callback_notify',
                [
                    'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    'accessToken' => $paymentTransaction->getAccessToken(),
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ];
    }

    /**
     * @return array
     */
    protected function getVerbosityOption()
    {
        $option = [];
        if ($this->config->isDebugModeEnabled()) {
            $option = [
                Option\Verbosity::VERBOSITY => Option\Verbosity::HIGH,
            ];
        }

        return $option;
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
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionName)
    {
        if ($actionName === self::VALIDATE) {
            return $this->config->isZeroAmountAuthorizationEnabled();
        }

        return in_array(
            $actionName,
            [self::AUTHORIZE, self::CAPTURE, self::CHARGE, self::PURCHASE, self::COMPLETE],
            true
        );
    }

    public function complete(PaymentTransaction $paymentTransaction)
    {
        $response = new Response($paymentTransaction->getResponse());

        $paymentTransaction
            ->setReference($response->getReference())
            ->setActive($response->isSuccessful())
            ->setSuccessful($response->isSuccessful());

        if ($paymentTransaction->getAction() === PaymentMethodInterface::CHARGE) {
            $paymentTransaction->setActive(false);
        }
    }

    /**
     * @param array $options
     * @return array
     */
    protected function combineOptions(array $options = [])
    {
        return array_replace(
            $this->config->getCredentials(),
            $this->getAdditionalOptions(),
            $options,
            $this->getVerbosityOption()
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
