<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method;

use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Gateway;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class AuthorizeNetPaymentMethod implements PaymentMethodInterface
{
    use LoggerAwareTrait;

    const AMOUNT_PRECISION = 2;
    const DATA_DESCRIPTOR = 'dataDescriptor';
    const DATA_VALUE = 'dataValue';

    // Authorize.NET solution id
    const SOLUTION_ID = 'AAA171478';

    /** @var Gateway */
    protected $gateway;

    /** @var AuthorizeNetConfigInterface */
    protected $config;

    /** @var RequestStack */
    protected $requestStack;

    /**
     * @param Gateway $gateway
     * @param AuthorizeNetConfigInterface $config
     * @param RequestStack $requestStack
     */
    public function __construct(Gateway $gateway, AuthorizeNetConfigInterface $config, RequestStack $requestStack)
    {
        $this->gateway = $gateway;
        $this->config = $config;
        $this->requestStack = $requestStack;
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
        $request = $this->requestStack->getCurrentRequest();

        return !$request || $request->isSecure();
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

        return $this->{$action}($paymentTransaction) ?: [];
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function validate(PaymentTransaction $paymentTransaction)
    {
        $paymentTransaction
            ->setAmount(0)
            ->setCurrency('')
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
        $request = array_merge(
            $this->getPaymentOptions($paymentTransaction),
            $this->getOpaqueCredentials($paymentTransaction)
        );

        $paymentTransaction
            ->setRequest($request)
            ->setAction($this->config->getPurchaseAction());

        return $this->executePaymentAction($paymentTransaction->getAction(), $paymentTransaction);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function authorize(PaymentTransaction $paymentTransaction)
    {
        return $this->executePaymentAction(self::AUTHORIZE, $paymentTransaction);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function charge(PaymentTransaction $paymentTransaction)
    {
        return $this->executePaymentAction(self::CHARGE, $paymentTransaction);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function capture(PaymentTransaction $paymentTransaction)
    {
        $authorizeTransaction = $paymentTransaction->getSourcePaymentTransaction();

        if (!$authorizeTransaction) {
            $paymentTransaction
                ->setSuccessful(false)
                ->setActive(false);

            return ['successful' => false];
        }

        $options = $this->getPaymentOptions($authorizeTransaction);
        $options[Option\OriginalTransaction::ORIGINAL_TRANSACTION] = $authorizeTransaction->getReference();

        $paymentTransaction->setRequest($options);
        $result = $this->executePaymentAction(self::CAPTURE, $paymentTransaction);

        $paymentTransaction->setActive(false);
        $authorizeTransaction->setActive(!$paymentTransaction->isSuccessful());

        return $result;
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
            $this->combineOptions($paymentTransaction->getRequest())
        );

        $paymentTransaction
            ->setSuccessful($response->isSuccessful())
            ->setActive($response->isSuccessful())
            ->setReference($response->getReference())
            ->setResponse($response->getData());

        if ($this->logger && !$response->isSuccessful()) {
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
        return [
            Option\Amount::AMOUNT => round($paymentTransaction->getAmount(), self::AMOUNT_PRECISION),
            Option\Currency::CURRENCY => $paymentTransaction->getCurrency(),
        ];
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function getOpaqueCredentials(PaymentTransaction $paymentTransaction)
    {
        $opaqueData = $this->extractOpaqueCreditCardCredentials($paymentTransaction);

        return [
            Option\DataDescriptor::DATA_DESCRIPTOR => $opaqueData[self::DATA_DESCRIPTOR],
            Option\DataValue::DATA_VALUE => $opaqueData[self::DATA_VALUE],
        ];
    }

    /**
     * @param array $options
     * @return array
     */
    protected function combineOptions(array $options = [])
    {
        return array_replace(
            $this->getCredentials(),
            $this->getAdditionalOptions(),
            $options
        );
    }

    /**
     * @return array
     */
    protected function getCredentials()
    {
        return [
            Option\ApiLoginId::API_LOGIN_ID => $this->config->getApiLoginId(),
            Option\TransactionKey::TRANSACTION_KEY => $this->config->getTransactionKey(),
        ];
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
        if (!array_key_exists('additionalData', $options)) {
            throw new \LogicException('Cant extract required opaque credit card credentials from transaction');
        }

        $additionalData = json_decode($options['additionalData'], true);

        if (!is_array($additionalData)) {
            throw new \LogicException('Additional data must be an array');
        }

        $this->assertOpaqueData($additionalData, self::DATA_DESCRIPTOR);
        $this->assertOpaqueData($additionalData, self::DATA_VALUE);

        return array_intersect_key($additionalData, array_flip([self::DATA_DESCRIPTOR, self::DATA_VALUE]));
    }

    /**
     * @param array $additionalData
     * @param string $fieldName
     * @throws \LogicException
     */
    private function assertOpaqueData(array $additionalData, $fieldName)
    {

        if (!array_key_exists($fieldName, $additionalData)) {
            throw new \LogicException(sprintf(
                'Can not find field "%s" in additional data',
                $fieldName
            ));
        }
    }

    /**
     * @return array
     */
    protected function getAdditionalOptions()
    {
        $options = [];
        if (!$this->config->isTestMode()) {
            $options[Option\SolutionId::SOLUTION_ID] = self::SOLUTION_ID;
        }

        return $options;
    }
}
