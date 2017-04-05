<?php

namespace Oro\Bundle\ApruveBundle\Method\Factory;

use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\PaymentAction\Executor\PaymentActionExecutor;

class ApruvePaymentMethodFactory implements ApruvePaymentMethodFactoryInterface
{
    /**
     * @var PaymentActionExecutor
     */
    private $paymentActionExecutor;

    /**
     * @param PaymentActionExecutor $paymentActionExecutor
     */
    public function __construct(PaymentActionExecutor $paymentActionExecutor)
    {
        $this->paymentActionExecutor = $paymentActionExecutor;
    }

    /**
     * {@inheritdoc}
     */
    public function create(ApruveConfigInterface $config)
    {
        return new ApruvePaymentMethod($config, $this->paymentActionExecutor);
    }
}
