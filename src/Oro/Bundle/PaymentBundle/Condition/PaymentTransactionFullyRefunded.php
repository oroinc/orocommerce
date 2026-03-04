<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Condition;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionAmountAvailableToRefundProvider;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Action to check if captured transaction was refunded in full amount or partially.
 */
class PaymentTransactionFullyRefunded extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    public const NAME = 'payment_transaction_was_fully_refunded';

    /** @var PaymentTransaction|PropertyPathInterface */
    protected $transaction;

    public function __construct(
        private readonly PaymentTransactionAmountAvailableToRefundProvider $transactionDataProvider
    ) {
    }

    #[\Override]
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

    #[\Override]
    protected function isConditionAllowed($context)
    {
        /** @var PaymentTransaction $transaction */
        $transaction = $this->resolveValue($context, $this->transaction);
        $availableAmountToRefund = $this->transactionDataProvider->getAvailableAmountToRefund($transaction);

        return !(bool)$availableAmountToRefund;
    }

    #[\Override]
    public function getName()
    {
        return self::NAME;
    }
}
