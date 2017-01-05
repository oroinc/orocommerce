<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccounts;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;

class LoadQuoteProductDemandData extends AbstractFixture implements FixtureInterface, DependentFixtureInterface
{
    const SELECTED_OFFER_1 = 'selected.offer.1';
    const SELECTED_OFFER_2 = 'selected.offer.2';

    const QUOTE_DEMAND_1 = 'quote.demand.1';
    const QUOTE_DEMAND_2 = 'quote.demand.2';

    /**
     * @var array
     */
    public static $items = [
        self::SELECTED_OFFER_1 => [
            'quoteDemandReference' => self::QUOTE_DEMAND_1,
            'quote' => LoadQuoteData::QUOTE1,
            'offer' => LoadQuoteProductOfferData::QUOTE_PRODUCT_OFFER_1,
            'account' => 'account.level_1',
            'accountUser' => LoadAccountUserData::EMAIL,
            'quantity' => 10,
            'subtotal' => 122,
            'total' => 122,
            'currency' => 'USD'
        ],
        self::SELECTED_OFFER_2 => [
            'quoteDemandReference' => self::QUOTE_DEMAND_2,
            'quote' => LoadQuoteData::QUOTE1,
            'offer' => LoadQuoteProductOfferData::QUOTE_PRODUCT_OFFER_2,
            'account' => 'account.level_1',
            'accountUser' => LoadAccountUserData::LEVEL_1_EMAIL,
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
            LoadAccountUserData::class,
            LoadAccounts::class,
            LoadQuoteProductOfferData::class,
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
            /** @var Quote $quote */
            $quote = $this->getReference($item['quote']);
            $quoteDemand = new QuoteDemand();
            $quoteDemand->setQuote($quote)
                ->setAccount($this->getReference($item['account']))
                ->setAccountUser($this->getReference($item['accountUser']))
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
