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

use OroB2B\Bundle\ProductBundle\Entity\Product;
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
            'OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = $this->getUser($manager);
        $organization = $user->getOrganization();
        for ($i = 0; $i < 100; $i++) {
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
     * @return Collection|Product[]
     * @throws \LogicException
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
        $products   = $this->getProducts($manager);
        $currencies = $this->getCurrencies();
        $types = [
            QuoteProduct::TYPE_OFFER,
            QuoteProduct::TYPE_REQUESTED,
            QuoteProduct::TYPE_NOT_AVAILABLE,
        ];
        $priceTypes = [
            QuoteProductOffer::PRICE_UNIT,
            QuoteProductOffer::PRICE_BUNDLED,
        ];
        for ($i = 0; $i < rand(1, 3); $i++) {
            $product = $products[rand(1, count($products) - 1)];
            $unitPrecisions = $product->getUnitPrecisions();
            $type = $types[rand(0, count($types) - 1)];
            $quoteProduct = new QuoteProduct();
            $quoteProduct
                ->setProduct($product)
                ->setType($type)
                ->setComment(sprintf('Seller Notes %s', $i + 1))
                ->setCommentCustomer(sprintf('Customer Notes %s', $i + 1))
            ;

            for ($j = 0; $j < rand(1, 3); $j++) {
                if (!count($unitPrecisions)) {
                    continue;
                }

                $productUnit = $unitPrecisions[rand(0, count($unitPrecisions) - 1)]->getUnit();

                $currency = $currencies[rand(0, count($currencies) - 1)];
                $priceType = $priceTypes[rand(0, count($priceTypes) - 1)];

                if ($quoteProduct->isTypeRequested() || $quoteProduct->isTypeNotAvailable()) {
                    $quoteProductRequest = new QuoteProductRequest();
                    $quoteProductRequest
                        ->setPrice(Price::create(rand(1, 100), $currency))
                        ->setQuantity(rand(1, 100))
                        ->setProductUnit($productUnit)
                    ;
                    $quoteProduct->addQuoteProductRequest($quoteProductRequest);
                }

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
                    if (count($unitPrecisions)) {
                        $productUnitRepl = $unitPrecisionsRepl[rand(0, count($unitPrecisionsRepl) - 1)]->getUnit();
                        $quoteProduct->setProductReplacement($productReplacement);
                        $quoteProductOffer->setProductUnit($productUnitRepl);
                    }
                }

                $quoteProduct->addQuoteProductOffer($quoteProductOffer);
            }
            $quote->addQuoteProduct($quoteProduct);
        }
    }

    /**
     * @param ObjectManager $manager
     * @return User
     * @throws \RuntimeException
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
