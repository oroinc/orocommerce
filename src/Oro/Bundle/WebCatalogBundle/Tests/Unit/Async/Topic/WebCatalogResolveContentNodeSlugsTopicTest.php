<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogResolveContentNodeSlugsTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class WebCatalogResolveContentNodeSlugsTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new WebCatalogResolveContentNodeSlugsTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => [
                    WebCatalogResolveContentNodeSlugsTopic::ID => 42,
                    WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => true,
                ],
                'expectedBody' => [
                    WebCatalogResolveContentNodeSlugsTopic::ID => 42,
                    WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => true,
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
                'exceptionMessage' => '/The required options "createRedirect", "id" are missing./',
            ],
            'id has invalid type' => [
                'body' => [
                    WebCatalogResolveContentNodeSlugsTopic::ID => new \stdClass(),
                    WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => true,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "id" with value stdClass is expected '
                    . 'to be of type "int"/',
            ],
            'createRedirect has invalid type' => [
                'body' => [
                    WebCatalogResolveContentNodeSlugsTopic::ID => 42,
                    WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => new \stdClass(),
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "createRedirect" with value stdClass is expected '
                    . 'to be of type "bool"/',
            ],
        ];
    }
}
