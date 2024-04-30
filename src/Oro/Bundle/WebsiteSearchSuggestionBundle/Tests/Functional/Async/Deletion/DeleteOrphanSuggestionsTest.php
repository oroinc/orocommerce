<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Functional\Async\Deletion;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Deletion\DeleteOrphanSuggestionsTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Functional\DataFixtures\LoadProductSuggestionsData;
use Oro\Component\MessageQueue\Client\Message;

final class DeleteOrphanSuggestionsTest extends WebTestCase
{
    use MessageQueueExtension;

    private EntityManager $entityManager;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadProductData::class,
            LoadProductSuggestionsData::class
        ]);

        self::clearMessageCollector();
        self::clearProcessedMessages();

        $this->entityManager = self::getContainer()->get('doctrine')->getManagerForClass(Suggestion::class);
    }

    public function testThatOrphanSuggestionDeleted(): void
    {
        $message = new Message([]);

        self::sendMessage(DeleteOrphanSuggestionsTopic::getName(), $message);

        self::consumeAllMessages();

        $suggestions = $this->entityManager->getRepository(Suggestion::class)->findAll();

        self::assertEquals(
            [$this->getReference(LoadProductSuggestionsData::SUGGESTION_WITH_PRODUCT)],
            $suggestions
        );
    }
}
