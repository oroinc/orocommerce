<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\PricingBundle\Async\Topic\ActualizeCombinedPriceListTopic;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ActualizeCombinedPriceListTopicTest extends AbstractTopicTestCase
{
    use EntityTrait;

    private CombinedPriceListProvider $combinedPriceListProvider;

    #[\Override]
    protected function getTopic(): TopicInterface
    {
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);

        $this->combinedPriceListProvider = $this->createMock(CombinedPriceListProvider::class);
        $this->combinedPriceListProvider->expects($this->any())
            ->method('getCombinedPriceListById')
            ->willReturn($cpl);

        return new ActualizeCombinedPriceListTopic($this->combinedPriceListProvider);
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        return [
            'provided CPL' => [
                'rawBody' => [
                    'cpl' => [1],
                ],
                'expectedMessage' => [
                    'cpl' => [$this->getEntity(CombinedPriceList::class, ['id' => 1])],
                ]
            ]
        ];
    }

    #[\Override]
    public function invalidBodyDataProvider(): array
    {
        return [
            'empty body' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required option "cpl" is missing./',
            ]
        ];
    }

    public function testCreateJobName()
    {
        $messageBody = ['cpl' => [3, 1]];

        $topic = $this->getTopic();
        $this->assertInstanceOf(JobAwareTopicInterface::class, $topic);
        $this->assertEquals($topic::getName() . ':' . md5(json_encode([1, 3])), $topic->createJobName($messageBody));
    }
}
