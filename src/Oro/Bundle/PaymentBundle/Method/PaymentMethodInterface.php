<?php

namespace Oro\Bundle\PaymentBundle\Method;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

interface PaymentMethodInterface
{
    const AUTHORIZE = 'authorize';
    const CHARGE = 'charge';
    const VALIDATE = 'validate';
    const CAPTURE = 'capture';

    /**
     * Action to wrap action combination - charge, authorize, authorize and capture
     */
    const PURCHASE = 'purchase';

    /**
     * @param string $actionName
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    public function execute($actionName, PaymentTransaction $paymentTransaction);

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
