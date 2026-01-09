<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfiguration;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerDemoData;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerUserDemoData;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadPriceListDemoData;
use Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadProductPriceDemoData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData;
use Oro\Bundle\ShoppingListBundle\Migrations\Data\Demo\ORM\LoadShoppingListDemoData;
use Oro\Bundle\TaxBundle\Migrations\Data\Demo\ORM\LoadTaxConfigurationDemoData;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads demo data for customer orders.
 */
class LoadCustomerOrderDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadCustomerDemoData::class,
            LoadCustomerUserDemoData::class,
            LoadPaymentTermDemoData::class,
            LoadPriceListDemoData::class,
            LoadProductPriceDemoData::class,
            LoadShoppingListDemoData::class,
            LoadTaxConfigurationDemoData::class,
            LoadProductDemoData::class,
            LoadProductUnitPrecisionDemoData::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var EntityManagerInterface $manager */

        $orderMetadata = $manager->getClassMetadata(Order::class);
        $this->disablePrePersistCallback($orderMetadata);
        $this->toggleFeatures(false);
        $this->toggleListeners(false);

        /** @var CustomerUser[] $customerUsers */
        $customerUsers = $manager->getRepository(CustomerUser::class)
            ->findBy(['emailLowercase' => ['amandarcole@example.org', 'brandajsanborn@example.org']]);

        /** @var EnumOption[] $internalStatuses */
        $internalStatuses = $manager->getRepository(EnumOption::class)
            ->findBy([
                'enumCode' => Order::INTERNAL_STATUS_CODE,
                'internalId' => [
                    OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
                    OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
                    OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED
                ]
            ]);

        /** @var Product[] $products */
        $products = $manager->getRepository(Product::class)->findBy(['type' => Product::TYPE_SIMPLE]);

        /** @var Website $website */
        $website = $manager->getRepository(Website::class)
            ->findOneBy(['name' => LoadWebsiteData::DEFAULT_WEBSITE_NAME]);

        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $manager->getRepository(PaymentTerm::class)->findOneBy([]);

        /** @var TotalHelper $totalHelper */
        $totalHelper = $this->container->get('oro_order.order.total.total_helper');
        /** @var PaymentTermAssociationProvider $paymentTermAccessor */
        $paymentTermAccessor = $this->container->get('oro_payment_term.provider.payment_term_association');

        $index = 0;
        $timeZone = new \DateTimeZone('UTC');
        foreach ($customerUsers as $customerUser) {
            foreach ($internalStatuses as $internalStatus) {
                $orderAddress = $this->getOrderAddressByCustomer($customerUser, $manager);
                $createdAt = $this->getRandomDateTime();

                $order = new Order();
                $order->setInternalStatus($internalStatus);
                $order->setOwner($customerUser->getOwner());
                $order->setPoNumber(sprintf('POSD%03d%03d', $customerUser->getId(), $index));
                $order->setIdentifier(sprintf('COI%03d%03d', $customerUser->getId(), $index));
                $order->setCustomer($customerUser->getCustomer());
                $order->setCustomerUser($customerUser);
                $order->setOrganization($customerUser->getOwner()->getOrganization());
                $order->setBillingAddress($orderAddress);
                $order->setShippingAddress($orderAddress);
                $order->setWebsite($website);
                $order->setCurrency(CurrencyConfiguration::DEFAULT_CURRENCY);
                $order->setShipUntil(new \DateTime(sprintf('+%d hours', random_int(0, 100)), $timeZone));
                $order->setCreatedAt($createdAt);
                $order->setUpdatedAt(clone $createdAt);

                $paymentTermAccessor->setPaymentTerm($order, $paymentTerm);

                $this->addOrderLineItems($order, $products);

                $manager->persist($order);

                $totalHelper->fill($order);

                $index++;
            }
        }

        $manager->flush();

        $this->enablePrePersistCallback($orderMetadata);
        $this->toggleFeatures(true);
        $this->toggleListeners(true);
    }

    private function getOrderAddressByCustomer(
        CustomerUser $customerUser,
        EntityManagerInterface $manager
    ): OrderAddress {
        $customerAddress = $customerUser->getAddresses()->first();

        $orderAddress = new OrderAddress();
        $orderAddress->setLabel($customerAddress->getLabel());
        $orderAddress->setCountry($customerAddress->getCountry());
        $orderAddress->setCity($customerAddress->getCity());
        $orderAddress->setRegion($customerAddress->getRegion());
        $orderAddress->setStreet($customerAddress->getStreet());
        $orderAddress->setPostalCode($customerAddress->getPostalCode());
        $orderAddress->setFirstName($customerUser->getFirstName());
        $orderAddress->setLastName($customerUser->getLastName());
        $orderAddress->setPhone('1234567890');

        $manager->persist($orderAddress);

        return $orderAddress;
    }

    private function addOrderLineItems(Order $order, array $products): void
    {
        $numberOfLineItem = random_int(1, 5);
        for ($i = 0; $i < $numberOfLineItem; $i++) {
            /** @var Product $product */
            $product = $products[array_rand($products)];
            $units = $product->getAvailableUnits();
            $unit = $units ? $units[array_rand($units)] : null;

            $orderLineItem = new OrderLineItem();
            $orderLineItem->setProduct($product);
            $orderLineItem->setProductName((string)$product->getName());
            $orderLineItem->setProductUnit($unit);
            $orderLineItem->setQuantity(random_int(1, 13));
            $orderLineItem->setPrice(Price::create(random_int(10, 1000), $order->getCurrency()));

            $order->addLineItem($orderLineItem);
        }
    }

    private function getRandomDateTime(): \DateTime
    {
        return new \DateTime(
            \sprintf('-%sday %s:%s', random_int(0, 7), random_int(0, 23), random_int(0, 59)),
            new \DateTimeZone('UTC')
        );
    }

    private function enablePrePersistCallback(ClassMetadata $classMetadata): void
    {
        $lifecycleCallbacks = $classMetadata->lifecycleCallbacks;
        array_unshift($lifecycleCallbacks['prePersist'], 'updateTotalDiscounts');
        array_unshift($lifecycleCallbacks['prePersist'], 'prePersist');
        $classMetadata->setLifecycleCallbacks($lifecycleCallbacks);
    }

    private function disablePrePersistCallback(ClassMetadata $classMetadata): void
    {
        $lifecycleCallbacks = $classMetadata->lifecycleCallbacks;
        $lifecycleCallbacks['prePersist'] = array_diff(
            $lifecycleCallbacks['prePersist'],
            ['prePersist', 'updateTotalDiscounts']
        );
        $classMetadata->setLifecycleCallbacks($lifecycleCallbacks);
    }

    private function toggleFeatures(?bool $enable): void
    {
        $configManager = $this->container->get('oro_config.global');
        $configManager->set('oro_promotion.feature_enabled', $enable ?? false);
        $configManager->flush();
    }

    private function toggleListeners(?bool $enable): void
    {
        $listenerManager = $this->container->get('oro_platform.optional_listeners.manager');
        if ($enable) {
            $listenerManager->enableListener('oro_order.order.listener.orm.order_shipping_status_listener');
        } else {
            $listenerManager->disableListener('oro_order.order.listener.orm.order_shipping_status_listener');
        }
    }
}
