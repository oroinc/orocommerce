<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\UserBundle\Entity\User;

trait OrderTaxHelperTrait
{
    protected function createOrder(bool $flush = true): Order
    {
        $doctrine = $this->getDoctrine();
        /** @var User $orderUser */
        $orderUser = $doctrine->getRepository(User::class)->findOneBy([]);
        if (!$orderUser->getOrganization()) {
            $orderUser->setOrganization(
                $doctrine->getRepository(Organization::class)->findOneBy([])
            );
        }
        /** @var CustomerUser $customerUser */
        $customerUser = $doctrine->getRepository(CustomerUser::class)->findOneBy([]);

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

    protected function updateOrder(Order $order): void
    {
        $order
            ->setIdentifier(uniqid('identifier_', true))
            ->setSubtotal('1800');

        $em = $this->getOrderEntityManager();
        $em->persist($order);
        $em->flush();
    }

    protected function getTaxValue(Order $order): ?TaxValue
    {
        return $this->getDoctrine()->getRepository(TaxValue::class)
            ->findOneBy(['entityClass' => Order::class, 'entityId' => $order->getId()]);
    }

    protected function removeTaxValue(TaxValue $taxValue, bool $flush = true): void
    {
        $em = $this->getTaxValueEntityManager();
        $em->remove($taxValue);
        if ($flush) {
            $em->flush();
        }
    }

    protected function removeOrder(object $entity, bool $flush = true): void
    {
        $em = $this->getOrderEntityManager();
        $em->remove($entity);
        if ($flush) {
            $em->flush();
        }
    }

    protected function getTaxValueEntityManager(): EntityManagerInterface
    {
        return $this->getDoctrine()->getManagerForClass(TaxValue::class);
    }

    protected function getOrderEntityManager(): EntityManagerInterface
    {
        return $this->getDoctrine()->getManagerForClass(Order::class);
    }

    protected function getDoctrine(): ManagerRegistry
    {
        return self::getContainer()->get('doctrine');
    }
}
