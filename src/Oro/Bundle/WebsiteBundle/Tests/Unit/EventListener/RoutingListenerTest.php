<?php

namespace Oro\Bundle\WebsiteBundle\Tests\Unit\EventListener;

use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\EventListener\RoutingListener;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RoutingListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RoutingListener
     */
    private $listener;

    /**
     * @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $websiteManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->websiteManager = $this->getMockBuilder(WebsiteManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new RoutingListener(
            $this->websiteManager
        );
    }

    public function testWebsiteWasAddedToRequest()
    {
        $website = new Website();
        $this->websiteManager->method('getCurrentWebsite')->willReturn($website);
        $request = Request::create('https://orocommerce.com/product');
        /** @var GetResponseEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(GetResponseEvent::class)->disableOriginalConstructor()->getMock();
        $event->method('getRequest')->willReturn($request);
        $this->listener->onRequest($event);
        $this->assertSame($website, $request->attributes->get('current_website'));
    }
}
