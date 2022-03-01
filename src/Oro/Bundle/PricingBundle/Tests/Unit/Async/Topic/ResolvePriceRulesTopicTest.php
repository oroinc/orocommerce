<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ResolvePriceRulesTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new ResolvePriceRulesTopic();
    }

    public function validBodyDataProvider(): array
    {
        return [
            [
                'rawBody' => ['product' => [1 => [10, 20], 50 => [10, 300]]],
                'expectedMessage' => ['product' => [1 => [10, 20], 50 => [10, 300]]]
            ]
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "product" is missing./',
            ]
        ];
    }
}
