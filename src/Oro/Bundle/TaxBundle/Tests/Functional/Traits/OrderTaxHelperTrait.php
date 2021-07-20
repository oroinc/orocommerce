<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Traits;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait OrderTaxHelperTrait
{
    /**
     * @param bool $flush
     * @return Order
     */
    protected function createOrder($flush = true)
    {
        /** @var User $orderUser */
        $orderUser = $this->getDoctrine()->getRepository('OroUserBundle:User')->findOneBy([]);
        if (!$orderUser->getOrganization()) {
            $orderUser->setOrganization(
                $this->getDoctrine()->getRepository('OroOrganizationBundle:Organization')->findOneBy([])
            );
        }
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getDoctrine()->getRepository('OroCustomerBundle:CustomerUser')->findOneBy([]);

        $order = new Order();
        $order
            ->setIdentifier(uniqid('identifier_', true))
            ->setOwner($orderUser)
            ->setOrganization($orderUser->getOrganization())
            ->setShipUntil(new \DateTime())
            ->setCurrency('EUR')
            ->setPoNumber('PO_NUM')
            ->setSubtotal('1500')
            ->setCustomer($customerUser->getCustomer())
            ->setCustomerUser($customerUser);

        $em = $this->getOrderEntityManager();
        $em->persist($order);

        if ($flush) {
            $em->flush();
        }

        return $order;
    }

    protected function updateOrder(Order $order)
    {
        $order
            ->setIdentifier(uniqid('identifier_', true))
            ->setSubtotal('1800');

        $em = $this->getOrderEntityManager();
        $em->persist($order);
        $em->flush();
    }

    /**
     * @param Order $order
     * @return TaxValue $taxValue
     */
    protected function getTaxValue(Order $order)
    {
        /** @var TaxValue $taxValue */
        $taxValue = $this->getDoctrine()->getRepository(TaxValue::class)->findOneBy(
            [
                'entityClass' => Order::class,
                'entityId' => $order->getId(),
            ]
        );

        return $taxValue;
    }

    /**
     * @param TaxValue $taxValue
     * @param bool $flush
     */
    protected function removeTaxValue(TaxValue $taxValue, $flush = true)
    {
        $em = $this->getTaxValueEntityManager();
        $em->remove($taxValue);
        if ($flush) {
            $em->flush();
        }
    }

    /**
     * @param $entity
     * @param bool $flush
     */
    protected function removeOrder($entity, $flush = true)
    {
        $em = $this->getOrderEntityManager();
        $em->remove($entity);
        if ($flush) {
            $em->flush();
        }
    }

    /**
     * @return ObjectManager
     */
    protected function getTaxValueEntityManager()
    {
        return $this->getDoctrine()->getManagerForClass(TaxValue::class);
    }

    /**
     * @return ObjectManager
     */
    protected function getOrderEntityManager()
    {
        return $this->getDoctrine()->getManagerForClass(Order::class);
    }

    /**
     * @return Registry
     */
    protected function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }

    /**
     * @return ContainerInterface
     */
    abstract protected static function getContainer();
}
