<?php

namespace Oro\Bundle\InfinitePayBundle\Method;

use Oro\Bundle\InfinitePayBundle\Action\ActionInterface;
use Oro\Bundle\InfinitePayBundle\Action\Registry\ActionRegistryInterface;
use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Method\Provider\OrderProviderInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\ReminderBundle\Exception\MethodNotSupportedException;

class InfinitePay implements PaymentMethodInterface
{
    const ACTIVATE = 'activate';

    /**
     * @var InfinitePayConfigInterface
     */
    protected $config;

    /**
     * @var ActionRegistryInterface
     */
    protected $actionRegistry;

    /**
     * @var OrderProviderInterface
     */
    protected $orderProvider;

    /**
     * @param InfinitePayConfigInterface $config
     * @param ActionRegistryInterface    $actionRegistry
     * @param OrderProviderInterface     $orderProvider
     */
    public function __construct(
        InfinitePayConfigInterface $config,
        ActionRegistryInterface $actionRegistry,
        OrderProviderInterface $orderProvider
    ) {
        $this->config = $config;
        $this->actionRegistry = $actionRegistry;
        $this->orderProvider = $orderProvider;
    }

    /**
     * @param $actionName
     * @param PaymentTransaction $paymentTransaction
     *
     * @return array
     */
    public function execute($actionName, PaymentTransaction $paymentTransaction)
    {
        if (!$this->supports($actionName)) {
            throw new MethodNotSupportedException('InfinityPay implementation does not support action: '.$actionName);
        }

        $entity = $this->orderProvider->getDataObjectFromPaymentTransaction($paymentTransaction);

        $action = $this->getActionExecutorFromActionType($actionName);

        return $action->execute($paymentTransaction, $entity);
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(PaymentContextInterface $context)
    {
        return !empty($context->getCustomer()->getVatId());
    }

    /**
     * @param string $actionName
     *
     * @return bool
     */
    public function supports($actionName)
    {
        return in_array(
            $actionName,
            [self::PURCHASE, self::CAPTURE, self::ACTIVATE],
            true
        );
    }

    /**
     * @param string $actionType
     *
     * @return ActionInterface
     * @throws \Exception
     */
    private function getActionExecutorFromActionType($actionType)
    {
        return $this->actionRegistry->getActionByType($actionType);
    }
}
