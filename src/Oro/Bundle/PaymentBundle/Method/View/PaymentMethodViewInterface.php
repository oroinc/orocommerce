<?php

namespace Oro\Bundle\PaymentBundle\Method\View;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

/**
 * Defines the contract for payment method view representations.
 *
 * Implementations provide display information for payment methods including labels,
 * template blocks, and context-specific options for rendering in the user interface.
 */
interface PaymentMethodViewInterface
{
    /**
     * @param PaymentContextInterface $context
     * @return array
     */
    public function getOptions(PaymentContextInterface $context);

    /**
     * @return string
     */
    public function getBlock();

    /**
     * @return string
     */
    public function getLabel();

    /**
     * @return string
     */
    public function getAdminLabel();

    /**
     * @return string
     */
    public function getShortLabel();

    /**
     * @return string
     */
    public function getPaymentMethodIdentifier();
}
