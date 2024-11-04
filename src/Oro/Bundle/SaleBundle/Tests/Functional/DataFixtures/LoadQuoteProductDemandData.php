<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;

class LoadQuoteProductDemandData extends AbstractFixture implements DependentFixtureInterface
{
    public const SELECTED_OFFER_1 = 'selected.offer.1';
    public const SELECTED_OFFER_2 = 'selected.offer.2';
    public const SELECTED_OFFER_3 = 'selected.offer.3';

    public const QUOTE_DEMAND_1 = 'quote.demand.1';
    public const QUOTE_DEMAND_2 = 'quote.demand.2';
    public const QUOTE_DEMAND_3 = 'quote.demand.3';

    private static array $items = [
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

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadCustomerUserData::class,
            LoadCustomers::class,
            LoadQuoteProductOfferData::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach (self::$items as $key => $item) {
            /** @var Quote $quote */
            $quote = $this->getReference($item['quote']);
            $quoteDemand = new QuoteDemand();
            $quoteDemand->setQuote($quote);
            $quoteDemand->setCustomer($this->getReference($item['customer']));
            $quoteDemand->setCustomerUser($this->getReference($item['customerUser']));
            $quoteDemand->setSubtotal($item['subtotal']);
            $quoteDemand->setTotal($item['total']);
            $quoteDemand->setTotalCurrency($item['currency']);
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
