<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Deletion\DeleteOrphanSuggestionsChunkTopic;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic class which deletes product suggestions.
 */
final class DeleteProductSuggestionsChunkTopicTest extends \PHPUnit\Framework\TestCase
{
    private OptionsResolver $optionsResolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->optionsResolver = new OptionsResolver();

        $topic = new DeleteOrphanSuggestionsChunkTopic();

        $topic->configureMessageBody($this->optionsResolver);
    }

    public function testThatWrapperValidated(): void
    {
        self::expectException(UndefinedOptionsException::class);

        $this->optionsResolver->resolve([
            'ids' => '1234'
        ]);
    }

    /**
     * @dataProvider invalidIdsValue
     */
    public function testThatTypesValidated(mixed $ids): void
    {
        self::expectException(InvalidOptionsException::class);

        $this->optionsResolver->resolve([
            DeleteOrphanSuggestionsChunkTopic::SUGGESTION_IDS => $ids
        ]);
    }

    public function testThatTypesCorrect(): void
    {
        $result = $this->optionsResolver->resolve([
            DeleteOrphanSuggestionsChunkTopic::SUGGESTION_IDS => [2, 3]
        ]);

        self::assertEquals([
            DeleteOrphanSuggestionsChunkTopic::SUGGESTION_IDS => [2, 3]
        ], $result);
    }

    private function invalidIdsValue(): array
    {
        return [
            [
                1,
                [null],
                []
            ]
        ];
    }
}
