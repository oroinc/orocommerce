<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\ReindexMessageGranularizer;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class AsyncIndexerTest extends \PHPUnit_Framework_TestCase
{
    const WEBSITE_ID = 1;

    /**
     * @var AsyncIndexer
     */
    private $indexer;

    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messageProducer;

    /**
     * @var IndexerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $baseIndexer;

    /**
     * @var ReindexMessageGranularizer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $granularizer;

    /**
     * @var IndexerInputValidator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $inputValidator;

    public function setUp()
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->baseIndexer = $this->createMock(IndexerInterface::class);

        $websiteRepository = $this->createMock(WebsiteRepository::class);
        $websiteRepository->method('getWebsiteIdentifiers')
            ->willReturn([self::WEBSITE_ID]);

        $this->inputValidator = $this->createMock(IndexerInputValidator::class);

        $this->granularizer = $this->createMock(ReindexMessageGranularizer::class);

        $this->indexer = new AsyncIndexer(
            $this->baseIndexer,
            $this->messageProducer,
            $this->inputValidator,
            $this->granularizer
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
            ->with(AsyncIndexer::TOPIC_SAVE, $expectedParams);

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
            ->with(AsyncIndexer::TOPIC_SAVE, $expectedParams);

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
            ->with(AsyncIndexer::TOPIC_DELETE, $expectedParams);

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
            ->with(AsyncIndexer::TOPIC_DELETE, $expectedParams);

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
            ->with(AsyncIndexer::TOPIC_RESET_INDEX, $expectedParams);

        $this->indexer->resetIndex(Item::class, $context);
    }

    public function testReindex()
    {
        $context = ['test'];

        $expectedParams = [
            'class' => Item::class,
            'context' => [
                'test'
            ]
        ];

        $this->inputValidator->method('validateReindexRequest')
            ->willReturn([
                            [Item::class],
                            [self::WEBSITE_ID]
                         ]);

        $this->granularizer->expects($this->atLeastOnce())
            ->method('process')
            ->with([Item::class], [self::WEBSITE_ID], $context)
            ->willReturn(
                [$expectedParams]
            );

        $this->messageProducer->expects($this->atLeastOnce())
            ->method('send')
            ->with(AsyncIndexer::TOPIC_REINDEX, $expectedParams);

        $this->indexer->reindex(Item::class, $context);
    }
}
