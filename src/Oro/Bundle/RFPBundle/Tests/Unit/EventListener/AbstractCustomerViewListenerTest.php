<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\RFPBundle\EventListener\AbstractCustomerViewListener;
use Oro\Bundle\RFPBundle\EventListener\CustomerViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractCustomerViewListenerTest extends \PHPUnit\Framework\TestCase
{
    const RENDER_HTML = 'render_html';
    const TRANSLATED_TEXT = 'translated_text';

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var \Twig_Environment|\PHPUnit\Framework\MockObject\MockObject */
    protected $env;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    protected $requestStack;

    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    protected $request;

    /** @var BeforeListRenderEvent|\PHPUnit\Framework\MockObject\MockObject */
    protected $event;

    /** * @var CustomerViewListener */
    protected $customerViewListener;

    protected function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($id) {
                    return $id . '.trans';
                }
            );

        $this->env = $this->createMock(\Twig_Environment::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->request = $this->createMock(Request::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->event = new BeforeListRenderEvent(
            $this->env,
            new ScrollData(),
            new \stdClass()
        );

        $this->customerViewListener = $this->createListenerToTest();
    }

    public function testOnCustomerViewGetsIgnoredIfNoRequest()
    {
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->customerViewListener->onCustomerView($this->event);
    }

    public function testOnCustomerViewGetsIgnoredIfNoRequestId()
    {
        $this->customerViewListener->onCustomerView($this->event);
    }

    public function testOnCustomerViewGetsIgnoredIfNoEntityFound()
    {
        $this->request->expects($this->once())
            ->method('get')
            ->willReturn(1);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturn(null);

        $this->customerViewListener->onCustomerView($this->event);
    }

    public function testOnCustomerViewCreatesScrollBlock()
    {
        $this->request->expects($this->once())
            ->method('get')
            ->willReturn(1);

        $customer = new Customer();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroCustomerBundle:Customer', 1)
            ->willReturn($customer);

        $this->env->expects($this->once())
            ->method('render')
            ->with($this->getCustomerViewTemplate(), ['entity' => $customer])
            ->willReturn(self::RENDER_HTML);

        $this->customerViewListener->onCustomerView($this->event);
    }

    public function testOnCustomerUserViewCreatesScrollBlock()
    {
        $this->request->expects($this->once())
            ->method('get')
            ->willReturn(1);

        $customerUser = new CustomerUser();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroCustomerBundle:CustomerUser', 1)
            ->willReturn($customerUser);

        $this->env->expects($this->once())
            ->method('render')
            ->with($this->getCustomerUserViewTemplate(), ['entity' => $customerUser])
            ->willReturn(self::RENDER_HTML);

        $this->customerViewListener->onCustomerUserView($this->event);
    }

    /**
     * @return string
     */
    abstract protected function getCustomerViewTemplate();

    /**
     * @return string
     */
    abstract protected function getCustomerLabel();

    /**
     * @return string
     */
    abstract protected function getCustomerUserViewTemplate();

    /**
     * @return string
     */
    abstract protected function getCustomerUserLabel();

    /**
     * @return AbstractCustomerViewListener
     */
    abstract protected function createListenerToTest();
}
