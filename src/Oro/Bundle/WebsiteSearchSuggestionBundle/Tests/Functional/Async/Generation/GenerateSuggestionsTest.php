<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Functional\Async\Generation;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\GenerateSuggestionsTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository\SuggestionRepository;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion;

final class GenerateSuggestionsTest extends WebTestCase
{
    use MessageQueueExtension;

    private SuggestionRepository $suggestionRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadProductData::class
        ]);

        self::clearMessageCollector();
        self::clearProcessedMessages();

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(Suggestion::class);
        $this->suggestionRepository = $entityManager->getRepository(Suggestion::class);
    }

    public function testThatSuggestionsFullyCreated(): void
    {
        self::sendMessage(GenerateSuggestionsTopic::getName(), [
            'ids' => [$this->getReference(LoadProductData::PRODUCT_1)->getId()]
        ]);
        self::consumeAllMessages();

        self::assertCount(
            6,
            $this->suggestionRepository->findAll(),
            'Test that suggestions for specified product created'
        );

        self::sendMessage(GenerateSuggestionsTopic::getName(), []);
        self::consumeAllMessages();

        self::assertGreaterThan(
            6,
            count($this->suggestionRepository->findAll()),
            'Test that the rest suggestions'
        );
    }
}
