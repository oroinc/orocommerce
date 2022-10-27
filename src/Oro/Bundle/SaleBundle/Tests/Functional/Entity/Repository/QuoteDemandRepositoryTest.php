<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\Repository\QuoteDemandRepository;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductDemandData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class QuoteDemandRepositoryTest extends WebTestCase
{
    /**
     * @var QuoteDemandRepository
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadQuoteProductDemandData::class,
            ]
        );
        $this->repository = $this->getContainer()->get('doctrine')->getRepository(QuoteDemand::class);
    }

    public function testGetQuoteDemandByQuote()
    {
        /** @var Quote $quote */
        $quote = $this->getReference(LoadQuoteData::QUOTE1);
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);
        $result = $this->repository->getQuoteDemandByQuote($quote, $customerUser);
        $this->assertInstanceOf(QuoteDemand::class, $result);
        $this->assertEquals($quote, $result->getQuote());
    }

    public function testGetQuoteDemandByQuoteWrongCustomer()
    {
        $quote = $this->getReference(LoadQuoteData::QUOTE1);
        $customerUser = $this->getReference(LoadCustomerUserData::LEVEL_1_1_EMAIL);
        $this->assertNull($this->repository->getQuoteDemandByQuote($quote, $customerUser));
    }
}
