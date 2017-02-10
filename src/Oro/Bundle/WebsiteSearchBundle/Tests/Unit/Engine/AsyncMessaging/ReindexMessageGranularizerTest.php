<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine\AsyncMessaging;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\ReindexMessageGranularizer;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;

class ReindexMessageGranularizerTest extends \PHPUnit_Framework_TestCase
{
    use ContextTrait;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $entityManager;

    /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject */
    private $queryBuilder;

    /**
     * @var array
     */
    private $tenIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

    /**
     * @var array
     */
    private $hundredsIds;

    /**
     * @var ReindexMessageGranularizer
     */
    private $testable;

    public function setUp()
    {
        $this->queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->setMethods(['getResult', 'getQuery', 'select', 'from'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryBuilder->expects($this->any())
            ->method('select')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->any())
            ->method('from')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturnSelf();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->entityManager->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->entityManager);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifierFieldName')
            ->willReturn('id');

        $this->testable = new ReindexMessageGranularizer(
            $this->doctrineHelper
        );

        $this->hundredsIds = range(1, 500);
    }

    /**
     * @dataProvider smallDataProvider
     * @param $input
     * @param $output
     */
    public function testGranulationSmall($input, $output)
    {
        $this->queryBuilder
            ->expects($this->any())
            ->method('getResult')
            ->willReturn($this->tenIds);

        $context = [];
        $context = $this->setContextEntityIds($context, $input['ids']);

        $result = $this->testable->process(
            $input['entities'],
            $input['websites'],
            $context
        );

        $this->assertEquals($output, $result);
    }

    /**
     * @dataProvider smallDataProvider
     * @param $input
     * @param $output
     */
    public function testGranulationSmallWithRealisticDataset($input, $output)
    {
        // use a more realistic dataset that's coming from the DB
        $data = array_filter(
            $this->tenIds,
            function ($e) {
                return ['id'=>$e];
            }
        );

        $this->queryBuilder
            ->expects($this->any())
            ->method('getResult')
            ->willReturn($data);

        $context = [];
        $context = $this->setContextEntityIds($context, $input['ids']);

        $result = $this->testable->process(
            $input['entities'],
            $input['websites'],
            $context
        );

        $this->assertEquals($output, $result);
    }

    /**
     * @dataProvider bigDataProvider
     * @param $input
     * @param $output
     */
    public function testGranulation($input, $output)
    {
        $this->queryBuilder
            ->expects($this->any())
            ->method('getResult')
            ->willReturn($this->hundredsIds);

        $context = [];
        $context = $this->setContextEntityIds($context, $input['ids']);

        $result = $this->testable->process(
            $input['entities'],
            $input['websites'],
            $context
        );

        $this->assertEquals($output, $result);
    }

    /**
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
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [1, 2, 3],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $this->tenIds,
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
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [1, 2, 3],
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
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $this->tenIds,
                        ]
                    ],
                    [
                        'class'   => ['Product2'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $this->tenIds,
                        ]
                    ],
                    [
                        'class'   => ['Product1'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $this->tenIds,
                        ]
                    ],
                    [
                        'class'   => ['Product2'],
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS      => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $this->tenIds,
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
                    'ids'      => []
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
