<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine\AsyncMessaging;

use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\ReindexMessageGranularizer;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\EntityIdentifierRepository;

class ReindexMessageGranularizerTest extends \PHPUnit\Framework\TestCase
{
    const IDS_FROM_REPOSITORY = [11, 23];

    use ContextTrait;

    /**
     * @var array
     */
    private $tenIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

    /**
     * @var ReindexMessageGranularizer
     */
    private $testable;

    /**
     * @var EntityIdentifierRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $identifierRepository;

    protected function setUp(): void
    {
        $this->identifierRepository = $this->getMockBuilder(EntityIdentifierRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->testable = new ReindexMessageGranularizer($this->identifierRepository);
    }

    /**
     * @dataProvider smallDataProvider
     */
    public function testGranulationSmall($input, $output)
    {
        $context = [];
        $context = $this->setContextEntityIds($context, $input['ids']);

        $this->identifierRepository->expects($this->any())
            ->method('getIds')
            ->willReturn(self::IDS_FROM_REPOSITORY);

        $result = $this->testable->process(
            $input['entities'],
            $input['websites'],
            $context
        );

        $this->assertEquals($output, iterator_to_array($result));
    }

    /**
     * @dataProvider bigDataProvider
     */
    public function testGranulation($input, $output)
    {
        $context = [];
        $context = $this->setContextEntityIds($context, range(1, 500));

        $this->identifierRepository->expects($this->any())
            ->method('getIds')
            ->willReturn(self::IDS_FROM_REPOSITORY);

        $result = $this->testable->process(
            $input['entities'],
            $input['websites'],
            $context
        );

        $this->assertEquals($output, iterator_to_array($result));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function smallDataProvider()
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

    /**
     * @return array
     */
    public function bigDataProvider()
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
