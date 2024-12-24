<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine\AsyncMessaging;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\ReindexMessageGranularizer;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\EntityIdentifierRepository;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ReindexMessageGranularizerTest extends TestCase
{
    use ContextTrait;

    private const array IDS_FROM_REPOSITORY = [11, 23];

    private array $tenIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

    private EntityIdentifierRepository&MockObject $identifierRepository;

    private ReindexMessageGranularizer $granularizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->setUpIdentifierRepository();
        $this->granularizer = new ReindexMessageGranularizer($this->identifierRepository);
    }

    public function testGranulationWithFieldGroup(): void
    {
        $context = [];
        $context = $this->setContextEntityIds($context, self::IDS_FROM_REPOSITORY);
        $context = $this->setContextFieldGroups($context, ['main']);

        $result = $this->granularizer->process(
            ['Product'],
            [1],
            $context
        );

        self::assertEquals(
            [
                [
                    'class'   => ['Product'],
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => self::IDS_FROM_REPOSITORY,
                        AbstractIndexer::CONTEXT_FIELD_GROUPS => ['main']
                    ]
                ]
            ],
            iterator_to_array($result)
        );
    }

    public function testGranulationWithBatchSize(): void
    {
        $context = $this->setContextEntityIds($context ?? [], self::IDS_FROM_REPOSITORY);
        $context = $this->setContextBatchSize($context, 10000);

        $result = $this->granularizer->process(
            ['Product'],
            [1],
            $context
        );

        self::assertEquals(
            [
                [
                    'class'   => ['Product'],
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => self::IDS_FROM_REPOSITORY,
                        AbstractIndexer::CONTEXT_BATCH_SIZE => 10000
                    ]
                ]
            ],
            \iterator_to_array($result)
        );

        self::assertEquals(100, ReflectionUtil::getPropertyValue($this->granularizer, 'chunkSize'));
    }

    /**
     * @dataProvider smallDataProvider
     */
    public function testGranulationSmall(array $input, array $output): void
    {
        $context = [];
        $context = $this->setContextEntityIds($context, $input['ids']);

        $result = $this->granularizer->process(
            $input['entities'],
            $input['websites'],
            $context
        );

        self::assertEquals($output, iterator_to_array($result));
    }

    /**
     * @dataProvider bigDataProvider
     */
    public function testGranulation(array $input, array $output): void
    {
        $context = [];
        $context = $this->setContextEntityIds($context, range(1, 500));

        $result = $this->granularizer->process(
            $input['entities'],
            $input['websites'],
            $context
        );

        self::assertEquals($output, iterator_to_array($result));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function smallDataProvider(): array
    {
        return [
            [
                'input'  => [
                    'entities' => ['Product'],
                    'websites' => [1, 2, 3],
                    'ids'      => []
                ],
                'output' => [
                    [
                        'class'   => ['Product'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => self::IDS_FROM_REPOSITORY,
                        ]
                    ],
                    [
                        'class'   => ['Product'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => self::IDS_FROM_REPOSITORY,
                        ]
                    ],
                    [
                        'class'   => ['Product'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [3],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => self::IDS_FROM_REPOSITORY,
                        ]
                    ]
                ]
            ],
            [
                'input'  => [
                    'entities' => ['Product'],
                    'websites' => [1, 2, 3],
                    'ids'      => [4, 5]
                ],
                'output' => [
                    [
                        'class'   => ['Product'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [4, 5],
                        ]
                    ],
                    [
                        'class'   => ['Product'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [4, 5],
                        ]
                    ],
                    [
                        'class'   => ['Product'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [3],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [4, 5],
                        ]
                    ]
                ]
            ],
            [
                'input'  => [
                    'entities' => ['Product1', 'Product2'],
                    'websites' => [1, 2],
                    'ids'      => []
                ],
                'output' => [
                    [
                        'class'   => ['Product1'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => self::IDS_FROM_REPOSITORY,
                        ]
                    ],
                    [
                        'class'   => ['Product1'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => self::IDS_FROM_REPOSITORY,
                        ]
                    ],
                    [
                        'class'   => ['Product2'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => self::IDS_FROM_REPOSITORY,
                        ]
                    ],
                    [
                        'class'   => ['Product2'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => self::IDS_FROM_REPOSITORY,
                        ]
                    ]
                ]
            ],
        ];
    }

    public function bigDataProvider(): array
    {
        return [
            [
                'input'  => [
                    'entities' => ['Product'],
                    'websites' => [1, 2],
                    'ids'      => null, // will be filled in method
                ],
                'output' => [
                    [
                        'class'   => ['Product'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => range(1, 100),
                        ]
                    ],
                    [
                        'class'   => ['Product'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => range(1, 100),
                        ]
                    ],
                    [
                        'class'   => ['Product'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => range(101, 200),
                        ]
                    ],
                    [
                        'class'   => ['Product'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => range(101, 200),
                        ]
                    ],
                    [
                        'class'   => ['Product'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => range(201, 300),
                        ]
                    ],
                    [
                        'class'   => ['Product'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => range(201, 300),
                        ]
                    ],
                    [
                        'class'   => ['Product'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => range(301, 400),
                        ]
                    ],
                    [
                        'class'   => ['Product'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => range(301, 400),
                        ]
                    ],
                    [
                        'class'   => ['Product'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => range(401, 500),
                        ]
                    ],
                    [
                        'class'   => ['Product'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => range(401, 500),
                        ]
                    ]
                ]
            ]
        ];
    }

    private function setUpIdentifierRepository(): void
    {
        $this->identifierRepository = $this->createMock(EntityIdentifierRepository::class);
        $this->identifierRepository->expects(self::any())
            ->method('getIds')
            ->willReturnCallback(fn () => $this->createIdsFromRepositoryIterator());
    }

    private function createIdsFromRepositoryIterator(): BufferedQueryResultIteratorInterface
    {
        $bufferedIterator = $this->createMock(BufferedQueryResultIteratorInterface::class);
        $arrayIterator = new \ArrayIterator(self::IDS_FROM_REPOSITORY);

        $bufferedIterator
            ->method('rewind')
            ->willReturnCallback(function () use ($arrayIterator): void {
                $arrayIterator->rewind();
            });

        $bufferedIterator
            ->method('current')
            ->willReturnCallback(function () use ($arrayIterator) {
                return $arrayIterator->current();
            });

        $bufferedIterator
            ->method('key')
            ->willReturnCallback(function () use ($arrayIterator) {
                return $arrayIterator->key();
            });

        $bufferedIterator
            ->method('next')
            ->willReturnCallback(function () use ($arrayIterator): void {
                $arrayIterator->next();
            });

        $bufferedIterator
            ->method('valid')
            ->willReturnCallback(function () use ($arrayIterator): bool {
                return $arrayIterator->valid();
            });

        return $bufferedIterator;
    }
}
