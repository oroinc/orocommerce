<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method;

use Psr\Log\LoggerAwareTrait;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Gateway;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class AuthorizeNetPaymentMethod implements PaymentMethodInterface
{
    use LoggerAwareTrait;

    const ZERO_AMOUNT = 0;
    const AMOUNT_PRECISION = 2;
    const API_CREDENTIALS_DELIMITER = ';';

    /**@var Gateway */
    protected $gateway;

    /** @var AuthorizeNetConfigInterface */
    protected $config;

    /**
     * @param Gateway $gateway
     * @param AuthorizeNetConfigInterface $config
     */
    public function __construct(Gateway $gateway, AuthorizeNetConfigInterface $config)
    {
        $this->gateway = $gateway;
        $this->config = $config;
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
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionName)
    {
        return in_array(
            $actionName,
            [self::AUTHORIZE, self::CAPTURE, self::CHARGE, self::PURCHASE, self::VALIDATE],
            true
        );
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

        switch ($action) {
            case self::VALIDATE:
                return $this->validate($paymentTransaction);
            case self::PURCHASE:
                return $this->purchase($paymentTransaction);
            case self::CAPTURE:
                return $this->capture($paymentTransaction);
            default:
                return $this->executePaymentAction($action, $paymentTransaction) ?: [];
        }
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function validate(PaymentTransaction $paymentTransaction)
    {
        $paymentTransaction
            ->setAmount(self::ZERO_AMOUNT)
            ->setCurrency(Option\Currency::US_DOLLAR)
            ->setAction(PaymentMethodInterface::VALIDATE)
            ->setActive(true)
            ->setSuccessful(true);

        return ['successful' => true];
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function purchase(PaymentTransaction $paymentTransaction)
    {
        $paymentTransaction
            ->setRequest($this->getPaymentOptions($paymentTransaction))
            ->setAction($this->config->getPurchaseAction());

        return $this->executePaymentAction($paymentTransaction->getAction(), $paymentTransaction);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function capture(PaymentTransaction $paymentTransaction)
    {
        $sourceTransaction = $paymentTransaction->getSourcePaymentTransaction();
        $options = $this->getPaymentOptions($paymentTransaction->getSourcePaymentTransaction());
        $options[Option\OriginalTransaction::ORIGINAL_TRANSACTION] = $sourceTransaction->getReference();

        $paymentTransaction
            ->setRequest($options)
            ->setAction(self::CAPTURE);

        return $this->executePaymentAction(self::CAPTURE, $paymentTransaction);
    }

    /**
     * @param string $action
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function executePaymentAction($action, PaymentTransaction $paymentTransaction)
    {
        $response = $this->gateway->request(
            $this->getTransactionType($action),
            $this->combineOptions((array)$paymentTransaction->getRequest())
        );

        $paymentTransaction
            ->setSuccessful($response->isSuccessful())
            ->setActive($response->isSuccessful())
            ->setReference($response->getReference())
            ->setResponse($response->getData());

        if (!$response->isSuccessful() && $this->logger) {
            $this->logger->critical($response->getMessage());
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
    protected function getPaymentOptions(PaymentTransaction $paymentTransaction)
    {
        list($dataDescriptor, $dataValue) = $this->extractOpaqueCreditCardCredentials($paymentTransaction);

        return [
            Option\DataDescriptor::DATA_DESCRIPTOR => $dataDescriptor,
            Option\DataValue::DATA_VALUE => $dataValue,
            //TODO: consider using RoundingServiceInterface
            Option\Amount::AMOUNT => round($paymentTransaction->getAmount(), self::AMOUNT_PRECISION),
            Option\Currency::CURRENCY => $paymentTransaction->getCurrency(),
        ];
    }

    /**
     * @param array $options
     * @return array
     */
    protected function combineOptions(array $options = [])
    {
        return array_replace(
            [
                Option\ApiLoginId::API_LOGIN_ID => $this->config->getApiLoginId(),
                Option\TransactionKey::TRANSACTION_KEY => $this->config->getTransactionKey(),
            ],
            $options
        );
    }

    /**
     * @param string $action
     * @return string
     */
    protected function getTransactionType($action)
    {
        switch ($action) {
            case self::CAPTURE:
                return Option\Transaction::CAPTURE;
                break;
            case self::CHARGE:
                return Option\Transaction::CHARGE;
                break;
            case self::AUTHORIZE:
                return Option\Transaction::AUTHORIZE;
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported action "%s"', $action));
        }
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function extractOpaqueCreditCardCredentials(PaymentTransaction $paymentTransaction)
    {
        $sourceTransaction = $paymentTransaction->getSourcePaymentTransaction();
        if (!$sourceTransaction) {
            throw new \LogicException('Cant extract required opaque credit card credentials from transaction');
        }
        $options = $sourceTransaction->getTransactionOptions();
        if (empty($options['additionalData']) ||
            strpos($options['additionalData'], self::API_CREDENTIALS_DELIMITER) === false
        ) {
            throw new \LogicException('Cant extract required opaque credit card credentials from transaction');
        }
        return explode(self::API_CREDENTIALS_DELIMITER, $options['additionalData']);
    }
}
