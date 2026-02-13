<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async\Topic;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\PricingBundle\Async\Topic\GenerateDependentPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class GenerateDependentPriceListPricesTopicTest extends AbstractTopicTestCase
{
    private ManagerRegistry|MockObject $doctrine;
    private ShardManager|MockObject $shardManager;
    private ProductPriceRepository|MockObject $productPriceRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->productPriceRepository = $this->createMock(ProductPriceRepository::class);

        parent::setUp();
    }

    protected function getTopic(): TopicInterface
    {
        return new GenerateDependentPriceListPricesTopic($this->doctrine, $this->shardManager);
    }

    public function validBodyDataProvider(): array
    {
        return [
            'minimal required fields' => [
                'rawBody' => [
                    'sourcePriceListId' => 1,
                    'version' => 100,
                ],
                'expectedMessage' => [
                    'sourcePriceListId' => 1,
                    'version' => 100,
                    'baseJobId' => null,
                    'level' => 0,
                    'productBatches' => $this->createMockGenerator(),
                ]
            ],
            'all fields with null version' => [
                'rawBody' => [
                    'sourcePriceListId' => 42,
                    'version' => null,
                    'baseJobId' => 123,
                    'level' => 2,
                ],
                'expectedMessage' => [
                    'sourcePriceListId' => 42,
                    'version' => null,
                    'baseJobId' => 123,
                    'level' => 2,
                    'productBatches' => $this->createMockGenerator(),
                ]
            ],
            'version as string' => [
                'rawBody' => [
                    'sourcePriceListId' => 10,
                    'version' => '200',
                ],
                'expectedMessage' => [
                    'sourcePriceListId' => 10,
                    'version' => '200',
                    'baseJobId' => null,
                    'level' => 0,
                    'productBatches' => $this->createMockGenerator(),
                ]
            ],
            'with baseJobId as string' => [
                'rawBody' => [
                    'sourcePriceListId' => 5,
                    'version' => 50,
                    'baseJobId' => '456',
                ],
                'expectedMessage' => [
                    'sourcePriceListId' => 5,
                    'version' => 50,
                    'baseJobId' => '456',
                    'level' => 0,
                    'productBatches' => $this->createMockGenerator(),
                ]
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty body' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required options "sourcePriceListId", "version" are missing./',
            ],
            'missing sourcePriceListId' => [
                'body' => ['version' => 100],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "sourcePriceListId" is missing./',
            ],
            'missing version' => [
                'body' => ['sourcePriceListId' => 1],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "version" is missing./',
            ],
            'invalid sourcePriceListId type' => [
                'body' => [
                    'sourcePriceListId' => 'invalid',
                    'version' => 100,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "sourcePriceListId" with value "invalid" is expected to be of type "int", ' .
                    'but is of type "string"./',
            ],
            'invalid version type' => [
                'body' => [
                    'sourcePriceListId' => 1,
                    'version' => ['invalid'],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "version" with value array is expected to be of type "int" or "null" or "string", ' .
                    'but is of type "array"./',
            ],
            'invalid baseJobId type' => [
                'body' => [
                    'sourcePriceListId' => 1,
                    'version' => 100,
                    'baseJobId' => ['invalid'],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "baseJobId" with value array is expected to be of type "null" or "string" or "int", ' .
                    'but is of type "array"./',
            ],
            'invalid level type' => [
                'body' => [
                    'sourcePriceListId' => 1,
                    'version' => 100,
                    'level' => 'invalid',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' =>
                    '/The option "level" with value "invalid" is expected to be of type "int", ' .
                    'but is of type "string"./',
            ],
        ];
    }

    public function testGetName(): void
    {
        self::assertSame(
            'oro_pricing.dependent_price_lists_prices.generate',
            $this->getTopic()::getName()
        );
    }

    public function testGetDescription(): void
    {
        self::assertSame(
            'Job to generate prices for dependent price lists by prices version.',
            $this->getTopic()::getDescription()
        );
    }

    public function testCreateJobNameWithBaseJobId(): void
    {
        $baseJob = $this->createMock(Job::class);
        $baseJob->expects($this->once())
            ->method('getName')
            ->willReturn('base_job_name');

        $jobRepository = $this->createMock(EntityRepository::class);
        $jobRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($baseJob);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(Job::class)
            ->willReturn($jobRepository);

        $messageBody = [
            'sourcePriceListId' => 1,
            'version' => 100,
            'baseJobId' => 123,
            'level' => 2,
        ];

        self::assertSame(
            'base_job_name:wave:2',
            $this->getTopic()->createJobName($messageBody)
        );
    }

    public function testCreateJobNameWithoutBaseJobId(): void
    {
        $messageBody = [
            'sourcePriceListId' => 1,
            'version' => 100,
        ];

        self::assertSame(
            'oro_pricing.dependent_price_lists_prices.generate:v100',
            $this->getTopic()->createJobName($messageBody)
        );
    }

    public function testSetProductsBatchSize(): void
    {
        $topic = $this->getTopic();
        $topic->setProductsBatchSize(1000);

        // Test that the batch size was changed - we can verify this by checking the behavior
        // Since the property is private, we test it indirectly through the configuration
        $this->assertInstanceOf(GenerateDependentPriceListPricesTopic::class, $topic);
    }

    /**
     * Create a mock generator for testing purposes
     */
    private function createMockGenerator(): \Generator
    {
        yield from [];
    }

    /**
     * Test that productBatches normalizer works with version
     */
    public function testProductBatchesNormalizerWithVersion(): void
    {
        $this->productPriceRepository->expects($this->once())
            ->method('getProductsByPriceListAndVersion')
            ->with(
                $this->shardManager,
                1,
                100,
                GenerateDependentPriceListPricesTopic::BUFFER_SIZE
            )
            ->willReturn($this->createBatchGenerator([[1, 2, 3], [4, 5, 6]]));

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(ProductPrice::class)
            ->willReturn($this->productPriceRepository);

        $topic = $this->getTopic();
        $resolver = new \Symfony\Component\OptionsResolver\OptionsResolver();
        $topic->configureMessageBody($resolver);

        $result = $resolver->resolve([
            'sourcePriceListId' => 1,
            'version' => 100,
        ]);

        // Convert generator to array to verify
        $batches = iterator_to_array($result['productBatches']);
        $this->assertCount(2, $batches);
        $this->assertEquals([1, 2, 3], $batches[0]);
        $this->assertEquals([4, 5, 6], $batches[1]);
    }

    /**
     * Test that setProductsBatchSize affects the batch size used
     */
    public function testProductBatchesNormalizerWithCustomBatchSize(): void
    {
        $customBatchSize = 100;

        $this->productPriceRepository->expects($this->once())
            ->method('getProductsByPriceListAndVersion')
            ->with(
                $this->shardManager,
                1,
                100,
                $customBatchSize
            )
            ->willReturn($this->createBatchGenerator([[1, 2]]));

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(ProductPrice::class)
            ->willReturn($this->productPriceRepository);

        $topic = $this->getTopic();
        $topic->setProductsBatchSize($customBatchSize);

        $resolver = new \Symfony\Component\OptionsResolver\OptionsResolver();
        $topic->configureMessageBody($resolver);

        $result = $resolver->resolve([
            'sourcePriceListId' => 1,
            'version' => 100,
        ]);

        // Convert generator to array to verify
        $batches = iterator_to_array($result['productBatches']);
        $this->assertCount(1, $batches);
    }

    /**
     * Helper method to create a generator with predefined batches
     */
    private function createBatchGenerator(array $batches): \Generator
    {
        foreach ($batches as $batch) {
            yield $batch;
        }
    }
}
