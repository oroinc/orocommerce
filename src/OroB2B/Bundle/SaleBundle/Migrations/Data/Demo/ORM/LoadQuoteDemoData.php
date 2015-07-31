<?php

namespace OroB2B\Bundle\SaleBundle\Migrations\Data\Demo\ORM;

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

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct as RFPRequestProduct;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest;

class LoadQuoteDemoData extends AbstractFixture implements
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
            'OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadAccountUserDemoData',
            'OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerDemoData',
            'OroB2B\Bundle\RFPBundle\Migrations\Data\Demo\ORM\LoadRequestDemoData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = $this->getUser($manager);
        $requests = $this->getRequests($manager);
        $organization = $user->getOrganization();
        $accounts = $this->getAccounts($manager);

        for ($i = 0; $i < 20; $i++) {
            /* @var $account Customer */
            $account = $accounts[rand(0, count($accounts) - 1)];

            if (!$account) {
                $accountUser = null;
            } else {
                $accountUsers = array_merge([null], $account->getUsers()->getValues());
                /* @var $accountUser AccountUser */
                $accountUser = $accountUsers[rand(0, count($accountUsers) - 1)];
            }

            // set date in future
            $validUntil = new \DateTime('now');
            $addDays = sprintf('+%s days', rand(10, 100));
            $validUntil->modify($addDays);
            $quote = new Quote();
            $quote
                ->setOwner($user)
                ->setOrganization($organization)
                ->setValidUntil($validUntil)
                ->setAccountUser($accountUser)
                ->setAccount($account)
            ;

            if (1 === rand(1, 3)) {
                $quote->setRequest($requests[rand(1, count($requests) - 1)]);
            }

            $this->processQuoteProducts($quote, $manager);

            $manager->persist($quote);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return Collection|Customer[]
     */
    protected function getAccounts(ObjectManager $manager)
    {
        return array_merge([null], $manager->getRepository('OroB2BCustomerBundle:Customer')->findBy([], null, 10));
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
     * @param Quote $quote
     * @param ObjectManager $manager
     */
    protected function processQuoteProducts(Quote $quote, ObjectManager $manager)
    {
        $products   = $this->getProducts($manager);
        $currencies = $this->getCurrencies();

        $types = [
            QuoteProduct::TYPE_REQUESTED,
            QuoteProduct::TYPE_NOT_AVAILABLE,
        ];

        $priceTypes = [
            QuoteProductOffer::PRICE_TYPE_UNIT,
            QuoteProductOffer::PRICE_TYPE_BUNDLED,
        ];

        if ($quote->getRequest()) {
            foreach ($quote->getRequest()->getRequestProducts() as $requestProduct) {
                $type = $types[rand(0, count($types) - 1)];

                $quoteProduct = $this->createQuoteProduct($requestProduct->getProduct(), $type);

                $this->processRequestProductItems($quoteProduct, $requestProduct);

                $quote->addQuoteProduct($quoteProduct);
            }
        } else {
            for ($i = 0; $i < rand(1, 3); $i++) {
                $product = $products[rand(1, count($products) - 1)];
                $quote->addQuoteProduct($this->createQuoteProduct($product, QuoteProduct::TYPE_OFFER));
            }
        }

        foreach ($quote->getQuoteProducts() as $quoteProduct) {
            $unitPrecisions = $quoteProduct->getProduct()->getUnitPrecisions();

            for ($j = 0; $j < rand(1, 3); $j++) {
                if (!count($unitPrecisions)) {
                    continue;
                }

                $productUnit = $unitPrecisions[rand(0, count($unitPrecisions) - 1)]->getUnit();

                $currency = $currencies[rand(0, count($currencies) - 1)];
                $priceType = $priceTypes[rand(0, count($priceTypes) - 1)];

                $quoteProductOffer = new QuoteProductOffer();
                $quoteProductOffer
                    ->setPrice(Price::create(rand(1, 100), $currency))
                    ->setQuantity(rand(1, 100))
                    ->setProductUnit($productUnit)
                    ->setPriceType($priceType)
                    ->setAllowIncrements((bool)rand(0, 1))
                ;

                if ($quoteProduct->isTypeNotAvailable()) {
                    $productReplacement = $products[rand(1, count($products) - 1)];
                    $unitPrecisionsRepl = $productReplacement->getUnitPrecisions();
                    $productUnitRepl = $unitPrecisionsRepl[rand(0, count($unitPrecisionsRepl) - 1)]->getUnit();
                    $quoteProduct->setProductReplacement($productReplacement);
                    $quoteProductOffer->setProductUnit($productUnitRepl);
                }

                $quoteProduct->addQuoteProductOffer($quoteProductOffer);
            }
        }
    }

    /**
     * @param Product $product
     * @param int $type
     * @return QuoteProduct
     */
    protected function createQuoteProduct(Product $product, $type)
    {
        static $index = 0;

        $index++;

        $quoteProduct = new QuoteProduct();
        $quoteProduct
            ->setProduct($product)
            ->setType($type)
            ->setComment(sprintf('Seller Notes %s', $index + 1))
            ->setCommentCustomer(sprintf('Customer Notes %s', $index + 1))
        ;

        return $quoteProduct;
    }

    /**
     * @param QuoteProduct $quoteProduct
     * @param RFPRequestProduct $requestProduct
     */
    protected function processRequestProductItems(QuoteProduct $quoteProduct, RFPRequestProduct $requestProduct)
    {
        foreach ($requestProduct->getRequestProductItems() as $requestProductItem) {
            $quoteProductRequest = new QuoteProductRequest();
            $quoteProductRequest
                ->setPrice($requestProductItem->getPrice())
                ->setQuantity($requestProductItem->getQuantity())
                ->setProductUnit($requestProductItem->getProductUnit())
            ;
            $quoteProduct->addQuoteProductRequest($quoteProductRequest);
        }
    }

    /**
     * @param ObjectManager $manager
     * @return Collection|Product[]
     */
    protected function getProducts(ObjectManager $manager)
    {
        $products = $manager->getRepository('OroB2BProductBundle:Product')->findBy([], null, 10);

        if (!count($products)) {
            throw new \LogicException('There are no products in system');
        }

        return $products;
    }

    /**
     * @param ObjectManager $manager
     * @return Collection|RFPRequest[]
     */
    protected function getRequests(ObjectManager $manager)
    {
        $requests = $manager->getRepository('OroB2BRFPBundle:Request')->findBy([], null, 10);

        if (!count($requests)) {
            throw new \LogicException('There are no RFPRequests in system');
        }

        return $requests;
    }

    /**
     * @param ObjectManager $manager
     * @return User
     */
    protected function getUser(ObjectManager $manager)
    {
        $role = $manager->getRepository('OroUserBundle:Role')
            ->findOneBy(['role' => LoadRolesData::ROLE_ADMINISTRATOR]);

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
