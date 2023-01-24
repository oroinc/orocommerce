<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Async\Topic;

use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchSaveTopic;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Oro\Bundle\WebsiteSearchBundle\Provider\ReindexationWebsiteProviderInterface;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class WebsiteSearchSaveTopicTest extends AbstractTopicTestCase
{
    private const WEBSITE_IDS = [101, 102];

    private IndexerInputValidator $indexerInputValidator;

    private Proxy|null|\PHPUnit\Framework\MockObject\MockObject $reference = null;

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

        $entityManager = $this->createMock(EntityManager::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $this->reference = $this->createMock(Proxy::class);
        $entityManager
            ->expects(self::any())
            ->method('getReference')
            ->willReturn($this->reference);

        $this->indexerInputValidator = new IndexerInputValidator(
            $websiteProvider,
            $mappingProvider,
            $managerRegistry,
            $reindexationWebsiteProvider,
            $tokenAccessor
        );

        parent::setUp();
    }

    public function getTopic(): WebsiteSearchSaveTopic
    {
        return new WebsiteSearchSaveTopic($this->indexerInputValidator);
    }

    /**
     * @dataProvider validBodyDataProvider
     */
    public function testConfigureMessageBodyWhenValid(array $body, array $expectedBody): void
    {
        $expectedBody['entity'][0] = $this->reference;

        parent::testConfigureMessageBodyWhenValid($body, $expectedBody);
    }

    public function validBodyDataProvider(): array
    {
        return [
            'with required options only' => [
                'body' => [
                    'entity' => [['class' => Product::class, 'id' => 42]],
                ],
                'expectedBody' => [
                    'entity' => [$this->reference],
                    'context' => [AbstractIndexer::CONTEXT_WEBSITE_IDS => self::WEBSITE_IDS],
                ],
            ],
            'context contains website id' => [
                'body' => [
                    'entity' => [['class' => Product::class, 'id' => 42]],
                    'context' => [AbstractIndexer::CONTEXT_WEBSITE_IDS => [42]],
                ],
                'expectedBody' => [
                    'entity' => [$this->reference],
                    'context' => [AbstractIndexer::CONTEXT_WEBSITE_IDS => [42]],
                ],
            ],
            'context contains string entity ids' => [
                'body' => [
                    'entity' => [['class' => Product::class, 'id' => 42]],
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [42],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => ['4242'],
                    ],
                ],
                'expectedBody' => [
                    'entity' => [$this->reference],
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [42],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [4242],
                    ],
                ],
            ],
            'context contains all defined options' => [
                'body' => [
                    'entity' => [['class' => Product::class, 'id' => 42]],
                    'context' => [
                        'skip_pre_processing' => true,
                        AbstractIndexer::CONTEXT_ENTITY_CLASS_KEY => Product::class,
                        AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY => 42,
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [42],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [4242],
                        'fieldGroups' => ['group1', 'group2'],
                    ],
                ],
                'expectedBody' => [
                    'entity' => [$this->reference],
                    'context' => [
                        'skip_pre_processing' => true,
                        AbstractIndexer::CONTEXT_ENTITY_CLASS_KEY => Product::class,
                        AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY => 42,
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [42],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [4242],
                        'fieldGroups' => ['group1', 'group2'],
                    ],
                ],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty body' => [
                'body' => [],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/Option "entity" was not expected to be empty/',
            ],
            'invalid entity' => [
                'body' => ['entity' => 123],
                'exceptionClass' => InvalidArgumentException::class,
                'exceptionMessage' => '/The nested option "entity" with value 123 is expected to be of type array/',
            ],
            'entity missing id' => [
                'body' => ['entity' => [['class' => Product::class]]],
                'exceptionClass' => InvalidArgumentException::class,
                'exceptionMessage' => '/The required option "entity\[0\]\[id\]" is missing./',
            ],
            'entity missing class' => [
                'body' => ['entity' => [['id' => 42]]],
                'exceptionClass' => InvalidArgumentException::class,
                'exceptionMessage' => '/The required option "entity\[0\]\[class\]" is missing./',
            ],
            'invalid context skip_pre_processing' => [
                'body' => [
                    'entity' => [['class' => Product::class, 'id' => 42]],
                    'context' => ['skip_pre_processing' => 123],
                ],
                'exceptionClass' => InvalidArgumentException::class,
                'exceptionMessage' => '/The option "context\[skip_pre_processing\]" with value 123 '
                    . 'is expected to be of type "bool"/',
            ],
            'invalid context websiteIds' => [
                'body' => [
                    'entity' => [['class' => Product::class, 'id' => 42]],
                    'context' => ['skip_pre_processing' => true, AbstractIndexer::CONTEXT_WEBSITE_IDS => false],
                ],
                'exceptionClass' => InvalidArgumentException::class,
                'exceptionMessage' => '/The option "context\[websiteIds\]" with value false '
                    . 'is expected to be of type "int\[\]" or "string\[\]"/',
            ],
            'invalid context entityIds' => [
                'body' => [
                    'entity' => [['class' => Product::class, 'id' => 42]],
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
                    'entity' => [['class' => Product::class, 'id' => 42]],
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
