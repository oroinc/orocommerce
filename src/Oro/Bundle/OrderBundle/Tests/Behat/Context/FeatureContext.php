<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\WarehouseBundle\Entity\Warehouse;
use Oro\Bundle\WarehouseBundle\SystemConfig\WarehouseConfig;

class FeatureContext extends OroFeatureContext implements KernelAwareContext
{
    use KernelDictionary;

    /**
     * This context method can change order createdAt field,so we can tests time related features
     *
     * Example: Given there is an order "OldOrder" created "-15 days"
     *
     * @Given /^there is an order "(?P<orderIdentifier>(?:[^"]|\\")*)" created "(?P<createdAt>(?:[^"]|\\")*)"$/
     */
    public function thereAnOrderCreatedAt($orderIdentifier, $createdAt)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()
            ->get('oro_entity.doctrine_helper')->getEntityManager(Order::class);

        $order = $em->getRepository(Order::class)
            ->findOneBy(['identifier' => $orderIdentifier]);

        /** @var Order $order */
        if ($order) {
            $order->setCreatedAt(new \DateTime($createdAt));
            $em->persist($order);
            $em->flush();
        } else {
            throw new EntityNotFoundException(sprintf('Order with identifier "%s" not found', $orderIdentifier));
        }
    }
}
