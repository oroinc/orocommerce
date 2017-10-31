<?php

namespace Oro\Bundle\CheckoutBundle\Model\Updater;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Duplicator\DuplicatorFactory;
use Oro\Component\Duplicator\DuplicatorInterface;

class CheckoutAddressUpdater extends AbstractCheckoutUpdater
{
    const BILLING_ADDRESS_ATTRIBUTE = 'billing_address';
    const SHIPPING_ADDRESS_ATTRIBUTE = 'shipping_address';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var DuplicatorFactory */
    protected $duplicatorFactory;

    /** @var array */
    protected $duplicatorSettings = [];

    /** @var DuplicatorInterface */
    protected $duplicator;

    /**
     * @param ManagerRegistry $registry
     * @param DuplicatorFactory $duplicatorFactory
     * @param array $duplicatorSettings
     */
    public function __construct(
        ManagerRegistry $registry,
        DuplicatorFactory $duplicatorFactory,
        array $duplicatorSettings
    ) {
        $this->registry = $registry;
        $this->duplicatorFactory = $duplicatorFactory;
        $this->duplicatorSettings = $duplicatorSettings;
    }

    /**
     * {@inheritDoc}
     *
     * @param Order $source
     */
    public function update(WorkflowDefinition $workflow, WorkflowData $data, $source)
    {
        $manager = $this->registry->getManagerForClass(OrderAddress::class);
        if (!$manager) {
            return;
        }

        $billingAddress = $source->getBillingAddress();
        if ($billingAddress) {
            $newBillingAddress = $this->duplicate($billingAddress);

            $manager->persist($newBillingAddress);

            $data->set(self::BILLING_ADDRESS_ATTRIBUTE, $newBillingAddress);
        }

        $shippingAddress = $source->getShippingAddress();
        if ($shippingAddress) {
            $newShippingAddress = $this->duplicate($shippingAddress);

            $manager->persist($newShippingAddress);

            $data->set(self::SHIPPING_ADDRESS_ATTRIBUTE, $newShippingAddress);
        }
    }

    /**
     * @param OrderAddress $address
     * @return OrderAddress
     */
    protected function duplicate(OrderAddress $address)
    {
        $duplicator = $this->getDuplicator();

        return $duplicator->duplicate($address, $this->duplicatorSettings);
    }

    /**
     * @return DuplicatorInterface
     */
    private function getDuplicator()
    {
        if (!$this->duplicator) {
            $this->duplicator = $this->duplicatorFactory->create();
        }

        return $this->duplicator;
    }
}
