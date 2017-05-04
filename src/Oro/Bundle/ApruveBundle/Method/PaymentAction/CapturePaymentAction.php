<?php

namespace Oro\Bundle\ApruveBundle\Method\PaymentAction;

use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\PaymentAction\Executor\PaymentActionExecutorInterface;
use Oro\Bundle\PaymentBundle\Context\Factory\TransactionPaymentContextFactoryInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class CapturePaymentAction extends AbstractPaymentAction
{
    const NAME = 'capture';

    /**
     * @var PaymentActionExecutorInterface
     */
    private $paymentActionExecutor;

    /**
     * @param TransactionPaymentContextFactoryInterface $paymentContextFactory
     * @param PaymentActionExecutorInterface            $paymentActionExecutor
     */
    public function __construct(
        TransactionPaymentContextFactoryInterface $paymentContextFactory,
        PaymentActionExecutorInterface $paymentActionExecutor
    ) {
        parent::__construct($paymentContextFactory);

        $this->paymentActionExecutor = $paymentActionExecutor;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(ApruveConfigInterface $apruveConfig, PaymentTransaction $paymentTransaction)
    {
        $paymentTransaction->setAction(ApruvePaymentMethod::INVOICE);

        return $this->paymentActionExecutor
            ->execute(ApruvePaymentMethod::INVOICE, $apruveConfig, $paymentTransaction);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
