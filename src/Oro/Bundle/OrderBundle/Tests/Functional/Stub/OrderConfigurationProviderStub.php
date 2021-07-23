<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Stub;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProvider;

class OrderConfigurationProviderStub extends OrderConfigurationProvider
{
    /** @var callable|null */
    private $getNewOrderInternalStatusCallback;

    /** @var callable|null */
    private $isAutomaticCancellationEnabledCallback;

    /** @var callable|null */
    private $getApplicableInternalStatusesCallback;

    /** @var callable|null */
    private $getTargetInternalStatusCallback;

    public function setGetNewOrderInternalStatusCallback(?callable $getNewOrderInternalStatusCallback): void
    {
        $this->getNewOrderInternalStatusCallback = $getNewOrderInternalStatusCallback;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewOrderInternalStatus(Order $order)
    {
        return $this->getNewOrderInternalStatusCallback
            ? ($this->getNewOrderInternalStatusCallback)($order)
            : parent::getNewOrderInternalStatus($order);
    }

    public function setIsAutomaticCancellationEnabledCallback(?callable $isAutomaticCancellationEnabledCallback): void
    {
        $this->isAutomaticCancellationEnabledCallback = $isAutomaticCancellationEnabledCallback;
    }

    /**
     * {@inheritdoc}
     */
    public function isAutomaticCancellationEnabled($identifier = null)
    {
        return $this->isAutomaticCancellationEnabledCallback
            ? ($this->isAutomaticCancellationEnabledCallback)($identifier)
            : parent::isAutomaticCancellationEnabled($identifier);
    }

    public function setGetApplicableInternalStatusesCallback(?callable $getApplicableInternalStatusesCallback): void
    {
        $this->getApplicableInternalStatusesCallback = $getApplicableInternalStatusesCallback;
    }

    /**
     * {@inheritdoc}
     */
    public function getApplicableInternalStatuses($identifier = null)
    {
        return $this->getApplicableInternalStatusesCallback
            ? ($this->getApplicableInternalStatusesCallback)($identifier)
            : parent::getApplicableInternalStatuses($identifier);
    }

    public function setGetTargetInternalStatusCallback(?callable $getTargetInternalStatusCallback): void
    {
        $this->getTargetInternalStatusCallback = $getTargetInternalStatusCallback;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetInternalStatus($identifier = null)
    {
        return $this->getTargetInternalStatusCallback
            ? ($this->getTargetInternalStatusCallback)($identifier)
            : parent::getTargetInternalStatus($identifier);
    }
}
