<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\ProductSuggestion;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository\ProductSuggestionRepository;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Functional\DataFixtures\LoadProductSuggestionsData;

/**
 * @dbIsolationPerTest
 */
final class ProductSuggestionRepositoryTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ProductSuggestionRepository $productSuggestionRepository;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadProductSuggestionsData::class
        ]);

        $entityManager = self::getContainer()->get('doctrine')
            ->getManagerForClass(ProductSuggestion::class);

        $this->productSuggestionRepository = $entityManager->getRepository(ProductSuggestion::class);
    }

    public function testThatProductsSuggestionsRemoved(): void
    {
        $this->productSuggestionRepository->clearProductSuggestionsByProductIds([
            $this->getReference(LoadProductData::PRODUCT_1)->getId()
        ]);

        self::assertEquals([], $this->productSuggestionRepository->findAll());
    }

    public function testThatProductSuggestionInserted(): void
    {
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        $suggestion = $this->getReference(LoadProductSuggestionsData::SUGGESTION_WITH_PRODUCT);

        $insertedIds = $this->productSuggestionRepository->insertProductSuggestions(
            [$suggestion->getId() => [$product1->getId(), $product2->getId()]]
        );

        self::assertCount(1, $insertedIds);
    }
}
