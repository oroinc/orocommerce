<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;

class LoadOrderWithLineItemsAndTaxes extends LoadOrderItems
{
    public function load(ObjectManager $manager)
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $order->setCustomer($this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME));

        parent::load($manager);
    }

    public function getDependencies()
    {
        $dependencies = parent::getDependencies();
        $dependencies[] = LoadTaxRules::class;

        return $dependencies;
    }
}
