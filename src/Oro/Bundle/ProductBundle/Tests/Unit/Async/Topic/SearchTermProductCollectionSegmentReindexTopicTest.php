<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async\Topic;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Async\Topic\SearchTermProductCollectionSegmentReindexTopic;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\SearchTermStub;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\Repository\SearchTermRepository;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class SearchTermProductCollectionSegmentReindexTopicTest extends AbstractTopicTestCase
{
    private const SEARCH_TERM_ID = 42;

    private ManagerRegistry|MockObject $doctrine;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $repo = $this->createMock(SearchTermRepository::class);
        $this->doctrine
            ->expects(self::any())
            ->method('getRepository')
            ->with(SearchTerm::class)
            ->willReturn($repo);

        $repo
            ->expects(self::any())
            ->method('find')
            ->willReturnMap([
                [self::SEARCH_TERM_ID, null, null, new SearchTermStub(self::SEARCH_TERM_ID)],
                [10, null, null, null],
            ]);

        parent::setUp();
    }

    #[\Override]
    protected function getTopic(): TopicInterface
    {
        return new SearchTermProductCollectionSegmentReindexTopic($this->doctrine);
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        return [
            'required options' => [
                'body' => [
                    SearchTermProductCollectionSegmentReindexTopic::SEARCH_TERM_ID => self::SEARCH_TERM_ID,
                ],
                'expectedBody' => [
                    SearchTermProductCollectionSegmentReindexTopic::SEARCH_TERM_ID => self::SEARCH_TERM_ID,
                    SearchTermProductCollectionSegmentReindexTopic::SEARCH_TERM =>
                        new SearchTermStub(self::SEARCH_TERM_ID),
                ],
            ],
        ];
    }

    #[\Override]
    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "search_term_id" is missing./',
            ],
            'invalid "search_term_id" type' => [
                'body' => [
                    SearchTermProductCollectionSegmentReindexTopic::SEARCH_TERM_ID => 'test',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "search_term_id" with value "test" ' .
                    'is expected to be of type "int", but is of type "string"./',
            ],
            'search term is not found' => [
                'body' => [
                    SearchTermProductCollectionSegmentReindexTopic::SEARCH_TERM_ID => 10,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/Search Term #10 is not found/',
            ],
        ];
    }
}
