<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async\Topic;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveFlatPriceTopic;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;

class ResolveFlatPriceTopicTest extends AbstractTopicTestCase
{
    #[\Override]
    protected function getTopic(): TopicInterface
    {
        $priceListToProductRepository = $this->createMock(PriceListToProductRepository::class);
        $priceListToProductRepository->expects(self::any())
            ->method('getProductIdsByPriceList')
            ->willReturn([1, 2, 3, 4]);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->willReturn($priceListToProductRepository);

        return new ResolveFlatPriceTopic($doctrine);
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        return [
            [
                'rawBody' => [
                    'priceList' => 1,
                    'products' => [1, 2, 3]
                ],
                'expectedMessage' => [
                    'priceList' => 1,
                    'products' => [1, 2, 3]
                ],
            ],
            [
                'rawBody' => [
                    'priceList' => 1,
                    'products' => []
                ],
                'expectedMessage' => [
                    'priceList' => 1,
                    'products' => [1, 2, 3, 4]
                ],
            ],
            [
                'rawBody' => [
                    'priceList' => 1,
                ],
                'expectedMessage' => [
                    'priceList' => 1,
                    'products' => [1, 2, 3, 4]
                ],
            ]
        ];
    }

    #[\Override]
    public function invalidBodyDataProvider(): array
    {
        return [];
    }

    public function testCreateJobName(): void
    {
        self::assertStringStartsWith(
            'oro_pricing.flat_price.resolve_',
            $this->getTopic()->createJobName([])
        );
    }
}
