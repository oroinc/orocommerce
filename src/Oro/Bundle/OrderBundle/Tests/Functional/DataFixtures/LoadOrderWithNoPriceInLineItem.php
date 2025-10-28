<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadOrderWithNoPriceInLineItem extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    const ORDER_WITH_NULL_PRICE = 'order_with_no_price_in_line_item';

    #[\Override]
    public function getDependencies()
    {
        return [
            LoadOrganization::class,
            LoadOrderUsers::class,
            LoadPaymentTermData::class,
            LoadProductData::class,
            LoadProductUnits::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = $this->getReference(LoadOrderUsers::ORDER_USER_1);
        if (!$user->getOrganization()) {
            $user->setOrganization($this->getReference('organization'));
        }

        /** @var CustomerUser $customerUser */
        $customerUser = $manager->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadCustomerUserData::AUTH_USER]);

        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->getReference(LoadPaymentTermData::PAYMENT_TERM_NET_10);

        /** @var Website $website */
        $website = $manager->getRepository(Website::class)->findOneBy([]);

        $order = new Order();
        $order->setIdentifier(self::ORDER_WITH_NULL_PRICE);
        $order->setOwner($user);
        $order->setOrganization($user->getOrganization());
        $order->setShipUntil(new \DateTime());
        $order->setCurrency('USD');
        $order->setPoNumber('PO_NULL_PRICE');
        $order->setSubtotalDiscountObject(MultiCurrency::create('100.0000', 'USD', '100.0000'));
        $order->setSubtotalObject(MultiCurrency::create('100.0000', 'USD', '100.0000'));
        $order->setTotalObject(MultiCurrency::create('100.0000', 'USD', '100.0000'));
        $order->setCustomer($customerUser->getCustomer());
        $order->setWebsite($website);
        $order->setCustomerUser($customerUser);

        $this->container->get('oro_payment_term.provider.payment_term_association')
            ->setPaymentTerm($order, $paymentTerm);

        // Add a line item with null price
        $lineItem = new OrderLineItem();
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var ProductUnit $productUnit */
        $productUnit = $this->getReference(LoadProductUnits::LITER);

        $lineItem->setProduct($product)
            ->setQuantity(10)
            ->setProductUnit($productUnit)
            ->setPrice(null); // Explicitly set price to null

        $order->addLineItem($lineItem);

        $manager->persist($order);
        $manager->persist($lineItem);
        $manager->flush();

        $this->addReference(self::ORDER_WITH_NULL_PRICE, $order);
    }
}
