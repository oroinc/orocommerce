<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Async\Topic;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Oro\Bundle\WebsiteSearchBundle\Provider\ReindexationWebsiteProviderInterface;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;

class WebsiteSearchReindexTopicTest extends AbstractTopicTestCase
{
    private const WEBSITE_IDS = [101, 102];

    private IndexerInputValidator $indexerInputValidator;

    protected function setUp(): void
    {
        $mappingProvider = $this->createMock(SearchMappingProvider::class);
        $reindexationWebsiteProvider = $this->createMock(ReindexationWebsiteProviderInterface::class);
        $tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $tokenAccessor->expects(self::any())
            ->method('getOrganization')
            ->willReturn(null);

        $mappingProvider
            ->expects(self::any())
            ->method('isClassSupported')
            ->willReturnCallback(fn ($class) => class_exists($class, true));
        $mappingProvider
            ->expects(self::any())
            ->method('getEntityClasses')
            ->willReturn([Product::class]);

        $websiteProvider = $this->createMock(WebsiteProviderInterface::class);
        $websiteProvider
            ->expects(self::any())
            ->method('getWebsiteIds')
            ->willReturn(self::WEBSITE_IDS);

        $managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->indexerInputValidator = new IndexerInputValidator(
            $websiteProvider,
            $mappingProvider,
            $managerRegistry,
            $reindexationWebsiteProvider,
            $tokenAccessor
        );

        parent::setUp();
    }

    public function getTopic(): WebsiteSearchReindexTopic
    {
        return new WebsiteSearchReindexTopic($this->indexerInputValidator);
    }

    public function validBodyDataProvider(): array
    {
        return [
            'empty body' => [
                'body' => [],
                'expectedBody' => [
                    'class' => [Product::class],
                    'context' => [AbstractIndexer::CONTEXT_WEBSITE_IDS => self::WEBSITE_IDS],
                    'granulize' => false,
                ],
            ],
            'class is string' => [
                'body' => [
                    'class' => Product::class,
                ],
                'expectedBody' => [
                    'class' => [Product::class],
                    'context' => [AbstractIndexer::CONTEXT_WEBSITE_IDS => self::WEBSITE_IDS],
                    'granulize' => false,
                ],
            ],
            'class is array' => [
                'body' => [
                    'class' => [Product::class],
                ],
                'expectedBody' => [
                    'class' => [Product::class],
                    'context' => [AbstractIndexer::CONTEXT_WEBSITE_IDS => self::WEBSITE_IDS],
                    'granulize' => false,
                ],
            ],
            'context contains website id' => [
                'body' => [
                    'class' => [Product::class],
                    'context' => [AbstractIndexer::CONTEXT_WEBSITE_IDS => [42]],
                ],
                'expectedBody' => [
                    'class' => [Product::class],
                    'context' => [AbstractIndexer::CONTEXT_WEBSITE_IDS => [42]],
                    'granulize' => false,
                ],
            ],
            'context contains string entity ids' => [
                'body' => [
                    'class' => [Product::class],
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [42],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => ['4242'],
                    ],
                ],
                'expectedBody' => [
                    'class' => [Product::class],
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [42],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [4242],
                    ],
                    'granulize' => false,
                ],
            ],
            'context contains all defined options' => [
                'body' => [
                    'jobId' => 321,
                    'class' => [Product::class],
                    'context' => [
                        'skip_pre_processing' => true,
                        AbstractIndexer::CONTEXT_ENTITY_CLASS_KEY => Product::class,
                        AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY => 42,
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [42],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [4242],
                        AbstractIndexer::CONTEXT_FIELD_GROUPS => ['group1', 'group2'],
                    ],
                    'granulize' => true,
                ],
                'expectedBody' => [
                    'jobId' => 321,
                    'class' => [Product::class],
                    'context' => [
                        'skip_pre_processing' => true,
                        AbstractIndexer::CONTEXT_ENTITY_CLASS_KEY => Product::class,
                        AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY => 42,
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [42],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [4242],
                        AbstractIndexer::CONTEXT_FIELD_GROUPS => ['group1', 'group2'],
                    ],
                    'granulize' => true,
                ],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'invalid class' => [
                'body' => ['class' => 123],
                'exceptionClass' => InvalidArgumentException::class,
                'exceptionMessage' => '/The option "class" with value 123 is invalid./',
            ],
            'invalid granulize' => [
                'body' => ['class' => Product::class, 'granulize' => 123],
                'exceptionClass' => InvalidArgumentException::class,
                'exceptionMessage' => '/The option "granulize" with value 123 is expected to be of type "bool"/',
            ],
            'invalid context skip_pre_processing' => [
                'body' => ['class' => Product::class, 'context' => ['skip_pre_processing' => 123]],
                'exceptionClass' => InvalidArgumentException::class,
                'exceptionMessage' => '/The option "context\[skip_pre_processing\]" with value 123 '
                    . 'is expected to be of type "bool"/',
            ],
            'invalid context websiteIds' => [
                'body' => [
                    'class' => Product::class,
                    'context' => ['skip_pre_processing' => true, AbstractIndexer::CONTEXT_WEBSITE_IDS => false],
                ],
                'exceptionClass' => InvalidArgumentException::class,
                'exceptionMessage' => '/The option "context\[websiteIds\]" with value false '
                    . 'is expected to be of type "int\[\]" or "string\[\]"/',
            ],
            'invalid context entityIds' => [
                'body' => [
                    'class' => Product::class,
                    'context' => [
                        'skip_pre_processing' => true,
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [42],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => false,
                    ],
                ],
                'exceptionClass' => InvalidArgumentException::class,
                'exceptionMessage' => '/The option "context\[entityIds\]" with value false '
                    . 'is expected to be of type "int\[\]" or "string\[\]"/',
            ],
            'invalid context currentWebsiteId' => [
                'body' => [
                    'class' => Product::class,
                    'context' => [
                        'skip_pre_processing' => true,
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [42],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [4242],
                        AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY => false,
                    ],
                ],
                'exceptionClass' => InvalidArgumentException::class,
                'exceptionMessage' => '/The option "context\[currentWebsiteId\]" with value false '
                    . 'is expected to be of type "int"/',
            ],
        ];
    }
}
