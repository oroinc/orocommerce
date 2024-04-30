<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository\SuggestionRepository;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Functional\DataFixtures\LoadProductSuggestionsData;

/**
 * @dbIsolationPerTest
 */
final class SuggestionRepositoryTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private SuggestionRepository $suggestionRepository;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadProductData::class,
            LoadProductSuggestionsData::class
        ]);

        $entityManager = self::getContainer()->get('doctrine')
            ->getManagerForClass(Suggestion::class);

        $this->suggestionRepository = $entityManager->getRepository(Suggestion::class);
    }

    public function testThatUniqueSuggestionsSuccessfullyPersisted(): void
    {
        $suggestions = [
            [
                'phrase' => 'phrase1',
                'words_count' => 1,
            ],
            [
                'phrase' => 'phrase2',
                'words_count' => 1,
            ]
        ];

        $result = $this->suggestionRepository->saveSuggestions(1, 1, $suggestions);

        self::assertCount(2, $result['inserted']);
        self::assertCount(0, $result['skipped']);
    }

    public function testThatDuplicatedSuggestionsSuccessfullyPersisted(): void
    {
        $suggestions = [
            [
                'phrase' => 'old phrase',
                'words_count' => 2,
            ],
            [
                'phrase' => 'new phrase',
                'words_count' => 2
            ],
            [
                'phrase' => 'old phrase 2',
                'words_count' => 3,
            ],
            [
                'phrase' => 'new phrase 2',
                'words_count' => 3,
            ],
        ];

        $this->suggestionRepository->saveSuggestions(
            1,
            1,
            [$suggestions[0], $suggestions[2]]
        );

        $result = $this->suggestionRepository->saveSuggestions(
            1,
            1,
            $suggestions
        );

        self::assertCount(2, $result['inserted']);
        self::assertCount(2, $result['skipped']);
    }

    public function testThatSuggestionIdsWithEmptyProductsReturned(): void
    {
        self::assertCount(1, $this->suggestionRepository->getSuggestionIdsWithEmptyProducts());
    }
}
