<?php

namespace Oro\Bundle\ApruveBundle\Handler\Invoice;

use Oro\Bundle\ApruveBundle\Handler\Exceptions\InvalidEventException;
use Oro\Bundle\ApruveBundle\Handler\Exceptions\SourceTransactionNotFoundException;
use Oro\Bundle\ApruveBundle\Handler\Exceptions\TransactionAlreadyExistsException;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

class InvoiceClosedWebhookEventHandler implements InvoiceClosedWebhookEventHandlerInterface
{
    /**
     * @internal
     */
    const TRANSACTION_ACTION = PaymentMethodInterface::CAPTURE;

    /**
     * @var PaymentTransactionRepository
     */
    private $paymentTransactionRepository;

    /**
     * @var PaymentTransactionProvider
     */
    private $paymentTransactionProvider;

    /**
     * @param PaymentTransactionRepository $paymentTransactionRepository
     * @param PaymentTransactionProvider   $paymentTransactionProvider
     */
    public function __construct(
        PaymentTransactionRepository $paymentTransactionRepository,
        PaymentTransactionProvider $paymentTransactionProvider
    ) {
        $this->paymentTransactionRepository = $paymentTransactionRepository;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(PaymentMethodInterface $paymentMethod, array $eventBody)
    {
        $apruveInvoiceId = $this->getEntityIdFromEventBody($eventBody);

        $sourcePaymentTransaction = $this->getSourcePaymentTransaction(
            $apruveInvoiceId,
            $paymentMethod->getIdentifier()
        );

        $this->checkAlreadyExistingTransaction($sourcePaymentTransaction);

        $this->createInvoiceClosedTransaction($paymentMethod, $sourcePaymentTransaction, $eventBody);
    }

    /**
     * @param PaymentMethodInterface $paymentMethod
     * @param PaymentTransaction     $sourcePaymentTransaction
     * @param array                  $apruveResponse
     */
    private function createInvoiceClosedTransaction(
        PaymentMethodInterface $paymentMethod,
        PaymentTransaction $sourcePaymentTransaction,
        array $apruveResponse
    ) {
        $newPaymentTransaction = $this->paymentTransactionProvider->createPaymentTransactionByParentTransaction(
            self::TRANSACTION_ACTION,
            $sourcePaymentTransaction
        );

        $newPaymentTransaction->setResponse($apruveResponse);

        $paymentMethod->execute(self::TRANSACTION_ACTION, $newPaymentTransaction);

        $this->paymentTransactionProvider->savePaymentTransaction($newPaymentTransaction);
    }

    /**
     * @param array $eventBody
     *
     * @return string
     */
    private function getEntityIdFromEventBody(array $eventBody)
    {
        if (array_key_exists('entity', $eventBody) && array_key_exists('id', $eventBody['entity'])) {
            return $eventBody['entity']['id'];
        }

        throw new InvalidEventException('Invoice id was not found in event body: '.json_encode($eventBody));
    }

    /**
     * @param string $apruveInvoiceId
     * @param string $paymentMethodIdentifier
     *
     * @return PaymentTransaction|object
     */
    private function getSourcePaymentTransaction($apruveInvoiceId, $paymentMethodIdentifier)
    {
        $criteria = [
            'reference' => $apruveInvoiceId,
            'action' => PaymentMethodInterface::INVOICE,
            'paymentMethod' => $paymentMethodIdentifier,
        ];

        $sourcePaymentTransaction = $this->paymentTransactionRepository->findOneBy($criteria);

        if ($sourcePaymentTransaction !== null) {
            return $sourcePaymentTransaction;
        }

        throw new SourceTransactionNotFoundException(
            'Source transaction was not found for criteria: '.json_encode($criteria)
        );
    }

    /**
     * @param PaymentTransaction $sourcePaymentTransaction
     */
    private function checkAlreadyExistingTransaction(PaymentTransaction $sourcePaymentTransaction)
    {
        $criteria = [
            'reference' => $sourcePaymentTransaction->getReference(),
            'action' => self::TRANSACTION_ACTION,
            'paymentMethod' => $sourcePaymentTransaction->getPaymentMethod(),
        ];

        $existingPaymentTransaction = $this->paymentTransactionRepository->findOneBy($criteria);

        if ($existingPaymentTransaction !== null) {
            throw new TransactionAlreadyExistsException(
                sprintf(
                    'Capture transaction for invoiced transaction with id: %s, already exists. Criteria: %s',
                    $sourcePaymentTransaction->getId(),
                    json_encode($criteria)
                )
            );
        }
    }
}
