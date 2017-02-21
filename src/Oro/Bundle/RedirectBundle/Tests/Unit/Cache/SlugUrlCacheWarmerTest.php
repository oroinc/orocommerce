<?php

namespace Oro\Bundle\RedirectBundle\Tests\Cache;

use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Cache\SlugUrlCacheWarmer;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class SlugUrlCacheWarmerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messageProducer;

    /**
     * @var SlugUrlCacheWarmer
     */
    private $warmer;

    protected function setUp()
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
