<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Entity\Repository;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\ORM\EntityManager;
use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\Repository\QuoteRepository;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class QuoteRepositoryTest extends WebTestCase
{
    /** @var QuoteRepository */
    private $repository;

    /** @var EntityManager */
    private $em;

    /** @var Configuration */
    private $configuration;

    /** @var QueryAnalyzer */
    private $queryAnalyzer;

    /** @var SQLLogger */
    private $prevLogger;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadQuoteData::class,
            ]
        );

        $this->em = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(Quote::class);
        $this->repository = $this->em->getRepository(Quote::class);

        $connection = $this->em->getConnection();
        $this->configuration = $connection->getConfiguration();

        $this->prevLogger = $this->configuration->getSQLLogger();
        $this->queryAnalyzer = new QueryAnalyzer($connection->getDatabasePlatform());

        $this->configuration->setSQLLogger($this->queryAnalyzer);
    }

    protected function tearDown(): void
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

        $quoteProductMetadata = $this->em->getClassMetadata(QuoteProduct::class);
        static::assertStringContainsString(
            \sprintf('LEFT JOIN %s', $quoteProductMetadata->getTableName()),
            $query
        );

        $quoteProductOfferMetadata = $this->em->getClassMetadata(QuoteProductOffer::class);
        static::assertStringContainsString(
            \sprintf('LEFT JOIN %s', $quoteProductOfferMetadata->getTableName()),
            $query
        );
    }

    public function testGetRelatedEntitiesCount()
    {
        $customerUser = $this->getReference(LoadUserData::ACCOUNT1_USER1);

        self::assertSame(1, $this->repository->getRelatedEntitiesCount($customerUser));
    }

    public function testGetRelatedEntitiesCountZero()
    {
        $customerUserWithoutRelatedEntities = $this->getContainer()->get('doctrine')
            ->getManagerForClass(CustomerUser::class)
            ->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadCustomerUserData::AUTH_USER]);

        self::assertSame(0, $this->repository->getRelatedEntitiesCount($customerUserWithoutRelatedEntities));
    }

    public function testResetCustomerUserForSomeEntities()
    {
        $this->configuration->setSQLLogger(null);

        $customerUser = $this->getReference(LoadUserData::ACCOUNT1_USER2);
        $this->repository->resetCustomerUser($customerUser, [
            $this->getReference(LoadQuoteData::QUOTE4),
        ]);

        $quotes = $this->repository->findBy(['customerUser' => null]);
        $this->assertCount(4, $quotes);
    }

    public function testResetCustomerUser()
    {
        $customerUser = $this->getReference(LoadUserData::ACCOUNT1_USER2);
        $this->repository->resetCustomerUser($customerUser);

        $quotes = $this->repository->findBy(['customerUser' => null]);
        $this->assertCount(5, $quotes);
    }
}
