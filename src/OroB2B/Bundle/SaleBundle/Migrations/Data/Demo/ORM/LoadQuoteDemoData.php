<?php

namespace OroB2B\Bundle\SaleBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Entity\Quote;

class LoadQuoteDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
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
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var ObjectManager $manager */
        $user = $this->getUser($manager);
        $organization = $user->getOrganization();
        for ($i=0; $i < 100; $i++) {
            // set date in future
            $validUntil = new \DateTime('now');
            $addDays = sprintf('+%s days', rand(10, 100));
            $validUntil->modify($addDays);
            $quote = new Quote();
            $quote
                ->setOwner($user)
                ->setOrganization($organization)
                ->setValidUntil($validUntil)
            ;
            $this->processQuoteProducts($quote, $manager);
            $manager->persist($quote);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return User
     * @throws \LogicException
     */
    protected function getUser(ObjectManager $manager)
    {
        /* @var $user User */
        $user = $manager->getRepository('OroUserBundle:User')->findOneBy([
            'email' => LoadAdminUserData::DEFAULT_ADMIN_EMAIL
        ]);

        if (!$user) {
            throw new \LogicException('There are no users in system');
        }

        return $user;
    }

    /**
     * @param ObjectManager $manager
     * @return Collection|Product[]
     * @throws \LogicException
     */
    protected function getProducts(ObjectManager $manager)
    {
        $products = $manager->getRepository('OroB2BProductBundle:Product')->findBy([], null, 10);

        if (empty($products)) {
            throw new \LogicException('There are no products in system');
        }

        return $products;
    }

    /**
     * @return array
     * @throws \LogicException
     */
    protected function getCurrencies()
    {
        $currencies = $this->container->get('oro_config.manager')->get('oro_currency.allowed_currencies');
        if (empty($currencies)) {
            $currency = $this->container->get('oro_locale.settings')->getCurrency();
            $currencies = $currency ? [$currency] : [];
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
        $products = $this->getProducts($manager);
        $currencies = $this->getCurrencies();
        for ($i = 0; $i < rand(1, 3); $i++) {
            $product = $products[rand(0, count($products) - 1)];
            $unitPrecisions = $product->getUnitPrecisions();
            $quoteProduct = new QuoteProduct();
            $quoteProduct
                ->setProduct($product)
                ->setType(QuoteProduct::TYPE_OFFER)
            ;
            for ($j = 0; $j < rand(1, 3); $j++) {
                if (!count($unitPrecisions)) {
                    continue;
                }
                $productUnit = $unitPrecisions[rand(0, count($unitPrecisions) - 1)]->getUnit();

                $currency = $currencies[rand(0, count($currencies) - 1)];
                $quoteProductOffer = new QuoteProductOffer();
                $quoteProductOffer
                    ->setPrice(Price::create(rand(1, 100), $currency))
                    ->setQuantity(rand(1, 100))
                    ->setProductUnit($productUnit)
                    ->setPriceType(QuoteProductOffer::PRICE_BUNDLED)
                    ->setAllowIncrements(true)
                ;
                $quoteProduct->addQuoteProductOffer($quoteProductOffer);
            }
            $quote->addQuoteProduct($quoteProduct);
        }
    }
}
