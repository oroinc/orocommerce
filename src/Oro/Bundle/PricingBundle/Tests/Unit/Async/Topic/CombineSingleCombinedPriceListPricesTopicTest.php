<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async\Topic;

use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\MessageQueueBundle\Compatibility\TopicInterface;
use Oro\Bundle\PricingBundle\Async\Topic\CombineSingleCombinedPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CombineSingleCombinedPriceListPricesTopicTest extends AbstractTopicTestCase
{
    use EntityTrait;

    private CombinedPriceListProvider $combinedPriceListProvider;

    protected function getTopic(): TopicInterface
    {
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);

        $this->combinedPriceListProvider = $this->createMock(CombinedPriceListProvider::class);
        $this->combinedPriceListProvider->expects($this->any())
            ->method('getCombinedPriceListById')
            ->willReturn($cpl);
        $this->combinedPriceListProvider->expects($this->any())
            ->method('getCombinedPriceListByCollectionInformation')
            ->willReturn($cpl);

        return new CombineSingleCombinedPriceListPricesTopic($this->combinedPriceListProvider);
    }

    public function testConfigureMessageBodyForEntityNotFoundException()
    {
        /** @var MockObject|CombinedPriceListProvider $combinedPriceListProvider */
        $combinedPriceListProvider = $this->createMock(CombinedPriceListProvider::class);
        $combinedPriceListProvider->expects($this->any())
            ->method('getCombinedPriceListByCollectionInformation')
            ->willThrowException(EntityNotFoundException::fromClassNameAndIdentifier(PriceList::class, ['id' => 1]));

        $topic = new CombineSingleCombinedPriceListPricesTopic($combinedPriceListProvider);
        $optionsResolver = new OptionsResolver();

        $topic->configureMessageBody($optionsResolver);

        $body = [
            'jobId' => 100,
            'collection' => [['p' => 1, 'm' => true]],
            'assign_to' => ['config' => true]
        ];
        $expectedBody = [
            'jobId' => 100,
            'cpl' => null,
            'assign_to' => ['config' => true],
            'products' => [],
            'collection' => [['p' => 1, 'm' => true]],
        ];
        self::assertEquals($expectedBody, $optionsResolver->resolve($body));
    }

    public function validBodyDataProvider(): array
    {
        return [
            'provided CPL' => [
                'rawBody' => [
                    'jobId' => 100,
                    'cpl' => 1
                ],
                'expectedMessage' => [
                    'jobId' => 100,
                    'cpl' => $this->getEntity(CombinedPriceList::class, ['id' => 1]),
                    'products' => [],
                    'assign_to' => []
                ]
            ],

            'provided CPL with products' => [
                'rawBody' => [
                    'jobId' => 100,
                    'cpl' => 1,
                    'products' => [1, 2]
                ],
                'expectedMessage' => [
                    'jobId' => 100,
                    'cpl' => $this->getEntity(CombinedPriceList::class, ['id' => 1]),
                    'products' => [1, 2],
                    'assign_to' => []
                ]
            ],

            'provided empty collection with assignments' => [
                'rawBody' => [
                    'jobId' => 100,
                    'collection' => [],
                    'assign_to' => ['config' => true]
                ],
                'expectedMessage' => [
                    'jobId' => 100,
                    'cpl' => $this->getEntity(CombinedPriceList::class, ['id' => 1]),
                    'assign_to' => ['config' => true],
                    'products' => [],
                    'collection' => []
                ]
            ],

            'provided collection with assignments' => [
                'rawBody' => [
                    'jobId' => 100,
                    'collection' => [['p' => 1, 'm' => true]],
                    'assign_to' => ['config' => true]
                ],
                'expectedMessage' => [
                    'jobId' => 100,
                    'cpl' => $this->getEntity(CombinedPriceList::class, ['id' => 1]),
                    'assign_to' => ['config' => true],
                    'products' => [],
                    'collection' => [['p' => 1, 'm' => true]],
                ]
            ]
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty body' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required option "jobId" is missing./',
            ]
        ];
    }
}
