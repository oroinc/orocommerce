<?php

namespace Oro\Bundle\PaymentBundle\Condition;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

class PaymentTransactionWasCharged extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'payment_transaction_was_charged';

    /**
     * @var PaymentTransaction
     */
    private $transaction;

    /**
     * @var PaymentTransactionRepository
     */
    private $transactionRepository;

    public function __construct(PaymentTransactionRepository $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
    protected function isConditionAllowed($context)
    {
        /** @var PaymentTransaction $transaction */
        $transaction = $this->resolveValue($context, $this->transaction);

        $captureTransactions = $this->transactionRepository->findSuccessfulRelatedTransactionsByAction(
            $transaction,
            PaymentMethodInterface::CAPTURE
        );

        return !empty($captureTransactions);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
