<?php
declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Method;

use Oro\Bundle\PaymentBundle\Method\Action\CaptureActionInterface;
use Oro\Bundle\PaymentBundle\Method\Action\PurchaseActionInterface;

/**
 * Payment method that allows for postponed capture based on initial purchase transaction
 */
interface PaymentMethodWithPostponedCaptureInterface extends
    PaymentMethodInterface,
    PurchaseActionInterface,
    CaptureActionInterface
{
}
