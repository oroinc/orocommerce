<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;

class LoadQuoteProductDemandData extends AbstractFixture implements FixtureInterface, DependentFixtureInterface
{
    const SELECTED_OFFER_1 = 'selected.offer.1';
    const SELECTED_OFFER_2 = 'selected.offer.2';
    const SELECTED_OFFER_3 = 'selected.offer.3';

    const QUOTE_DEMAND_1 = 'quote.demand.1';
    const QUOTE_DEMAND_2 = 'quote.demand.2';
    const QUOTE_DEMAND_3 = 'quote.demand.3';

    /**
     * @var array
     */
    public static $items = [
        self::SELECTED_OFFER_1 => [
            'quoteDemandReference' => self::QUOTE_DEMAND_1,
            'quote' => LoadQuoteData::QUOTE1,
            'offer' => LoadQuoteProductOfferData::QUOTE_PRODUCT_OFFER_1,
            'customer' => 'customer.level_1',
            'customerUser' => LoadCustomerUserData::EMAIL,
            'quantity' => 10,
            'subtotal' => 122,
            'total' => 122,
            'currency' => 'USD'
        ],
        self::SELECTED_OFFER_2 => [
            'quoteDemandReference' => self::QUOTE_DEMAND_2,
            'quote' => LoadQuoteData::QUOTE1,
            'offer' => LoadQuoteProductOfferData::QUOTE_PRODUCT_OFFER_2,
            'customer' => 'customer.level_1',
            'customerUser' => LoadCustomerUserData::LEVEL_1_EMAIL,
            'quantity' => 10,
            'subtotal' => 321,
            'total' => 321,
            'currency' => 'UAH'
        ],
        self::SELECTED_OFFER_3 => [
            'quoteDemandReference' => self::QUOTE_DEMAND_3,
            'quote' => LoadQuoteData::QUOTE3,
            'offer' => LoadQuoteProductOfferData::QUOTE_PRODUCT_OFFER_2,
            'customer' => 'customer.level_1',
            'customerUser' => LoadCustomerUserData::LEVEL_1_EMAIL,
            'quantity' => 10,
            'subtotal' => 321,
            'total' => 321,
            'currency' => 'UAH'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCustomerUserData::class,
            LoadCustomers::class,
            LoadQuoteProductOfferData::class,
        ];
    }

    /**
     * Load data fixtures with the passed EntityManager
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$items as $key => $item) {
            /** @var Quote $quote */
            $quote = $this->getReference($item['quote']);
            $quoteDemand = new QuoteDemand();
            $quoteDemand->setQuote($quote)
                ->setCustomer($this->getReference($item['customer']))
                ->setCustomerUser($this->getReference($item['customerUser']))
                ->setSubtotal($item['subtotal'])
                ->setTotal($item['total'])
                ->setTotalCurrency($item['currency']);
            $manager->persist($quoteDemand);
            $this->setReference($item['quoteDemandReference'], $quoteDemand);
            /** @var QuoteProductOffer $offer */
            $offer = $this->getReference($item['offer']);
            $selectedOffer = new QuoteProductDemand($quoteDemand, $offer, $item['quantity']);
            $manager->persist($selectedOffer);
            $this->setReference($key, $selectedOffer);
        }
        $manager->flush();
    }
}
