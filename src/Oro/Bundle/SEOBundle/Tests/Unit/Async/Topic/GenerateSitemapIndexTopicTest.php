<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapIndexTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class GenerateSitemapIndexTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new GenerateSitemapIndexTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required' => [
                'body' => [
                    'jobId' => 1,
                    'version' => 1,
                    'websiteIds' => [1, 2, 3],
                ],
                'expectedBody' => [
                    'jobId' => 1,
                    'version' => 1,
                    'websiteIds' => [1, 2, 3],
                ],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'WithoutParameters' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required options "jobId", "version", "websiteIds" are missing./',
            ],
            'WithWrongJobIdParameterType' => [
                'body' => [
                    'jobId' => 'qwe',
                    'version' => 1,
                    'websiteIds' => [1, 2, 3],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "jobId" with value "qwe" ' .
                    'is expected to be of type "int", but is of type "string"./',
            ],
            'WithWrongVersionParameterType' => [
                'body' => [
                    'jobId' => 1,
                    'version' => 'qwe',
                    'websiteIds' => [1, 2, 3],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "version" with value "qwe" ' .
                    'is expected to be of type "int", but is of type "string"./',
            ],
            'WithWrongWebsiteIdsParameterType' => [
                'body' => [
                    'jobId' => 1,
                    'version' => 1,
                    'websiteIds' => 'qwe',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "websiteIds" with value "qwe" ' .
                    'is expected to be of type "array", but is of type "string"./',
            ],
        ];
    }
}
