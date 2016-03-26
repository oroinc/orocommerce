<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\SaleBundle\Entity\Repository\QuoteProductSelectedOfferRepository;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductSelectedOfferData;

/**
 * @dbIsolation
 */
class QuoteProductSelectedOfferRepositoryTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductSelectedOfferData',
            ]
        );
    }

    public function testGetSavedOffersByQuote()
    {
        $class = 'OroB2B\Bundle\SaleBundle\Entity\QuoteProductSelectedOffer';
        /** @var QuoteProductSelectedOfferRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getManagerForClass($class)
            ->getRepository($class);

        $savedOffers = $repository->getSavedOffersByQuote($this->getReference(LoadQuoteData::QUOTE1));
        $expectedResult = [];
        $offer = $this->getReference(LoadQuoteProductSelectedOfferData::SELECTED_OFFER_1);
        $expectedResult[$offer->getQuoteProductOffer()->getId()] = $offer;
        $offer = $this->getReference(LoadQuoteProductSelectedOfferData::SELECTED_OFFER_2);
        $expectedResult[$offer->getQuoteProductOffer()->getId()] = $offer;
        $this->assertSame($expectedResult, $savedOffers);
    }
}
