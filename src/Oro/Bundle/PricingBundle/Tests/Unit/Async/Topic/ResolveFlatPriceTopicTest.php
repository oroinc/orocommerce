<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\PricingBundle\Async\Topic\ResolveFlatPriceTopic;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class ResolveFlatPriceTopicTest extends AbstractTopicTestCase
{
    use EntityTrait;

    /** @var ManagerRegistry */
    private $doctrine;

    protected function getTopic(): TopicInterface
    {
        $priceListToProductRepository = $this->createMock(PriceListToProductRepository::class);
        $priceListToProductRepository
            ->expects($this->any())
            ->method('getProductIdsByPriceList')
            ->willReturn([1,2,3,4]);

        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->doctrine
            ->expects($this->any())
            ->method('getRepository')
            ->willReturn($priceListToProductRepository);

        return new ResolveFlatPriceTopic($this->doctrine);
    }

    public function validBodyDataProvider(): array
    {
        return [
            [
                'rawBody' => [
                    'priceList' => 1,
                    'products' => [1,2,3]
                ],
                'expectedMessage' => [
                    'priceList' => 1,
                    'products' => [1,2,3]
                ],
            ],
            [
                'rawBody' => [
                    'priceList' => 1,
                    'products' => []
                ],
                'expectedMessage' => [
                    'priceList' => 1,
                    'products' => [1,2,3,4]
                ],
            ],
            [
                'rawBody' => [
                    'priceList' => 1,
                ],
                'expectedMessage' => [
                    'priceList' => 1,
                    'products' => [1,2,3,4]
                ],
            ]
        ];
    }

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
