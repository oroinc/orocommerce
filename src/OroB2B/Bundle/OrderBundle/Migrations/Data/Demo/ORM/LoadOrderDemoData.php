<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderProduct;
use OroB2B\Bundle\OrderBundle\Entity\OrderProductItem;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\SaleBundle\Entity\Quote;

class LoadOrderDemoData extends AbstractFixture implements
    FixtureInterface,
    ContainerAwareInterface,
    DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

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
    public function getDependencies()
    {
        return [
            'Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData',
            'OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData',
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountUserDemoData',
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountDemoData',
            'OroB2B\Bundle\SaleBundle\Migrations\Data\Demo\ORM\LoadQuoteDemoData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = $this->getUser($manager);
        $quotes = $this->getQuotes($manager);
        $organization = $user->getOrganization();
        $accountUsers = $this->getAccountUsers($manager);
        for ($i = 0; $i < 20; $i++) {
            $fromQuote = (1 === rand(1, 3));
            $accountUser = $accountUsers[rand(0, count($accountUsers) - 1)];
            $order = new Order();
            $order
                ->setIdentifier($i + 1)
                ->setOwner($user)
                ->setOrganization($organization)
                ->setAccountUser($accountUser)
                ->setAccount($accountUser ? $accountUser->getAccount() : null)
            ;

            if ($fromQuote) {
                $order->setQuote($quotes[rand(0, count($quotes) - 1)]);
            }

            $this->processOrderProducts($order, $manager);

            $manager->persist($order);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return Collection|AccountUser[]
     */
    protected function getAccountUsers(ObjectManager $manager)
    {
        return array_merge([null], $manager->getRepository('OroB2BAccountBundle:AccountUser')->findBy([], null, 10));
    }

    /**
     * @return array
     */
    protected function getCurrencies()
    {
        $currencies = $this->container->get('oro_config.manager')->get('oro_currency.allowed_currencies');

        if (!$currencies) {
            $currencies = (array)$this->container->get('oro_locale.settings')->getCurrency();
        }

        if (!$currencies) {
            throw new \LogicException('There are no currencies in system');
        }

        return $currencies;
    }

    /**
     * @param Order $order
     * @param ObjectManager $manager
     */
    protected function processOrderProducts(Order $order, ObjectManager $manager)
    {
        $products   = $this->getProducts($manager);
        $currencies = $this->getCurrencies();

        $priceTypes = [
            OrderProductItem::PRICE_TYPE_UNIT,
            OrderProductItem::PRICE_TYPE_BUNDLED,
        ];

        for ($i = 0; $i < rand(1, 3); $i++) {
            $orderProduct = $this->createOrderProduct($products[rand(1, count($products) - 1)]);
            $unitPrecisions = $orderProduct->getProduct()->getUnitPrecisions();

            for ($j = 0; $j < rand(1, 3); $j++) {
                if (!count($unitPrecisions)) {
                    continue;
                }
                $productUnit = $unitPrecisions[rand(0, count($unitPrecisions) - 1)]->getUnit();

                $currency = $currencies[rand(0, count($currencies) - 1)];
                $priceType = $priceTypes[rand(0, count($priceTypes) - 1)];

                $orderProductItem = new OrderProductItem();
                $orderProductItem
                    ->setPrice(Price::create(rand(1, 100), $currency))
                    ->setQuantity(rand(1, 100))
                    ->setProductUnit($productUnit)
                    ->setPriceType($priceType)
                ;
                $orderProductItem->setFromQuote(null !== $order->getQuote());

                $orderProduct->addOrderProductItem($orderProductItem);
                $order->addOrderProduct($orderProduct);
            }
        }
    }

    /**
     * @param Product $product
     * @return OrderProduct
     */
    protected function createOrderProduct(Product $product)
    {
        static $index = 0;

        $index++;

        $orderProduct = new OrderProduct();
        $orderProduct
            ->setProduct($product)
            ->setComment(sprintf('Seller Notes %s', $index + 1))
        ;

        return $orderProduct;
    }

    /**
     * @param ObjectManager $manager
     * @return Collection|Product[]
     */
    protected function getProducts(ObjectManager $manager)
    {
        $products = $manager->getRepository('OroB2BProductBundle:Product')->findBy([], null, 10);

        if (!count($products)) {
            throw new \LogicException('There are no Products in system');
        }

        return $products;
    }

    /**
     * @param ObjectManager $manager
     * @return Collection|Quote[]
     */
    protected function getQuotes(ObjectManager $manager)
    {
        $quotes = $manager->getRepository('OroB2BSaleBundle:Quote')->findBy([], null, 10);

        if (!count($quotes)) {
            throw new \LogicException('There are no Quotes in system');
        }

        return $quotes;
    }

    /**
     * @param ObjectManager $manager
     * @return User
     */
    protected function getUser(ObjectManager $manager)
    {
        $role = $manager
            ->getRepository('OroUserBundle:Role')
            ->findOneBy(['role' => LoadRolesData::ROLE_ADMINISTRATOR])
        ;

        if (!$role) {
            throw new \RuntimeException(sprintf('%s role should exist.', LoadRolesData::ROLE_ADMINISTRATOR));
        }

        $user = $manager->getRepository('OroUserBundle:Role')->getFirstMatchedUser($role);

        if (!$user) {
            throw new \RuntimeException(
                sprintf('At least one user with role %s should exist.', LoadRolesData::ROLE_ADMINISTRATOR)
            );
        }

        return $user;
    }
}
