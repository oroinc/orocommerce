<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\EventListener\AbstractFormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

abstract class AbstractFormViewListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    protected $env;

    /** @var AbstractFormViewListener */
    protected $listener;

    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    protected $request;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    protected $requestStack;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($id) {
                    return $id . '.trans';
                }
            );

        $this->env = $this->createMock(Environment::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->request = $this->createMock(Request::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);
    }

    protected function tearDown(): void
    {
        unset($this->request, $this->requestStack);

        parent::tearDown();
    }

    /**
     * @return AbstractFormViewListener
     */
    abstract public function getListener();

    public function testOnViewInvalidId()
    {
        $event = new BeforeListRenderEvent($this->env, new ScrollData(), new \stdClass());

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityReference');

        $this->getListener()->onView($event);

        $this->request
            ->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn('string');

        $this->getListener()->onView($event);
    }

    public function testOnViewEmpty()
    {
        $event = new BeforeListRenderEvent($this->env, new ScrollData(), new \stdClass());

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn(null);

        $this->request
            ->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityRepository')
            ->willReturn(null);

        $this->getListener()->onView($event);
    }

    public function testEmptyRequest()
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->requestStack->expects($this->any())->method('getCurrentRequest')->willReturn(null);

        $event = new BeforeListRenderEvent($this->env, new ScrollData(), new \stdClass());

        $this->doctrineHelper->expects($this->never())->method($this->anything());
        $this->request->expects($this->never())->method($this->anything());

        $this->getListener()->onView($event);
    }
}
