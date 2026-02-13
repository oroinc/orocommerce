<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\PricingBundle\Async\Topic\GenerateSinglePriceListPricesByRulesTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class GenerateSinglePriceListPricesByRulesTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new GenerateSinglePriceListPricesByRulesTopic();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function validBodyDataProvider(): array
    {
        return [
            'minimal required fields with int version' => [
                'rawBody' => [
                    'priceListId' => 1,
                    'version' => 100,
                    'jobId' => 50,
                ],
                'expectedMessage' => [
                    'priceListId' => 1,
                    'version' => 100,
                    'jobId' => 50,
                    'products' => [],
                ]
            ],
            'with null version' => [
                'rawBody' => [
                    'priceListId' => 42,
                    'version' => null,
                    'jobId' => 123,
                ],
                'expectedMessage' => [
                    'priceListId' => 42,
                    'version' => null,
                    'jobId' => 123,
                    'products' => [],
                ]
            ],
            'with string version' => [
                'rawBody' => [
                    'priceListId' => 10,
                    'version' => '200',
                    'jobId' => 5,
                ],
                'expectedMessage' => [
                    'priceListId' => 10,
                    'version' => '200',
                    'jobId' => 5,
                    'products' => [],
                ]
            ],
            'with string jobId' => [
                'rawBody' => [
                    'priceListId' => 5,
                    'version' => 50,
                    'jobId' => '456',
                ],
                'expectedMessage' => [
                    'priceListId' => 5,
                    'version' => 50,
                    'jobId' => '456',
                    'products' => [],
                ]
            ],
            'with int products array' => [
                'rawBody' => [
                    'priceListId' => 1,
                    'version' => 100,
                    'jobId' => 10,
                    'products' => [1, 2, 3, 4, 5],
                ],
                'expectedMessage' => [
                    'priceListId' => 1,
                    'version' => 100,
                    'jobId' => 10,
                    'products' => [1, 2, 3, 4, 5],
                ]
            ],
            'with string products array' => [
                'rawBody' => [
                    'priceListId' => 2,
                    'version' => 200,
                    'jobId' => 20,
                    'products' => ['10', '20', '30'],
                ],
                'expectedMessage' => [
                    'priceListId' => 2,
                    'version' => 200,
                    'jobId' => 20,
                    'products' => ['10', '20', '30'],
                ]
            ],
            'all fields with empty products' => [
                'rawBody' => [
                    'priceListId' => 99,
                    'version' => 999,
                    'jobId' => 888,
                    'products' => [],
                ],
                'expectedMessage' => [
                    'priceListId' => 99,
                    'version' => 999,
                    'jobId' => 888,
                    'products' => [],
                ]
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function invalidBodyDataProvider(): array
    {
        return [
            'empty body' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required options "jobId", "priceListId", "version" are missing./',
            ],
            'missing priceListId' => [
                'body' => [
                    'version' => 100,
                    'jobId' => 10,
                ],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "priceListId" is missing./',
            ],
            'missing version' => [
                'body' => [
                    'priceListId' => 1,
                    'jobId' => 10,
                ],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "version" is missing./',
            ],
            'missing jobId' => [
                'body' => [
                    'priceListId' => 1,
                    'version' => 100,
                ],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "jobId" is missing./',
            ],
            'invalid priceListId type' => [
                'body' => [
                    'priceListId' => 'invalid',
                    'version' => 100,
                    'jobId' => 10,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "priceListId" with value "invalid" is expected to be of type "int", ' .
                    'but is of type "string"./',
            ],
            'invalid version type' => [
                'body' => [
                    'priceListId' => 1,
                    'version' => ['invalid'],
                    'jobId' => 10,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "version" with value array is expected to be of type "int" or "null" or "string", ' .
                    'but is of type "array"./',
            ],
            'invalid jobId type' => [
                'body' => [
                    'priceListId' => 1,
                    'version' => 100,
                    'jobId' => ['invalid'],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "jobId" with value array is expected to be of type "int" or "string", ' .
                    'but is of type "array"./',
            ],
            'invalid products type - not array' => [
                'body' => [
                    'priceListId' => 1,
                    'version' => 100,
                    'jobId' => 10,
                    'products' => 'invalid',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "products" with value "invalid" is expected to be of type "int\[\]" or "string\[\]", '
                    . 'but is of type "string"./',
            ],
            'invalid products array - mixed types' => [
                'body' => [
                    'priceListId' => 1,
                    'version' => 100,
                    'jobId' => 10,
                    'products' => [1, 'two', 3],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "products" with value array is expected to be of type "int\[\]" or "string\[\]", ' .
                    'but one of the elements is of type "string|int"./',
            ],
            'invalid products array - boolean elements' => [
                'body' => [
                    'priceListId' => 1,
                    'version' => 100,
                    'jobId' => 10,
                    'products' => [true, false],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "products" with value array is expected to be of type "int\[\]" or "string\[\]", ' .
                    'but one of the elements is of type "bool"./',
            ],
        ];
    }

    public function testGetName(): void
    {
        self::assertSame(
            'oro_pricing.dependent_price_list_price.single_generate',
            $this->getTopic()::getName()
        );
    }

    public function testGetDescription(): void
    {
        self::assertSame(
            'Job task to generate prices for single price list.',
            $this->getTopic()::getDescription()
        );
    }
}
