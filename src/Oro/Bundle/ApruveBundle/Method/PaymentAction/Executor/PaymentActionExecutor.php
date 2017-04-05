<?php

namespace Oro\Bundle\ApruveBundle\Method\PaymentAction\Executor;

use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\PaymentAction\PaymentActionInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class PaymentActionExecutor implements PaymentActionExecutorInterface
{
    /** @var PaymentActionInterface[] */
    protected $actions = [];

    /**
     * {@inheritdoc}
     */
    public function addPaymentAction(PaymentActionInterface $paymentAction)
    {
        $this->actions[$paymentAction->getName()] = $paymentAction;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($action, ApruveConfigInterface $apruveConfig, PaymentTransaction $paymentTransaction)
    {
        $paymentAction = $this->getPaymentAction($action);

        return $paymentAction->execute($apruveConfig, $paymentTransaction);
    }

    /**
     * {@inheritdoc}
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
