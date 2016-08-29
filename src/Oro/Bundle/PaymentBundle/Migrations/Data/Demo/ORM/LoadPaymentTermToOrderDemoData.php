<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Action\PurchaseAction;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadPaymentTermToOrderDemoData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\PaymentBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData',
            'Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM\LoadOrderDemoData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $doctrine = $this->container->get('doctrine');
        /** @var Order[] $ordersAll */
        $ordersAll = $doctrine->getRepository('OroOrderBundle:Order')->findAll();

        /** @var PurchaseAction $purchaseAction */
        $purchaseAction = $this->container->get('orob2b_payment.action.purchase');

        foreach ($ordersAll as $order) {
            $purchaseAction->initialize(
                [
                    'object' => $order,
                    'paymentMethod' => "payment_term",
                    'currency' => $order->getCurrency(),
                    'amount' => $order->getTotal()
                ]
            );
            // add payment transaction to each demo order
            $purchaseAction->execute($order);
        }
    }
}
