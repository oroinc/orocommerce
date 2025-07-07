<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\ProductBundle\Entity\ProductName;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;

    public function __construct(TestEntityNameResolverDataLoaderInterface $innerDataLoader)
    {
        $this->innerDataLoader = $innerDataLoader;
    }

    #[\Override]
    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (Order::class === $entityClass) {
            $order = new Order();
            $order->setOrganization($repository->getReference('organization'));
            $order->setOwner($repository->getReference('user'));
            $order->setIdentifier('ORD1');
            $order->setPoNumber('PO1');
            $order->setCurrency('USD');
            $order->setShippingMethod('test_shipping');
            $order->setShippingMethodType('test_shipping_type');
            $repository->setReference('order', $order);
            $em->persist($order);

            $em->flush();

            return ['order'];
        }

        if (OrderAddress::class === $entityClass) {
            $orderAddress = new OrderAddress();
            $orderAddress->setOrganization($repository->getReference('organization'));
            $orderAddress->setFirstName('Jane');
            $orderAddress->setMiddleName('M');
            $orderAddress->setLastName('Doo');
            $repository->setReference('orderAddress', $orderAddress);
            $em->persist($orderAddress);
            $em->flush();

            return ['orderAddress'];
        }

        return $this->innerDataLoader->loadEntity($em, $repository, $entityClass);
    }

    #[\Override]
    public function getExpectedEntityName(
        ReferenceRepository $repository,
        string $entityClass,
        string $entityReference,
        ?string $format,
        ?string $locale
    ): string {
        if (Order::class === $entityClass) {
            $identifier = (string)$repository->getReference($entityReference)->getIdentifier();
            if (EntityNameProviderInterface::SHORT === $format) {
                return $identifier;
            }

            return sprintf('Order #%s', $identifier);
        }
        if (OrderAddress::class === $entityClass) {
            return EntityNameProviderInterface::SHORT === $format
                ? 'Jane'
                : 'Jane M Doo';
        }

        return $this->innerDataLoader->getExpectedEntityName(
            $repository,
            $entityClass,
            $entityReference,
            $format,
            $locale
        );
    }

    private function createProductName(
        EntityManagerInterface $em,
        string $value,
        ?Localization $localization = null
    ): ProductName {
        $name = new ProductName();
        $name->setString($value);
        if (null !== $localization) {
            $name->setLocalization($localization);
        }
        $em->persist($name);

        return $name;
    }
}
