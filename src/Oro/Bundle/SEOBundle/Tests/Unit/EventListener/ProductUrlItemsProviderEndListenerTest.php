<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\SEOBundle\Limiter\WebCatalogProductLimiter;
use Oro\Bundle\SEOBundle\Event\UrlItemsProviderEvent;
use Oro\Bundle\SEOBundle\EventListener\ProductUrlItemsProviderEndListener;

class ProductUrlItemsProviderEndListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebCatalogProductLimiter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $webCatalogProductLimiter;

    /**
     * @var ProductUrlItemsProviderEndListener
     */
    protected $ProductUrlItemsProviderEndListener;

    protected function setUp()
    {
        $this->webCatalogProductLimiter = $this->getMockBuilder(WebCatalogProductLimiter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ProductUrlItemsProviderEndListener = new ProductUrlItemsProviderEndListener(
            $this->webCatalogProductLimiter
        );
    }

    public function testOnEnd()
    {
        $version = 42;
        $event = new UrlItemsProviderEvent($version);

        $this->webCatalogProductLimiter->expects($this->once())
            ->method('erase')
            ->with($version);

        $this->ProductUrlItemsProviderEndListener->onEnd($event);
    }
}
