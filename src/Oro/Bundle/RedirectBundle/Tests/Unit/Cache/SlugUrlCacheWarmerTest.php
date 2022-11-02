<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Cache;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RedirectBundle\Async\Topic\CalculateSlugCacheMassTopic;
use Oro\Bundle\RedirectBundle\Cache\SlugUrlCacheWarmer;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class SlugUrlCacheWarmerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MessageProducerInterface|MockObject
     */
    private $messageProducer;

    /**
     * @var RoutingInformationProvider|MockObject
     */
    private $routingInformationProvider;

    /**
     * @var MessageFactoryInterface|MockObject
     */
    private $messageFactory;

    /**
     * @var SlugUrlCacheWarmer
     */
    private $warmer;

    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->routingInformationProvider = $this->createMock(RoutingInformationProvider::class);
        $this->messageFactory = $this->createMock(MessageFactoryInterface::class);

        $this->warmer = new SlugUrlCacheWarmer(
            $this->messageProducer,
            $this->routingInformationProvider,
            $this->messageFactory
        );
    }

    public function testIsOptional(): void
    {
        self::assertTrue($this->warmer->isOptional());
    }

    public function testWarmUp(): void
    {
        $this->routingInformationProvider->expects(self::once())
            ->method('getEntityClasses')
            ->willReturn([Product::class, Category::class]);

        $message1 = ['class' => Product::class, 'id' => [], 'createRedirect' => false];
        $message2 = ['class' => Category::class, 'id' => [], 'createRedirect' => false];
        $this->messageFactory->expects(self::exactly(2))
            ->method('createMassMessage')
            ->withConsecutive(
                [Product::class, [], false],
                [Category::class, [], false]
            )
            ->willReturn(
                $message1,
                $message2
            );
        $this->messageProducer->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [CalculateSlugCacheMassTopic::getName(), $message1],
                [CalculateSlugCacheMassTopic::getName(), $message2]
            );

        $this->warmer->warmUp(__DIR__);
    }
}
