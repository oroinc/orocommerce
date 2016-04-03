<?php

namespace OroB2B\Bundle\PaymentBundle\Method;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\OroB2BPaymentExtension;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Gateway;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class PayflowGateway implements PaymentMethodInterface
{
    const TYPE = 'PayPalPaymentsPro';

    /** @var Gateway */
    protected $gateway;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param Gateway $gateway
     * @param ConfigManager $configManager
     */
    public function __construct(Gateway $gateway, ConfigManager $configManager)
    {
        $this->gateway = $gateway;
        $this->configManager = $configManager;
    }

    /** {@inheritdoc} */
    public function execute(PaymentTransaction $paymentTransaction)
    {
        $actionName = $paymentTransaction->getAction();

        if (!method_exists($this, $actionName)) {
            throw new \InvalidArgumentException(sprintf('Unknown action "%s"', $actionName));
        }

        return $this->{$actionName}($paymentTransaction);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    public function authorize(PaymentTransaction $paymentTransaction)
    {
        $options = [
            Option\Tender::TENDER => Option\Tender::CREDIT_CARD,
            Option\Amount::AMT => round($paymentTransaction->getAmount(), 2),
            Option\Account::ACCT => '4111111111111111',
            Option\ExpirationDate::EXPDATE => '1218',
        ];

        $paymentTransaction->setRequest($options);

        $response = $this->gateway
            ->request(Option\Transaction::AUTHORIZATION, array_replace($this->getCredentials(), $options));

        $paymentTransaction
            ->setActive($response->isSuccessful())
            ->setReference($response->getReference())
            ->setResponse($response->getData());
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    public function capture(PaymentTransaction $paymentTransaction)
    {
        $sourcePaymentTransaction = $paymentTransaction->getSourcePaymentTransaction();
        if (!$sourcePaymentTransaction) {
            return;
        }

        $keys = [Option\Tender::TENDER];
        $options = array_intersect_key($sourcePaymentTransaction->getRequest(), array_flip($keys));
        $options[Option\OriginalTransaction::ORIGID] = $sourcePaymentTransaction->getReference();

        $paymentTransaction->setRequest($options);

        $response = $this->gateway
            ->request(Option\Transaction::DELAYED_CAPTURE, array_replace($this->getCredentials(), $options));

        $paymentTransaction
            ->setAmount($sourcePaymentTransaction->getAmount())
            ->setReference($response->getReference())
            ->setResponse($response->getData());

        $sourcePaymentTransaction->setActive(!$response->isSuccessful());
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
     * @param string $key
     * @return string
     */
    protected function getConfigValue($key)
    {
        $key = OroB2BPaymentExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $key;

        return $this->configManager->get($key);
    }

    /** {@inheritdoc} */
    public function getType()
    {
        return self::TYPE;
    }
}
