<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Cache;

use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Cache\SlugUrlCacheWarmer;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class SlugUrlCacheWarmerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageProducer;

    /**
     * @var SlugUrlCacheWarmer
     */
    private $warmer;

    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->warmer = new SlugUrlCacheWarmer($this->messageProducer);
    }

    public function testIsOptional()
    {
        $this->assertTrue($this->warmer->isOptional());
    }

    public function testWarmUp()
    {
        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::CALCULATE_URL_CACHE_MASS, '');

        $this->warmer->warmUp(__DIR__);
    }
}
