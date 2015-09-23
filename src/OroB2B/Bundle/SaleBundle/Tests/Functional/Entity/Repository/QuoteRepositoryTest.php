<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;

use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\Repository\QuoteRepository;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

/**
 * @dbIsolation
 */
class QuoteRepositoryTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData',
            ]
        );
    }

    public function testQuoteInOneQuery()
    {
        /** @var Quote $quote */
        $quote = $this->getReference(LoadQuoteData::QUOTE1);

        $quoteClass = $this->getContainer()->getParameter('orob2b_sale.entity.quote.class');

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass($quoteClass);

        /** @var QuoteRepository $repository */
        $repository = $em->getRepository($quoteClass);

        $queryAnalyzer = new QueryAnalyzer($em->getConnection()->getDatabasePlatform());

        $prevLogger = $em->getConnection()->getConfiguration()->getSQLLogger();
        $em->getConnection()->getConfiguration()->setSQLLogger($queryAnalyzer);

        $loadedQuote = $repository->getQuote($quote->getId());

        // iterate collections to run additional queries if not fetched at once
        $this->assertNotEmpty($loadedQuote->getQuoteProducts());
        $this->assertSameSize(
            LoadQuoteData::$items[LoadQuoteData::QUOTE1]['products'],
            $loadedQuote->getQuoteProducts()
        );
        foreach ($loadedQuote->getQuoteProducts() as $quoteProduct) {
            $this->assertNotEmpty($quoteProduct->getProductSku());

            $this->assertNotEmpty($quoteProduct->getQuoteProductOffers());
            $this->assertSameSize(
                LoadQuoteData::$items[LoadQuoteData::QUOTE1]['products'][$quoteProduct->getProductSku()],
                $quoteProduct->getQuoteProductOffers()
            );
            foreach ($quoteProduct->getQuoteProductOffers() as $quoteProductOffer) {
                $this->assertNotEmpty($quoteProductOffer->getQuantity());
            }
        }

        $queries = $queryAnalyzer->getExecutedQueries();
        $this->assertCount(1, $queries);

        $query = reset($queries);

        $quoteProductMetadata = $em
            ->getClassMetadata($this->getContainer()->getParameter('orob2b_sale.entity.quote_product.class'));
        $this->assertContains(sprintf('LEFT JOIN %s', $quoteProductMetadata->getTableName()), $query);

        $quoteProductOfferMetadata = $em
            ->getClassMetadata($this->getContainer()->getParameter('orob2b_sale.entity.quote_product_offer.class'));
        $this->assertContains(sprintf('LEFT JOIN %s', $quoteProductOfferMetadata->getTableName()), $query);

        $em->getConnection()->getConfiguration()->setSQLLogger($prevLogger);
    }
}
