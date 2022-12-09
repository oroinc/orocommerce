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
    const VALIDATE = 'validate';

    /**
     * Authorize with non-zero amount, e.g activate, initiate
     */
    const AUTHORIZE = 'authorize';

    /**
     * Capture authorized amount, e.g complete
     */
    const CAPTURE = 'capture';

    /**
     * Capture non-zero amount
     */
    const CHARGE = 'charge';

    /**
     * Send invoice
     */
    const INVOICE = 'invoice';

    /**
     * Decorate actions - charge, authorize, authorize and capture
     */
    const PURCHASE = 'purchase';

    /**
     * Represents pending transaction that requires update
     */
    const PENDING = 'pending';

    /**
     * Cancel authorized or captured amount, e.g void, reversal
     */
    const CANCEL = 'cancel';

    /**
     * Refund captured amount
     */
    const REFUND = 'refund';

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
