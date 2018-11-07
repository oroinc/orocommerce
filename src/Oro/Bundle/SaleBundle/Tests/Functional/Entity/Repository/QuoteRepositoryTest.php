<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Entity\Repository;

use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\ORM\EntityManager;
use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class QuoteRepositoryTest extends WebTestCase
{
    /** @var EntityManager */
    private $em;

    /** @var QueryAnalyzer */
    private $queryAnalyzer;

    /** @var SQLLogger */
    private $prevLogger;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                'Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData',
            ]
        );

        $this->em = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(Quote::class);

        $connection = $this->em->getConnection();
        $configuration = $connection->getConfiguration();

        $this->prevLogger = $configuration->getSQLLogger();
        $this->queryAnalyzer = new QueryAnalyzer($connection->getDatabasePlatform());

        $configuration->setSQLLogger($this->queryAnalyzer);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->em
            ->getConnection()
            ->getConfiguration()
            ->setSQLLogger($this->prevLogger);
    }

    public function testQuoteInOneQuery(): void
    {
        /** @var Quote $quote */
        $quote = $this->getReference(LoadQuoteData::QUOTE1);

        $loadedQuote = $this->em->getRepository(Quote::class)->getQuote($quote->getId());

        $this->assertQuoteFetchedInOneQuery($loadedQuote);
    }

    public function testGetQuoteByGuestAccessIdInOneQuery(): void
    {
        /** @var Quote $quote */
        $quote = $this->getReference(LoadQuoteData::QUOTE1);

        $loadedQuote = $this->em->getRepository(Quote::class)->getQuoteByGuestAccessId($quote->getGuestAccessId());

        $this->assertQuoteFetchedInOneQuery($loadedQuote);
    }

    /**
     * @param Quote $loadedQuote
     */
    private function assertQuoteFetchedInOneQuery(Quote $loadedQuote): void
    {
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

        $queries = $this->queryAnalyzer->getExecutedQueries();
        $this->assertCount(1, $queries);

        $query = reset($queries);

        $quoteProductMetadata = $this->em
            ->getClassMetadata($this->getContainer()->getParameter('oro_sale.entity.quote_product.class'));
        $this->assertContains(sprintf('LEFT JOIN %s', $quoteProductMetadata->getTableName()), $query);

        $quoteProductOfferMetadata = $this->em
            ->getClassMetadata($this->getContainer()->getParameter('oro_sale.entity.quote_product_offer.class'));
        $this->assertContains(sprintf('LEFT JOIN %s', $quoteProductOfferMetadata->getTableName()), $query);
    }
}
