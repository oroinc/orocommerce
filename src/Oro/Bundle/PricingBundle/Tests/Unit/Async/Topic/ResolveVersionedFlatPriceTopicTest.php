<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\PricingBundle\Async\Topic\ResolveVersionedFlatPriceTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ResolveVersionedFlatPriceTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new ResolveVersionedFlatPriceTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            [
                'rawBody' => ['version' => 1, 'priceLists' => [1, 2, 3]],
                'expectedMessage' => ['version' => 1, 'priceLists' => [1, 2, 3]]
            ]
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required options "priceLists", "version" are missing./',
            ],
            [
                'body' => ['version' => 'string', 'priceLists' => [1]],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "version" with value "string" is expected to be of type "int", '
                    . 'but is of type "string"./',
            ],
            [
                'body' => ['version' => 1, 'priceLists' => ['string']],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "priceLists" with value array is expected to be of type "int\[\]", but one of the ' .
                    'elements is of type "string"/',
            ]
        ];
    }

    public function testCreateJobName(): void
    {
        self::assertSame(
            'oro_pricing.flat_price.resolve:v42',
            $this->getTopic()->createJobName(['version' => 42])
        );
    }
}
