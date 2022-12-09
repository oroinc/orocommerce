<?php

namespace Oro\Bundle\PaymentBundle\Condition;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

/**
 * Oro action condition for checking canceled payment transaction
 */
class PaymentTransactionWasRefunded extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'payment_transaction_was_refunded';

    /** @var PaymentTransaction */
    protected $transaction;

    protected PaymentTransactionRepository $transactionRepository;

    public function __construct(PaymentTransactionRepository $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    public function initialize(array $options)
    {
        if (array_key_exists('transaction', $options)) {
            $this->transaction = $options['transaction'];
        }

        if (!$this->transaction) {
            throw new InvalidArgumentException('Missing "transaction" option');
        }

        return $this;
    }

    protected function isConditionAllowed($context)
    {
        /** @var PaymentTransaction $transaction */
        $transaction = $this->resolveValue($context, $this->transaction);

        $refundTransactions = $this->transactionRepository->findSuccessfulRelatedTransactionsByAction(
            $transaction,
            PaymentMethodInterface::REFUND
        );

        return !empty($refundTransactions);
    }

    public function getName()
    {
        return self::NAME;
    }
}
