<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\SaleBundle\Entity\Repository\QuoteProductDemandRepository;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductDemandData;

/**
 * @dbIsolation
 */
class QuoteProductDemandRepositoryTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductDemandData',
            ]
        );
    }

    public function testGetSavedOffersByQuote()
    {
        $class = 'OroB2B\Bundle\SaleBundle\Entity\QuoteProductDemand';
        /** @var QuoteProductDemandRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getManagerForClass($class)
            ->getRepository($class);

        $savedOffers = $repository->getSavedOffersByQuote($this->getReference(LoadQuoteData::QUOTE1));
        $expectedResult = [];
        $offer = $this->getReference(LoadQuoteProductDemandData::SELECTED_OFFER_1);
        $expectedResult[$offer->getQuoteProductOffer()->getId()] = $offer;
        $offer = $this->getReference(LoadQuoteProductDemandData::SELECTED_OFFER_2);
        $expectedResult[$offer->getQuoteProductOffer()->getId()] = $offer;
        $this->assertSame($expectedResult, $savedOffers);
    }
}
