<?php

namespace OroB2B\Bundle\PaymentBundle\Method;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Gateway;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;
use OroB2B\Bundle\PaymentBundle\Traits\ConfigTrait;

class PayflowGateway implements PaymentMethodInterface
{
    use ConfigTrait, CountryAwarePaymentMethodTrait;

    const TYPE = 'payflow_gateway';

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

        if (!method_exists($this, $actionName)) {
            throw new \InvalidArgumentException(sprintf('Unknown action "%s"', $actionName));
        }

        $this->gateway->setTestMode($this->isTestMode());

        return $this->{$actionName}($paymentTransaction) ?: [];
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    public function authorize(PaymentTransaction $paymentTransaction)
    {
        $response = $this->gateway
            ->request(
                Option\Transaction::AUTHORIZATION,
                array_replace($this->getCredentials(), $paymentTransaction->getRequest())
            );

        $paymentTransaction
            ->setSuccessful($response->isSuccessful())
            ->setActive($response->isSuccessful())
            ->setReference($response->getReference())
            ->setResponse($response->getData());
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    public function charge(PaymentTransaction $paymentTransaction)
    {
        $response = $this->gateway
            ->request(
                Option\Transaction::SALE,
                array_replace($this->getCredentials(), $paymentTransaction->getRequest())
            );

        $paymentTransaction
            ->setSuccessful($response->isSuccessful())
            ->setReference($response->getReference())
            ->setResponse($response->getData());
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    public function capture(PaymentTransaction $paymentTransaction)
    {
        $sourcePaymentTransaction = $paymentTransaction->getSourcePaymentTransaction();
        if (!$sourcePaymentTransaction) {
            return [];
        }

        $keys = [Option\Tender::TENDER];
        $options = array_intersect_key($sourcePaymentTransaction->getRequest(), array_flip($keys));
        $options[Option\OriginalTransaction::ORIGID] = $sourcePaymentTransaction->getReference();

        $paymentTransaction->setRequest($options);

        $response = $this->gateway
            ->request(Option\Transaction::DELAYED_CAPTURE, array_replace($this->getCredentials(), $options));

        $paymentTransaction
            ->setSuccessful($response->isSuccessful())
            ->setReference($response->getReference())
            ->setResponse($response->getData());

        $sourcePaymentTransaction
            ->setActive(!$paymentTransaction->isSuccessful())
            ->setSuccessful($response->isSuccessful());

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
        $options = [
            Option\SecureTokenIdentifier::SECURETOKENID => Option\SecureTokenIdentifier::generate(),
            Option\CreateSecureToken::CREATESECURETOKEN => true,
            Option\Amount::AMT => round($paymentTransaction->getAmount(), 2),
            Option\TransparentRedirect::SILENTTRAN => true,
            Option\Tender::TENDER => Option\Tender::CREDIT_CARD,
            Option\Currency::CURRENCY => $paymentTransaction->getCurrency(),
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

        $paymentTransaction
            ->setRequest($options)
            ->setAction($this->getPurchaseAction());

        $this->execute($paymentTransaction);

        $keys = [Option\SecureToken::SECURETOKEN, Option\SecureTokenIdentifier::SECURETOKENID];

        $response = array_intersect_key($paymentTransaction->getResponse(), array_flip($keys));

        $response['formAction'] = $this->gateway->getFormAction();

        return $response;
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

    /** {@inheritdoc} */
    public function getType()
    {
        return static::TYPE;
    }
}
