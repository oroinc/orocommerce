<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfiguration;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM\Trait\OrderLineItemsDemoDataTrait;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads demo data for order line items.
 */
class LoadCustomerOrderLineItemsDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait, OrderLineItemsDemoDataTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCustomerOrderDemoData::class,
            LoadProductDemoData::class,
            LoadProductUnitPrecisionDemoData::class,
        ];
    }

    /**
     * @param EntityManagerInterface $manager
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $totalHelper = $this->getTotalHelper();
        $orders = $manager->getRepository(Order::class)->findAll();

        /** @var Order $order */
        foreach ($orders as $order) {
            if ($order->getLineItems()->count()) {
                continue;
            }
            $order->setCurrency(CurrencyConfiguration::DEFAULT_CURRENCY);
            $productsCount = random_int(1, 5);

            for ($i = 0; $i < $productsCount; $i++) {
                $lineItem = $this->getOrderLineItem($manager);
                $order->addLineItem($lineItem);
            }
            $totalHelper->fill($order);

            $manager->persist($order);
        }

        $manager->flush();
    }

    /**
     * @return TotalHelper
     */
    protected function getTotalHelper()
    {
        return $this->container->get('oro_order.order.total.total_helper');
    }
}
