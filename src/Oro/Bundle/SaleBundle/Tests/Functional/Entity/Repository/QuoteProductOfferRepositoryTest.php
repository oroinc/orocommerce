<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\Repository\QuoteProductOfferRepository;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductOfferData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class QuoteProductOfferRepositoryTest extends WebTestCase
{
    private QuoteProductOfferRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadQuoteProductOfferData::class,
        ]);

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository(QuoteProductOffer::class);
    }

    public function testFindByQuoteProductOffer(): void
    {
        $expected = [
            $this->getReference('quote_product_product-1')->getId() => new ArrayCollection([
                $this->getReference('quote.product.offer.1'),
            ]),
            $this->getReference('quote_product_product-2')->getId() => new ArrayCollection([
                $this->getReference('quote.product.offer.2'),
            ])
        ];

        $quoteProductIds = [
            $this->getReference('quote_product_product-1')->getId(),
            $this->getReference('quote_product_product-2')->getId(),
        ];

        $result = $this->repository->getProductOffersByQuoteIds($quoteProductIds);
        self::assertEquals($expected, $result);
    }

    public function testFindByQuoteProductOfferWithInvalidQuoteProductId(): void
    {
        $quoteProductIds = [];
        $result = $this->repository->getProductOffersByQuoteIds($quoteProductIds);
        self::assertEmpty($result);
    }
}
