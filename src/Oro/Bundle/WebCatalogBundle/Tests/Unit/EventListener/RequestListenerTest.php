<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentVariantRepository;
use Oro\Bundle\WebCatalogBundle\EventListener\RequestListener;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var RequestListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->listener = new RequestListener($this->registry);
    }

    public function testOnKernelRequestSubRequest()
    {
        /** @var GetResponseEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(false);
        $this->registry->expects($this->never())
            ->method($this->anything());

        $this->listener->onKernelRequest($event);
    }

    public function testOnKernelRequestNotSlug()
    {
        /** @var GetResponseEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);
        $request = Request::create('/');
        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $this->registry->expects($this->never())
            ->method($this->anything());

        $this->listener->onKernelRequest($event);
        $this->assertFalse($request->attributes->has('_content_variant'));
    }

    public function testOnKernelRequest()
    {
        /** @var GetResponseEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);
        $request = Request::create('/');

        /** @var Slug|\PHPUnit_Framework_MockObject_MockObject $slug */
        $slug = $this->getMockBuilder(Slug::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request->attributes->set('_used_slug', $slug);

        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $repo = $this->getMockBuilder(ContentVariantRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $variant = $this->getMock(ContentVariantInterface::class);

        $repo->expects($this->once())
            ->method('findVariantBySlug')
            ->with($slug)
            ->willReturn($variant);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(ContentVariant::class)
            ->willReturn($repo);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ContentVariant::class)
            ->willReturn($em);

        $this->listener->onKernelRequest($event);
        $this->assertTrue($request->attributes->has('_content_variant'));
        $this->assertEquals($variant, $request->attributes->get('_content_variant'));
    }
}
