<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\SEOBundle\Limiter\WebCatalogProductLimiter;
use Oro\Bundle\SEOBundle\Event\UrlItemsProviderEvent;
use Oro\Bundle\SEOBundle\EventListener\ProductUrlItemsProviderStartListener;
use Oro\Component\Website\WebsiteInterface;

class ProductUrlItemsProviderStartListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebCatalogProductLimiter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $webCatalogProductLimiter;

    /**
     * @var ProductUrlItemsProviderStartListener
     */
    protected $ProductUrlItemsProviderStartListener;

    protected function setUp()
    {
        $this->webCatalogProductLimiter = $this->getMockBuilder(WebCatalogProductLimiter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ProductUrlItemsProviderStartListener = new ProductUrlItemsProviderStartListener(
            $this->webCatalogProductLimiter
        );
    }

    public function testOnStart()
    {
        $website = $this->createMock(WebsiteInterface::class);
        $event = new UrlItemsProviderEvent(1, $website);

        $this->webCatalogProductLimiter->expects($this->once())
            ->method('prepareLimitation')
            ->with(1, $website);

        $this->ProductUrlItemsProviderStartListener->onStart($event);
    }
}
