<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Cache;

use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCacheWarmer;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class ContentNodeTreeCacheWarmerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messageProducer;

    /**
     * @var ContentNodeTreeCacheWarmer
     */
    private $warmer;

    protected function setUp()
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->warmer = new ContentNodeTreeCacheWarmer($this->messageProducer);
    }

    public function testIsOptional()
    {
        $this->assertTrue($this->warmer->isOptional());
    }

    public function testWarmUp()
    {
        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::CALCULATE_WEB_CATALOG_CACHE, '');

        $this->warmer->warmUp(__DIR__);
    }
}
