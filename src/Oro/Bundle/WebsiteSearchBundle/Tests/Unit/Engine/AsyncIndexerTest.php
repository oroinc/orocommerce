<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Entity\Item;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\ReindexMessageGranularizer;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class AsyncIndexerTest extends \PHPUnit\Framework\TestCase
{
    const WEBSITE_ID = 1;

    /**
     * @var AsyncIndexer
     */
    private $indexer;

    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageProducer;

    /**
     * @var IndexerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $baseIndexer;

    /**
     * @var SearchMappingProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mappingProvider;

    /**
     * @var WebsiteProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteProvider;

    /**
     * @var IndexerInputValidator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $inputValidator;

    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->baseIndexer = $this->createMock(IndexerInterface::class);

        $websiteRepository = $this->createMock(WebsiteRepository::class);
        $websiteRepository->method('getWebsiteIdentifiers')
            ->willReturn([self::WEBSITE_ID]);

        $this->websiteProvider = $this->createMock(WebsiteProviderInterface::class);
        $this->websiteProvider
            ->expects($this->any())
            ->method('getWebsiteIds')
            ->willReturn([1]);
        $this->mappingProvider = $this->createMock(SearchMappingProvider::class);
        $this->mappingProvider
            ->expects($this->any())
            ->method('isClassSupported')
            ->willReturnCallback(fn ($class) => class_exists($class, true));

        $this->inputValidator = new IndexerInputValidator($this->websiteProvider, $this->mappingProvider);

        $this->granularizer = $this->createMock(ReindexMessageGranularizer::class);

        $this->indexer = new AsyncIndexer(
            $this->baseIndexer,
            $this->messageProducer,
            $this->inputValidator
        );
    }

    public function testSaveOne()
    {
        $entity = $this->createMock(Item::class);
        $entity->method('getId')
            ->willReturn(101);

        $context = ['test'];

        $expectedParams = [
            'entity' =>[
                'class' => get_class($entity),
                'id' => 101
            ],
            'context' => [
                'test'
            ]
        ];

        $this->messageProducer->expects($this->atLeastOnce())
            ->method('send')
            ->with(AsyncIndexer::TOPIC_SAVE, new Message($expectedParams, MessagePriority::NORMAL));

        $this->indexer->save($entity, $context);
    }

    public function testSaveMany()
    {
        $entity1 = $this->createMock(Item::class);
        $entity1->method('getId')
            ->willReturn(101);

        $entity2 = $this->createMock(Item::class);
        $entity2->method('getId')
            ->willReturn(102);

        $context = ['test'];

        $expectedParams = [
            'entity' =>[
                [
                    'class' => get_class($entity1),
                    'id' => 101
                ],
                [
                    'class' => get_class($entity2),
                    'id' => 102
                ]
            ],
            'context' => [
                'test'
            ]
        ];

        $this->messageProducer->expects($this->atLeastOnce())
            ->method('send')
            ->with(AsyncIndexer::TOPIC_SAVE, new Message($expectedParams, MessagePriority::NORMAL));

        $this->indexer->save([$entity1, $entity2], $context);
    }

    public function testDeleteOne()
    {
        $entity = $this->createMock(Item::class);
        $entity->method('getId')
            ->willReturn(101);

        $context = ['test'];

        $expectedParams = [
            'entity' =>[
                'class' => get_class($entity),
                'id' => 101
            ],
            'context' => [
                'test'
            ]
        ];

        $this->messageProducer->expects($this->atLeastOnce())
            ->method('send')
            ->with(AsyncIndexer::TOPIC_DELETE, new Message($expectedParams, MessagePriority::NORMAL));

        $this->indexer->delete($entity, $context);
    }

    public function testDeleteMany()
    {
        $entity1 = $this->createMock(Item::class);
        $entity1->method('getId')
            ->willReturn(101);

        $entity2 = $this->createMock(Item::class);
        $entity2->method('getId')
            ->willReturn(102);

        $context = ['test'];

        $expectedParams = [
            'entity' =>[
                [
                    'class' => get_class($entity1),
                    'id' => 101
                ],
                [
                    'class' => get_class($entity2),
                    'id' => 102
                ]
            ],
            'context' => [
                'test'
            ]
        ];

        $this->messageProducer->expects($this->atLeastOnce())
            ->method('send')
            ->with(AsyncIndexer::TOPIC_DELETE, new Message($expectedParams, MessagePriority::NORMAL));

        $this->indexer->delete([$entity1, $entity2], $context);
    }

    public function testGetClassesForReindex()
    {
        $class = '\StdClass';
        $context = ['foo', 'bar'];

        $this->baseIndexer->expects($this->once())
            ->method('getClassesForReindex')
            ->with($class, $context);

        $this->indexer->getClassesForReindex($class, $context);
    }

    public function testResetReindex()
    {
        $context = ['test'];

        $expectedParams = [
            'class' => Item::class,
            'context' => [
                'test'
            ]
        ];

        $this->messageProducer->expects($this->atLeastOnce())
            ->method('send')
            ->with(AsyncIndexer::TOPIC_RESET_INDEX, new Message($expectedParams, MessagePriority::NORMAL));

        $this->indexer->resetIndex(Item::class, $context);
    }

    public function testReindex()
    {
        $expectedParams = [
            'class' => [Item::class],
            'context' => ['websiteIds' => [1]],
            'granulize' => true
        ];

        $this->messageProducer
            ->expects($this->atLeastOnce())
            ->method('send')
            ->with(AsyncIndexer::TOPIC_REINDEX, new Message($expectedParams, AsyncIndexer::DEFAULT_PRIORITY_REINDEX));

        $this->indexer->reindex(Item::class, []);
    }
}
