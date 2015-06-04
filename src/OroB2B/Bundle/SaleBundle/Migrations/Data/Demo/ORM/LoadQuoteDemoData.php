<?php

namespace OroB2B\Bundle\SaleBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\CurrencyBundle\Model\Price;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductItem;
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
            'OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData',
            /* ToDo: waiting for merge this file
            'OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData'
            */
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $user = $this->getUser($manager);

        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BSaleBundle/Migrations/Data/Demo/ORM/data/quotes.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            // set date in future
            $validUntil = new \DateTime('now');
            $addDays = sprintf('+%s days', rand(10, 100));
            $validUntil->modify($addDays);
            $row = array_combine($headers, array_values($data));
            $quote = new Quote();
            $quote
                ->setOwner($user)
                ->setQid($row['qid'])
                ->setValidUntil($validUntil)
            ;
            $this->processQuoteProducts($quote, $manager);

            $manager->persist($quote);
        }

        fclose($handler);

        $manager->flush();
    }

    /**
     * @param EntityManager $manager
     * @return User
     * @throws \LogicException
     */
    protected function getUser(EntityManager $manager)
    {
        $user = $manager->getRepository('OroUserBundle:User')
            ->createQueryBuilder('user')
            ->orderBy('user.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult()
        ;

        if (!$user) {
            throw new \LogicException('There are no users in system');
        }

        return $user;
    }

    /**
     * @param EntityManager $manager
     * @return Collection|Product[]
     * @throws \LogicException
     */
    protected function getProducts(EntityManager $manager)
    {
        $products = $manager->getRepository('OroB2BProductBundle:Product')
            ->createQueryBuilder('p')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;

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
        $currencies = $this->container->get('oro_config.global')->get('oro_currency.allowed_currencies');
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
     * @param EntityManager $manager
     */
    protected function processQuoteProducts(Quote $quote, EntityManager $manager)
    {
        $products = $this->getProducts($manager);
        $currencies = $this->getCurrencies();
        for ($i = 0; $i < 3; $i++) {
            $product = $products[rand(0, count($products) - 1)];
            $unitPrecisions = $product->getUnitPrecisions();
            $quoteProduct = new QuoteProduct();
            $quoteProduct->setProduct($product);
            for ($j = 0; $j < 3; $j++) {
                if (!count($unitPrecisions)) {
                    continue;
                }
                $productUnit = $unitPrecisions[rand(0, count($unitPrecisions) - 1)]->getUnit();

                $currency = $currencies[rand(0, count($currencies) - 1)];
                $quoteProductItem = new QuoteProductItem();
                $price = new Price();
                $price
                    ->setValue(rand(1, 100))
                    ->setCurrency($currency)
                ;
                $quoteProductItem
                    ->setPrice($price)
                    ->setQuantity(rand(1, 100))
                    ->setProductUnit($productUnit)
                ;
                $quoteProduct->addQuoteProductItem($quoteProductItem);
            }
            $quote->addQuoteProduct($quoteProduct);
        }
    }
}
