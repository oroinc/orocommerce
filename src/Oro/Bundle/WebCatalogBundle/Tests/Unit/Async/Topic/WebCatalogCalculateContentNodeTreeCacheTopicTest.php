<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Async\Topic;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateContentNodeTreeCacheTopic;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class WebCatalogCalculateContentNodeTreeCacheTopicTest extends AbstractTopicTestCase
{
    private const CONTENT_NODE_ID = 4242;
    private const SCOPE_ID = 424242;

    private ContentNode $contentNode;

    private Scope $scope;

    protected function getTopic(): TopicInterface
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $scopeEntityManager = $this->createMock(EntityManagerInterface::class);
        $contentNodeEntityManager = $this->createMock(EntityManagerInterface::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [Scope::class, $scopeEntityManager],
                [ContentNode::class, $contentNodeEntityManager],
            ]);

        $this->contentNode = new ContentNode();
        $contentNodeEntityManager
            ->expects(self::any())
            ->method('find')
            ->willReturnMap([[ContentNode::class, self::CONTENT_NODE_ID, $this->contentNode]]);

        $this->scope = new Scope();
        $scopeEntityManager
            ->expects(self::any())
            ->method('find')
            ->willReturnMap([[Scope::class, self::SCOPE_ID, $this->scope]]);

        return new WebCatalogCalculateContentNodeTreeCacheTopic($managerRegistry);
    }

    /**
     * @dataProvider validBodyDataProvider
     */
    public function testConfigureMessageBodyWhenValid(array $body, array $expectedBody): void
    {
        $expectedBody[WebCatalogCalculateContentNodeTreeCacheTopic::CONTENT_NODE] = $this->contentNode;
        $expectedBody[WebCatalogCalculateContentNodeTreeCacheTopic::SCOPE] = $this->scope;

        parent::testConfigureMessageBodyWhenValid($body, $expectedBody);
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    WebCatalogCalculateContentNodeTreeCacheTopic::JOB_ID => 42,
                    WebCatalogCalculateContentNodeTreeCacheTopic::CONTENT_NODE => self::CONTENT_NODE_ID,
                    WebCatalogCalculateContentNodeTreeCacheTopic::SCOPE => self::SCOPE_ID,
                ],
                'expectedBody' => [
                    WebCatalogCalculateContentNodeTreeCacheTopic::JOB_ID => 42,
                    WebCatalogCalculateContentNodeTreeCacheTopic::CONTENT_NODE => self::CONTENT_NODE_ID,
                    WebCatalogCalculateContentNodeTreeCacheTopic::SCOPE => self::SCOPE_ID,
                ],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required options "contentNode", "jobId", "scope" are missing./',
            ],
            'jobId has invalid type' => [
                'body' => [
                    WebCatalogCalculateContentNodeTreeCacheTopic::JOB_ID => new \stdClass(),
                    WebCatalogCalculateContentNodeTreeCacheTopic::CONTENT_NODE => self::CONTENT_NODE_ID,
                    WebCatalogCalculateContentNodeTreeCacheTopic::SCOPE => self::SCOPE_ID,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "jobId" with value stdClass is expected to be of type "int"/',
            ],
            'contentNode has invalid type' => [
                'body' => [
                    WebCatalogCalculateContentNodeTreeCacheTopic::JOB_ID => 42,
                    WebCatalogCalculateContentNodeTreeCacheTopic::CONTENT_NODE => new \stdClass(),
                    WebCatalogCalculateContentNodeTreeCacheTopic::SCOPE => self::SCOPE_ID,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "contentNode" with value stdClass is expected to be of type "int"/',
            ],
            'scope has invalid type' => [
                'body' => [
                    WebCatalogCalculateContentNodeTreeCacheTopic::JOB_ID => 42,
                    WebCatalogCalculateContentNodeTreeCacheTopic::CONTENT_NODE => self::CONTENT_NODE_ID,
                    WebCatalogCalculateContentNodeTreeCacheTopic::SCOPE => new \stdClass(),
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "scope" with value stdClass is expected to be of type "int"/',
            ],
            'contentNode is not found' => [
                'body' => [
                    WebCatalogCalculateContentNodeTreeCacheTopic::JOB_ID => 42,
                    WebCatalogCalculateContentNodeTreeCacheTopic::CONTENT_NODE => 101,
                    WebCatalogCalculateContentNodeTreeCacheTopic::SCOPE => self::SCOPE_ID,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => sprintf(
                    '/The option "contentNode" could not be normalized: '
                    . 'the entity %s #101 is not found/',
                    preg_quote(ContentNode::class, '/')
                ),
            ],
            'scope is not found' => [
                'body' => [
                    WebCatalogCalculateContentNodeTreeCacheTopic::JOB_ID => 42,
                    WebCatalogCalculateContentNodeTreeCacheTopic::CONTENT_NODE => self::CONTENT_NODE_ID,
                    WebCatalogCalculateContentNodeTreeCacheTopic::SCOPE => 202,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => sprintf(
                    '/The option "scope" could not be normalized: '
                    . 'the entity %s #202 is not found/',
                    preg_quote(Scope::class, '/')
                ),
            ],
        ];
    }
}
