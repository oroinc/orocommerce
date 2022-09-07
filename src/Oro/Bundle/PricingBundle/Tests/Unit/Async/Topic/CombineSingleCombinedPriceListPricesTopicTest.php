<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async\Topic;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\PricingBundle\Async\Topic\CombineSingleCombinedPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
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

    public function testConfigureMessageBodyForUnexpectedDbException()
    {
        $e = $this->createMock(ForeignKeyConstraintViolationException::class);

        /** @var MockObject|CombinedPriceListProvider $combinedPriceListProvider */
        $combinedPriceListProvider = $this->createMock(CombinedPriceListProvider::class);
        $combinedPriceListProvider->expects($this->any())
            ->method('getCombinedPriceListByCollectionInformation')
            ->willThrowException($e);

        $topic = new CombineSingleCombinedPriceListPricesTopic($combinedPriceListProvider);
        $optionsResolver = new OptionsResolver();

        $topic->configureMessageBody($optionsResolver);

        $body = [
            'jobId' => 100,
            'collection' => [['p' => 1, 'm' => true]],
            'assign_to' => ['config' => true],
            'version' => 1
        ];
        $expectedBody = [
            'jobId' => 100,
            'cpl' => false,
            'assign_to' => ['config' => true],
            'products' => [],
            'collection' => [['p' => 1, 'm' => true]],
            'version' => 1
        ];
        $actual = $optionsResolver->resolve($body);
        self::assertEquals($expectedBody, $actual);
        self::assertFalse($actual['cpl']);
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
            'assign_to' => ['config' => true],
            'version' => 1,
        ];
        $expectedBody = [
            'jobId' => 100,
            'cpl' => null,
            'assign_to' => ['config' => true],
            'products' => [],
            'collection' => [['p' => 1, 'm' => true]],
            'version' => 1,
        ];
        $actual = $optionsResolver->resolve($body);
        self::assertEquals($expectedBody, $actual);
        self::assertNull($actual['cpl']);
    }

    public function validBodyDataProvider(): array
    {
        return [
            'provided CPL' => [
                'rawBody' => [
                    'jobId' => 100,
                    'cpl' => 1,
                    'version' => 1
                ],
                'expectedMessage' => [
                    'jobId' => 100,
                    'cpl' => $this->getEntity(CombinedPriceList::class, ['id' => 1]),
                    'products' => [],
                    'collection' => [],
                    'assign_to' => [],
                    'version' => null
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
                    'collection' => [],
                    'assign_to' => [],
                    'version' => null
                ]
            ],

            'provided empty collection with assignments' => [
                'rawBody' => [
                    'jobId' => 100,
                    'collection' => [],
                    'assign_to' => ['config' => true],
                    'version' => 1
                ],
                'expectedMessage' => [
                    'jobId' => 100,
                    'cpl' => $this->getEntity(CombinedPriceList::class, ['id' => 1]),
                    'assign_to' => ['config' => true],
                    'products' => [],
                    'collection' => [],
                    'version' => 1
                ]
            ],

            'provided collection with assignments' => [
                'rawBody' => [
                    'jobId' => 100,
                    'collection' => [['p' => 1, 'm' => true]],
                    'assign_to' => ['config' => true],
                    'version' => 1
                ],
                'expectedMessage' => [
                    'jobId' => 100,
                    'cpl' => $this->getEntity(CombinedPriceList::class, ['id' => 1]),
                    'assign_to' => ['config' => true],
                    'products' => [],
                    'collection' => [['p' => 1, 'm' => true]],
                    'version' => 1
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
            ],

            'incorrect collection' => [
                'body' => [
                    'jobId' => 100,
                    'collection' => [['pl' => 1, 'merge' => true]]
                ],
                'exceptionClass' => UndefinedOptionsException::class,
                'exceptionMessage' => '/The options "collection\[0\]\[merge\]", "collection\[0\]\[pl\]" do not exist. '
                    . 'Defined options are: "m", "p"./',
            ]
        ];
    }
}
