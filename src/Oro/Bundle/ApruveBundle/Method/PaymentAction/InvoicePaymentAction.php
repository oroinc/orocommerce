<?php

namespace Oro\Bundle\ApruveBundle\Method\PaymentAction;

use Oro\Bundle\ApruveBundle\Apruve\Factory\Invoice\ApruveInvoiceFromPaymentContextFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Factory\Invoice\ApruveInvoiceFromResponseFactoryInterface;
use Oro\Bundle\ApruveBundle\Client\Factory\Config\ApruveConfigRestClientFactoryInterface;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequestInterface;
use Oro\Bundle\ApruveBundle\Client\Request\Invoice\Factory\CreateInvoiceRequestFactoryInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\PaymentBundle\Context\Factory\TransactionPaymentContextFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class InvoicePaymentAction extends AbstractPaymentAction implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const NAME = PaymentMethodInterface::INVOICE;

    /**
     * @var ApruveInvoiceFromPaymentContextFactoryInterface
     */
    private $apruveInvoiceFromPaymentContextFactory;

    /**
     * @var CreateInvoiceRequestFactoryInterface
     */
    private $createInvoiceRequestFactory;

    /**
     * @var ApruveConfigRestClientFactoryInterface
     */
    private $apruveConfigRestClientFactory;

    /**
     * @var ApruveInvoiceFromResponseFactoryInterface
     */
    private $apruveInvoiceFromResponseFactory;

    /**
     * @param TransactionPaymentContextFactoryInterface       $paymentContextFactory
     * @param ApruveInvoiceFromPaymentContextFactoryInterface $apruveInvoiceFromPaymentContextFactory
     * @param ApruveInvoiceFromResponseFactoryInterface       $apruveInvoiceFromResponseFactory
     * @param ApruveConfigRestClientFactoryInterface          $apruveConfigRestClientFactory
     * @param CreateInvoiceRequestFactoryInterface            $createInvoiceRequestFactory
     */
    public function __construct(
        TransactionPaymentContextFactoryInterface $paymentContextFactory,
        ApruveInvoiceFromPaymentContextFactoryInterface $apruveInvoiceFromPaymentContextFactory,
        ApruveInvoiceFromResponseFactoryInterface $apruveInvoiceFromResponseFactory,
        ApruveConfigRestClientFactoryInterface $apruveConfigRestClientFactory,
        CreateInvoiceRequestFactoryInterface $createInvoiceRequestFactory
    ) {
        parent::__construct($paymentContextFactory);

        $this->apruveInvoiceFromPaymentContextFactory = $apruveInvoiceFromPaymentContextFactory;
        $this->apruveInvoiceFromResponseFactory = $apruveInvoiceFromResponseFactory;
        $this->apruveConfigRestClientFactory = $apruveConfigRestClientFactory;
        $this->createInvoiceRequestFactory = $createInvoiceRequestFactory;
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

        $apruveOrderId = $this->getApruveOrderId($paymentTransaction);
        if ($apruveOrderId === null) {
            return $this->returnAndLogApruveOrderIdError($paymentTransaction->getId());
        }

        $apruveInvoiceRequest = $this->createApruveInvoiceRequest($paymentContext, $apruveOrderId);
        $apruveClient = $this->apruveConfigRestClientFactory->create($apruveConfig);

        try {
            $restResponse = $apruveClient->execute($apruveInvoiceRequest);
            $transactionResult = $restResponse->isSuccessful();

            $paymentTransaction
                ->setResponse($restResponse->json())
                ->setReference($this->getApruveInvoiceId($restResponse));

            if (!$transactionResult) {
                return $this->returnAndLogApruveError($restResponse);
            }
        } catch (RestException $exception) {
            $transactionResult = false;

            return $this->returnAndLogRestExceptionError($exception);
        } finally {
            $paymentTransaction
                ->setRequest($apruveInvoiceRequest->toArray())
                ->setSuccessful($transactionResult)
                ->setActive($transactionResult);
        }

        return $this->returnSuccess();
    }

    /**
     * Fetch Apruve order id from reference of source transaction.
     *
     * It is assumed that source transaction holds Apruve order id in reference property.
     *
     * @param PaymentTransaction $paymentTransaction
     *
     * @return string|null
     */
    private function getApruveOrderId(PaymentTransaction $paymentTransaction)
    {
        $apruveOrderId = null;
        $sourceTransaction = $paymentTransaction->getSourcePaymentTransaction();

        if ($sourceTransaction instanceof PaymentTransaction) {
            if ($sourceTransaction->getAction() === PaymentMethodInterface::AUTHORIZE) {
                $apruveOrderId = $sourceTransaction->getReference();
            }
        }

        return $apruveOrderId;
    }

    /**
     * @param RestResponseInterface $restResponse
     *
     * @return string|null
     *
     * @throws \Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException
     */
    private function getApruveInvoiceId(RestResponseInterface $restResponse)
    {
        $createdApruveInvoice = $this->apruveInvoiceFromResponseFactory
            ->createFromResponse($restResponse);

        return $createdApruveInvoice->getId();
    }

    /**
     * @param PaymentContextInterface $paymentContext
     * @param string                  $apruveOrderId
     *
     * @return ApruveRequestInterface
     */
    private function createApruveInvoiceRequest(PaymentContextInterface $paymentContext, $apruveOrderId)
    {
        $apruveInvoice = $this->apruveInvoiceFromPaymentContextFactory
            ->createFromPaymentContext($paymentContext);
        $apruveInvoiceRequest = $this->createInvoiceRequestFactory
            ->create($apruveInvoice, $apruveOrderId);

        return $apruveInvoiceRequest;
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
    private function returnAndLogApruveOrderIdError($transactionId)
    {
        if ($this->logger) {
            $msg = sprintf(
                'Could not fetch Apruve Order ID from transaction %d',
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
            'message' => 'oro.apruve.payment_transaction.invoice.result.error',
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
