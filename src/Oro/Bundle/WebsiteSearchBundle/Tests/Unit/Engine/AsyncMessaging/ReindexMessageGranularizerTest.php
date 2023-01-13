<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine\AsyncMessaging;

use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\ReindexMessageGranularizer;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\EntityIdentifierRepository;

class ReindexMessageGranularizerTest extends \PHPUnit\Framework\TestCase
{
    use ContextTrait;

    private const IDS_FROM_REPOSITORY = [11, 23];

    private array $tenIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

    /** @var EntityIdentifierRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $identifierRepository;

    /** @var ReindexMessageGranularizer */
    private $granularizer;

    protected function setUp(): void
    {
        $this->identifierRepository = $this->createMock(EntityIdentifierRepository::class);

        $this->granularizer = new ReindexMessageGranularizer($this->identifierRepository);
    }

    public function testGranulationWithFieldGroup()
    {
        $context = [];
        $context = $this->setContextEntityIds($context, self::IDS_FROM_REPOSITORY);
        $context = $this->setContextFieldGroups($context, ['main']);

        $this->identifierRepository->expects($this->any())
            ->method('getIds')
            ->willReturn(self::IDS_FROM_REPOSITORY);

        $result = $this->granularizer->process(
            ['Product'],
            [1],
            $context
        );

        $this->assertEquals(
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

    /**
     * @dataProvider smallDataProvider
     */
    public function testGranulationSmall(array $input, array $output)
    {
        $context = [];
        $context = $this->setContextEntityIds($context, $input['ids']);

        $this->identifierRepository->expects($this->any())
            ->method('getIds')
            ->willReturn(self::IDS_FROM_REPOSITORY);

        $result = $this->granularizer->process(
            $input['entities'],
            $input['websites'],
            $context
        );

        $this->assertEquals($output, iterator_to_array($result));
    }

    /**
     * @dataProvider bigDataProvider
     */
    public function testGranulation(array $input, array $output)
    {
        $context = [];
        $context = $this->setContextEntityIds($context, range(1, 500));

        $this->identifierRepository->expects($this->any())
            ->method('getIds')
            ->willReturn(self::IDS_FROM_REPOSITORY);

        $result = $this->granularizer->process(
            $input['entities'],
            $input['websites'],
            $context
        );

        $this->assertEquals($output, iterator_to_array($result));
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
                        'class'   => ['Product2'],
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
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [1],
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
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [1],
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
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => range(1, 100),
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
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => range(201, 300),
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
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => range(401, 500),
                        ]
                    ]
                ]
            ]
        ];
    }
}
