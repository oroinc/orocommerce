<?php

namespace Oro\Bundle\ApruveBundle\Method\PaymentAction;

use Oro\Bundle\ApruveBundle\Apruve\Factory\Shipment\ApruveShipmentFromPaymentContextFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Factory\Shipment\ApruveShipmentFromResponseFactoryInterface;
use Oro\Bundle\ApruveBundle\Client\Factory\Config\ApruveConfigRestClientFactoryInterface;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequestInterface;
use Oro\Bundle\ApruveBundle\Client\Request\Invoice\Factory\CreateInvoiceRequestFactoryInterface;
use Oro\Bundle\ApruveBundle\Client\Request\Shipment\Factory\CreateShipmentRequestFactoryInterface;
use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\PaymentBundle\Context\Factory\TransactionPaymentContextFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class ShipmentPaymentAction extends AbstractPaymentAction implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const NAME = 'shipment';

    /**
     * @internal
     */
    const TRANSACTION_ACTIVE = false;

    /**
     * @var ApruveShipmentFromPaymentContextFactoryInterface
     */
    private $apruveShipmentFromPaymentContextFactory;

    /**
     * @var ApruveShipmentFromResponseFactoryInterface
     */
    private $apruveShipmentFromResponseFactory;

    /**
     * @var CreateInvoiceRequestFactoryInterface
     */
    private $createShipmentRequestFactory;

    /**
     * @var ApruveConfigRestClientFactoryInterface
     */
    private $apruveConfigRestClientFactory;

    /**
     * @param TransactionPaymentContextFactoryInterface        $paymentContextFactory
     * @param ApruveShipmentFromPaymentContextFactoryInterface $apruveShipmentFromPaymentContextFactory
     * @param ApruveShipmentFromResponseFactoryInterface       $apruveShipmentFromResponseFactory
     * @param ApruveConfigRestClientFactoryInterface           $apruveConfigRestClientFactory
     * @param CreateShipmentRequestFactoryInterface            $createShipmentRequestFactory
     */
    public function __construct(
        TransactionPaymentContextFactoryInterface $paymentContextFactory,
        ApruveShipmentFromPaymentContextFactoryInterface $apruveShipmentFromPaymentContextFactory,
        ApruveShipmentFromResponseFactoryInterface $apruveShipmentFromResponseFactory,
        ApruveConfigRestClientFactoryInterface $apruveConfigRestClientFactory,
        CreateShipmentRequestFactoryInterface $createShipmentRequestFactory
    ) {
        parent::__construct($paymentContextFactory);

        $this->apruveShipmentFromPaymentContextFactory = $apruveShipmentFromPaymentContextFactory;
        $this->apruveShipmentFromResponseFactory = $apruveShipmentFromResponseFactory;
        $this->apruveConfigRestClientFactory = $apruveConfigRestClientFactory;
        $this->createShipmentRequestFactory = $createShipmentRequestFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(ApruveConfigInterface $apruveConfig, PaymentTransaction $paymentTransaction)
    {
        $paymentContext = $this->paymentContextFactory->create($paymentTransaction);
        if ($paymentContext === null) {
            return $this->returnAndLogPaymentContextError($paymentTransaction->getId());
        }

        $apruveInvoiceId = $this->getApruveInvoiceId($paymentTransaction);
        if ($apruveInvoiceId === null) {
            return $this->returnAndLogApruveInvoiceIdError($paymentTransaction->getId());
        }

        $apruveShipmentRequest = $this->createApruveShipmentRequest($paymentContext, $apruveInvoiceId);
        $apruveClient = $this->apruveConfigRestClientFactory->create($apruveConfig);

        try {
            $restResponse = $apruveClient->execute($apruveShipmentRequest);
            $transactionResult = $restResponse->isSuccessful();

            $paymentTransaction
                ->setResponse($restResponse->json())
                ->setReference($this->getApruveShipmentId($restResponse));

            if (!$transactionResult) {
                return $this->returnAndLogApruveError($restResponse);
            }
        } catch (RestException $exception) {
            $transactionResult = false;

            return $this->returnAndLogRestExceptionError($exception);
        } finally {
            $paymentTransaction
                ->setRequest($apruveShipmentRequest->toArray())
                ->setSuccessful($transactionResult)
                ->setActive(self::TRANSACTION_ACTIVE);
        }

        return $this->returnSuccess();
    }

    /**
     * Fetch Apruve order id from reference of source transaction.
     *
     * It is assumed that source transaction holds Apruve invoice id in reference property.
     *
     * @param PaymentTransaction $paymentTransaction
     *
     * @return string|null
     */
    private function getApruveInvoiceId(PaymentTransaction $paymentTransaction)
    {
        $apruveInvoiceId = null;
        $sourceTransaction = $paymentTransaction->getSourcePaymentTransaction();

        if ($sourceTransaction instanceof PaymentTransaction) {
            if ($sourceTransaction->getAction() === ApruvePaymentMethod::INVOICE) {
                $apruveInvoiceId = $sourceTransaction->getReference();
            }
        }

        return $apruveInvoiceId;
    }

    /**
     * @param RestResponseInterface $restResponse
     *
     * @return string|null
     *
     * @throws \Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException
     */
    private function getApruveShipmentId(RestResponseInterface $restResponse)
    {
        $createdApruveShipment = $this->apruveShipmentFromResponseFactory
            ->createFromResponse($restResponse);

        return $createdApruveShipment->getId();
    }

    /**
     * @param PaymentContextInterface $paymentContext
     * @param string                  $apruveShipmentId
     *
     * @return ApruveRequestInterface
     */
    private function createApruveShipmentRequest(PaymentContextInterface $paymentContext, $apruveShipmentId)
    {
        $apruveShipment = $this->apruveShipmentFromPaymentContextFactory
            ->createFromPaymentContext($paymentContext);
        $apruveShipmentRequest = $this->createShipmentRequestFactory
            ->create($apruveShipment, $apruveShipmentId);

        return $apruveShipmentRequest;
    }

    /**
     * @param int $transactionId
     *
     * @return array
     */
    private function returnAndLogPaymentContextError($transactionId)
    {
        if ($this->logger) {
            $msg = sprintf(
                'Payment context was not created from given transaction (%d)',
                $transactionId
            );
            $this->logger->error($msg);
        }

        return $this->returnError();
    }

    /**
     * @param int $transactionId
     *
     * @return array
     */
    private function returnAndLogApruveInvoiceIdError($transactionId)
    {
        if ($this->logger) {
            $msg = sprintf(
                'Could not fetch Apruve Invoice ID from transaction %d',
                $transactionId
            );
            $this->logger->error($msg);
        }

        return $this->returnError();
    }

    /**
     * @param RestResponseInterface $restResponse
     *
     * @return array
     */
    private function returnAndLogApruveError(RestResponseInterface $restResponse)
    {
        if ($this->logger) {
            $msg = sprintf(
                'Request to Apruve was unsuccessful (%d %s): %s',
                $restResponse->getStatusCode(),
                $restResponse->getRequestUrl(),
                $restResponse->getBodyAsString()
            );
            $this->logger->error($msg);
        }

        return $this->returnError();
    }

    /**
     * @param RestException $exception
     *
     * @return array
     */
    private function returnAndLogRestExceptionError(RestException $exception)
    {
        if ($this->logger) {
            $this->logger->error($exception->getMessage());
        }

        return $this->returnError();
    }

    /**
     * @return array
     */
    private function returnError()
    {
        return [
            'successful' => false,
            'message' => 'oro.apruve.payment_transaction.shipment.result.error',
        ];
    }

    /**
     * @return array
     */
    private function returnSuccess()
    {
        return ['successful' => true];
    }
}
