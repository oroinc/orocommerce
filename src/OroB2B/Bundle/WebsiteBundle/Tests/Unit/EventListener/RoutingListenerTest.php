<?php

namespace Oro\Bundle\WebsiteProBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebsiteProBundle\EventListener\RoutingListener;
use Oro\Bundle\WebsiteProBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RoutingListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var RoutingListener
     */
    private $listener;

    /**
     * @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $websiteManager;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestStack;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteManager = $this->getMockBuilder(WebsiteManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new RoutingListener(
            $this->configManager,
            $this->websiteManager,
            $this->requestStack
        );
    }

    public function testWebsiteWasAddedToRequest()
    {
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $request = Request::create('https://orocommerce.com/product');
        $this->requestStack->method('getMasterRequest')->willReturn($request);
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->websiteManager->method('getCurrentWebsite')->willReturn($website);
        $this->configManager
            ->expects($this->at(0))
            ->method('get')
            ->with('oro_b2b_website.url', false, false, 1)
            ->willReturn('http://orocommerce.com');
        $this->configManager
            ->expects($this->at(1))
            ->method('get')
            ->with('oro_b2b_website.secure_url', false, false, 1)
            ->willReturn('https://orocommerce.com');
        /** @var GetResponseEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(GetResponseEvent::class)->disableOriginalConstructor()->getMock();
        $this->listener->onRequest($event);
        $this->assertSame($website, $request->attributes->get(RoutingListener::CURRENT_WEBSITE));
    }

    public function testRedirectToProperUri()
    {
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $request = Request::create('https://us.orocommerce.com/product');
        $this->requestStack->method('getMasterRequest')->willReturn($request);
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->websiteManager->method('getCurrentWebsite')->willReturn($website);
        $this->configManager
            ->expects($this->at(0))
            ->method('get')
            ->with('oro_b2b_website.url', false, false, 1)
            ->willReturn('http://orocommerce.com');
        $this->configManager
            ->expects($this->at(1))
            ->method('get')
            ->with('oro_b2b_website.secure_url', false, false, 1)
            ->willReturn('https://orocommerce.com');
        /** @var GetResponseEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(GetResponseEvent::class)->disableOriginalConstructor()->getMock();
        $event->expects($this->once())
            ->method('setResponse');
//            ->with(new RedirectResponse($redirectUri)); todo
        $this->listener->onRequest($event);
    }
}
