<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProductDemand;

class LoadQuoteProductDemandData extends AbstractFixture implements FixtureInterface, DependentFixtureInterface
{
    const SELECTED_OFFER_1 = 'selected.offer.1';
    const SELECTED_OFFER_2 = 'selected.offer.2';

    /**
     * @var array
     */
    public static $items = [
        self::SELECTED_OFFER_1 => [
            'quote' => LoadQuoteData::QUOTE1,
            'offer' => LoadQuoteProductOfferData::QUOTE_PRODUCT_OFFER_1,
            'quantity' => 10,
        ],
        self::SELECTED_OFFER_2 => [
            'quote' => LoadQuoteData::QUOTE1,
            'offer' => LoadQuoteProductOfferData::QUOTE_PRODUCT_OFFER_2,
            'quantity' => 10,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductOfferData',
        ];
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$items as $key => $item) {
            $selectedOffer = new QuoteProductDemand(
                $this->getReference($item['quote']),
                $this->getReference($item['offer']),
                $item['quantity']
            );
            $manager->persist($selectedOffer);
            $this->setReference($key, $selectedOffer);
        }
        $manager->flush();
    }
}
