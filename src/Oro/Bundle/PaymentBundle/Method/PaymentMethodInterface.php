<?php

namespace Oro\Bundle\PaymentBundle\Method;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * Payment Method with generic execute method
 */
interface PaymentMethodInterface
{
    /**
     * Validate with zero amount, e.g reserve
     */
    public const VALIDATE = 'validate';

    /**
     * Authorize with non-zero amount, e.g activate, initiate
     */
    public const AUTHORIZE = 'authorize';

    /**
     * Re-authorize the existing authorization hold.
     */
    public const RE_AUTHORIZE = 're_authorize';

    /**
     * Capture authorized amount, e.g complete
     */
    public const CAPTURE = 'capture';

    /**
     * Capture non-zero amount
     */
    public const CHARGE = 'charge';

    /**
     * Send invoice
     */
    public const INVOICE = 'invoice';

    /**
     * Decorate actions - charge, authorize, authorize and capture
     */
    public const PURCHASE = 'purchase';

    /**
     * Represents pending transaction that requires update
     */
    public const PENDING = 'pending';

    /**
     * Cancel authorized or captured amount, e.g void, reversal
     */
    public const CANCEL = 'cancel';

    /**
     * Refund captured amount
     */
    public const REFUND = 'refund';

    /**
     * @param string $action
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    public function execute($action, PaymentTransaction $paymentTransaction);

    /**
     * @return string
     */
    public function getIdentifier();

    /**
     * @param PaymentContextInterface $context
     * @return bool
     */
    public function isApplicable(PaymentContextInterface $context);

    /**
     * @param string $actionName
     * @return bool
     */
    public function supports($actionName);
}
