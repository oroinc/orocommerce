<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Unit\Async\Generation;

use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Generation\PersistSuggestionProductRelationProcessor;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\PersistProductsSuggestionRelationChunkTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Persister\ProductSuggestionPersister;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

final class PersistSuggestionProductRelationProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testThatPersistCalled(): void
    {
        $message = new Message();
        $message->setBody([
            PersistProductsSuggestionRelationChunkTopic::PRODUCTS_WRAPPER => [
                1 => [1, 2, 3]
            ]
        ]);

        $productSuggestionPersister = $this->createMock(ProductSuggestionPersister::class);

        $processor = new PersistSuggestionProductRelationProcessor($productSuggestionPersister);

        $productSuggestionPersister
            ->expects(self::once())
            ->method('persistProductSuggestions')
            ->with([
                1 => [1, 2, 3]
            ]);

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $processor->process($message, $this->createMock(SessionInterface::class))
        );
    }
}
