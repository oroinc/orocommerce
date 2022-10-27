<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapByWebsiteAndTypeTopic;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProviderRegistryInterface;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class GenerateSitemapByWebsiteAndTypeTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        $urlProvider = $this->createMock(UrlItemsProviderRegistryInterface::class);
        $urlProvider
            ->expects(self::any())
            ->method('getProvidersIndexedByNames')
            ->willReturn(['type' => 'type']);

        return new GenerateSitemapByWebsiteAndTypeTopic($urlProvider);
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required' => [
                'body' => [
                    'jobId' => 1,
                    'version' => 1,
                    'websiteId' => 1,
                    'type' => 'type',
                ],
                'expectedBody' => [
                    'jobId' => 1,
                    'version' => 1,
                    'websiteId' => 1,
                    'type' => 'type',
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
                'exceptionMessage' => '/The required options "jobId", "type", "version", "websiteId" are missing./',
            ],
            'WithWrongJobIdParameterType' => [
                'body' => [
                    'jobId' => 'qwe',
                    'version' => 1,
                    'websiteId' => 1,
                    'type' => 'type',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "jobId" with value "qwe" is expected to be of type "int", but is of type "string"./',
            ],
            'WithWrongVersionParameterType' => [
                'body' => [
                    'jobId' => 1,
                    'version' => 'qwe',
                    'websiteId' => 1,
                    'type' => 'type',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "version" with value "qwe" ' .
                    'is expected to be of type "int", but is of type "string"./',
            ],
            'WithWrongWebsiteIdParameterType' => [
                'body' => [
                    'jobId' => 1,
                    'version' => 1,
                    'websiteId' => 'qwe',
                    'type' => 'type',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "websiteId" with value "qwe" ' .
                    'is expected to be of type "int", but is of type "string"./',
            ],
            'WithWrongTypeParameterType' => [
                'body' => [
                    'jobId' => 1,
                    'version' => 1,
                    'websiteId' => 1,
                    'type' => 1,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "type" with value 1 is expected to be of type "string", but is of type "int"./',
            ],
            'WithWrongTypeParameterValue' => [
                'body' => [
                    'jobId' => 1,
                    'version' => 1,
                    'websiteId' => 1,
                    'type' => 'qwe',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "type" with value "qwe" is invalid. Accepted values are: "type"./',
            ],
        ];
    }
}
