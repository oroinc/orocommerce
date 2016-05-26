<?php

namespace OroB2B\Bundle\PaymentBundle\Method;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Gateway;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;
use OroB2B\Bundle\PaymentBundle\Traits\ConfigTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class PayflowGateway implements PaymentMethodInterface
{
    use ConfigTrait, CountryAwarePaymentMethodTrait;

    const TYPE = 'payflow_gateway';

    const ZERO_AMOUNT = 0;

    /** @var Gateway */
    protected $gateway;

    /** @var RouterInterface */
    protected $router;

    /**
     * @param Gateway $gateway
     * @param ConfigManager $configManager
     * @param RouterInterface $router
     */
    public function __construct(Gateway $gateway, ConfigManager $configManager, RouterInterface $router)
    {
        $this->gateway = $gateway;
        $this->configManager = $configManager;
        $this->router = $router;
    }

    /** {@inheritdoc} */
    public function execute(PaymentTransaction $paymentTransaction)
    {
        $actionName = $paymentTransaction->getAction();

        if (!$this->supports($actionName)) {
            throw new \InvalidArgumentException(sprintf('Unsupported action "%s"', $actionName));
        }

        $this->gateway->setTestMode($this->isTestMode());

        return $this->{$actionName}($paymentTransaction) ?: [];
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    public function authorize(PaymentTransaction $paymentTransaction)
    {
        $sourcePaymentTransaction = $paymentTransaction->getSourcePaymentTransaction();
        if ($sourcePaymentTransaction && !$this->getRequiredAmountEnabled()) {
            $this->useValidateTransactionData($paymentTransaction, $sourcePaymentTransaction);

            return;
        }

        $response = $this->gateway
            ->request(
                Option\Transaction::AUTHORIZATION,
                array_replace($this->getCredentials(), (array)$paymentTransaction->getRequest())
            );

        $paymentTransaction
            ->setSuccessful($response->isSuccessful())
            ->setActive($response->isSuccessful())
            ->setReference($response->getReference())
            ->setResponse($response->getData());
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @param PaymentTransaction $sourcePaymentTransaction
     */
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
                array_replace($this->getCredentials(), (array)$paymentTransaction->getRequest())
            );

        $paymentTransaction
            ->setSuccessful($response->isSuccessful())
            ->setActive(!$response->isSuccessful())
            ->setReference($response->getReference())
            ->setResponse($response->getData());

        $sourcePaymentTransaction = $paymentTransaction->getSourcePaymentTransaction();
        if ($sourcePaymentTransaction && $sourcePaymentTransaction->getAction() !== self::VALIDATE) {
            $sourcePaymentTransaction->setActive(!$paymentTransaction->isSuccessful());
        }

        return [
            'message' => $response->getMessage(),
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

        if (!$sourcePaymentTransaction->getResponse() && !$sourcePaymentTransaction->getRequest()) {
            return $this->charge($paymentTransaction);
        }

        unset($options[Option\Currency::CURRENCY]);

        $response = $this->gateway
            ->request(Option\Transaction::DELAYED_CAPTURE, array_replace($this->getCredentials(), $options));

        $paymentTransaction
            ->setRequest($options)
            ->setSuccessful($response->isSuccessful())
            ->setActive(false)
            ->setReference($response->getReference())
            ->setResponse($response->getData());

        $sourcePaymentTransaction->setActive(!$paymentTransaction->isSuccessful());

        return [
            'message' => $response->getMessage(),
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
            ->setAction($this->getPurchaseAction());

        $response = $this->execute($paymentTransaction);

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

        $keys = [Option\SecureToken::SECURETOKEN, Option\SecureTokenIdentifier::SECURETOKENID];

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
            Option\Amount::AMT => round($paymentTransaction->getAmount(), 2),
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
            Option\SecureTokenIdentifier::SECURETOKENID => Option\SecureTokenIdentifier::generate(),
            Option\CreateSecureToken::CREATESECURETOKEN => true,
            Option\TransparentRedirect::SILENTTRAN => true,
            Option\ReturnUrl::RETURNURL => $this->router->generate(
                'orob2b_payment_callback_return',
                [
                    'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    'accessToken' => $paymentTransaction->getAccessToken(),
                ],
                true
            ),
            Option\ErrorUrl::ERRORURL => $this->router->generate(
                'orob2b_payment_callback_error',
                [
                    'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    'accessToken' => $paymentTransaction->getAccessToken(),
                ],
                true
            ),
        ];
    }

    /**
     * @return array
     */
    protected function getCredentials()
    {
        return [
            Option\Vendor::VENDOR => $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_VENDOR_KEY),
            Option\User::USER => $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_USER_KEY),
            Option\Password::PASSWORD => $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_PASSWORD_KEY),
            Option\Partner::PARTNER => $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_PARTNER_KEY),
        ];
    }

    /**
     * @return bool
     */
    protected function isTestMode()
    {
        return (bool)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_TEST_MODE_KEY);
    }

    /**
     * @return string
     */
    protected function getPurchaseAction()
    {
        return $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_PAYMENT_ACTION_KEY);
    }

    /** {@inheritdoc} */
    public function isApplicable(array $context = [])
    {
        return $this->isCountryApplicable($context);
    }

    /**
     * @return bool
     */
    protected function getAllowedCountries()
    {
        return $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_SELECTED_COUNTRIES_KEY);
    }

    /**
     * @return bool
     */
    protected function isAllCountriesAllowed()
    {
        return $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_ALLOWED_COUNTRIES_KEY)
            === Configuration::ALLOWED_COUNTRIES_ALL;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_ENABLED_KEY);
    }

    /**
     * @return bool
     */
    protected function isZeroAmountAuthorizationEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_ZERO_AMOUNT_AUTHORIZATION_KEY);
    }

    /** {@inheritdoc} */
    public function getType()
    {
        return static::TYPE;
    }

    /** {@inheritdoc} */
    public function supports($actionName)
    {
        if ($actionName === self::VALIDATE) {
            return $this->isZeroAmountAuthorizationEnabled();
        }

        return in_array(
            $actionName,
            [self::AUTHORIZE, self::CAPTURE, self::CHARGE, self::PURCHASE],
            true
        );
    }

    /**
     * @return bool
     */
    protected function getRequiredAmountEnabled()
    {
        return $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY);
    }
}
