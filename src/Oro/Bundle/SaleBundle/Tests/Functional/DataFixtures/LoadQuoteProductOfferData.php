<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;

class LoadQuoteProductOfferData extends AbstractFixture implements FixtureInterface, DependentFixtureInterface
{
    const QUOTE_PRODUCT_OFFER_1 = 'quote.product.offer.1';
    const QUOTE_PRODUCT_OFFER_2 = 'quote.product.offer.2';

    /**
     * @var array
     */
    public static $items = [
        self::QUOTE_PRODUCT_OFFER_1 => [
            'allowIncrements' => true,
            'amount' => 100,
            'currency' => 'USD',
            'quantity' => 1,
            'productUnit' => 'product_unit.box',
            'product' => 'product-1',
        ],
        self::QUOTE_PRODUCT_OFFER_2 => [
            'allowIncrements' => true,
            'amount' => 20,
            'currency' => 'USD',
            'quantity' => 10,
            'productUnit' => 'product_unit.box',
            'product' => 'product-2',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData',
        ];
    }

    /**
     * Load data fixtures with the passed EntityManager
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$items as $key => $item) {
            $offer = new QuoteProductOffer();
            $offer->setAllowIncrements($item['allowIncrements']);
            $offer->setPrice(Price::create($item['amount'], $item['currency']));
            $offer->setQuantity($item['quantity']);
            $offer->setProductUnit($this->getReference($item['productUnit']));

            $quoteProduct = new QuoteProduct();
            $quoteProduct->setProduct($this->getReference($item['product']));
            $quoteProduct->addQuoteProductOffer($offer);
            $manager->persist($quoteProduct);

            $manager->persist($offer);
            $this->setReference($key, $offer);
        }
        $manager->flush();
    }
}
