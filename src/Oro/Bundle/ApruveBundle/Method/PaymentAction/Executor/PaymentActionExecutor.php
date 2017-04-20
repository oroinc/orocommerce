<?php

namespace Oro\Bundle\ApruveBundle\Method\PaymentAction\Executor;

use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\PaymentAction\PaymentActionInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class PaymentActionExecutor implements PaymentActionExecutorInterface
{
    /**
     * @var PaymentActionInterface[]
     */
    private $actions = [];

    /**
     * {@inheritDoc}
     */
    public function addPaymentAction(PaymentActionInterface $paymentAction)
    {
        $this->actions[$paymentAction->getName()] = $paymentAction;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($action, ApruveConfigInterface $apruveConfig, PaymentTransaction $paymentTransaction)
    {
        $paymentAction = $this->getPaymentAction($action);

        return $paymentAction->execute($apruveConfig, $paymentTransaction);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($name)
    {
        return array_key_exists($name, $this->actions);
    }

    /**
     * @param string $name
     *
     * @return PaymentActionInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function getPaymentAction($name)
    {
        if ($this->supports($name)) {
            return $this->actions[$name];
        }

        throw new \InvalidArgumentException(
            sprintf('Payment action with name "%s" is not supported', $name)
        );
    }
}
