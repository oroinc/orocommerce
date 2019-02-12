<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\EventListener\FormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

class FormViewListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \Twig_Environment|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $env;

    /** @var FormViewListener */
    protected $listener;

    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    protected $request;

    /** @var RequestStack */
    protected $requestStack;

    /**
     * {@inheritdoc}
     */
    public function setUp()
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

        $this->requestStack = new RequestStack();
        $this->requestStack->push($this->request);

        $this->listener = new FormViewListener($this->translator, $this->doctrineHelper, $this->requestStack);
    }

    public function testOnCustomerUserView()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $customerUser = new CustomerUser();

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->with('OroCustomerBundle:CustomerUser', 1)
            ->willReturn($customerUser);

        $this->env->expects($this->once())
            ->method('render')
            ->with('OroOrderBundle:CustomerUser:orders_view.html.twig', ['entity' => $customerUser])
            ->willReturn('rendered');

        $scrollData = new ScrollData();

        $event = new BeforeListRenderEvent(
            $this->env,
            $scrollData,
            new \stdClass()
        );

        $this->listener->onCustomerUserView($event);

        $expectedData = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => [
                                0 => 'rendered',
                            ],
                        ],
                    ],
                    ScrollData::TITLE => 'oro.order.sales_orders.label.trans',
                    ScrollData::USE_SUB_BLOCK_DIVIDER => true,
                ],
            ],
        ];

        $this->assertEquals($expectedData, $scrollData->getData());
    }

    public function testOnCustomerUserViewWithEmptyRequest()
    {
        $event = new BeforeListRenderEvent($this->env, new ScrollData(), new \stdClass());

        // remove request added in setUp method
        $this->requestStack->pop();

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityReference');

        $this->listener->onCustomerUserView($event);
    }

    public function testOnCustomerView()
    {
        $this->request->expects($this->any())->method('get')->with('id')->willReturn(1);

        $customer = new Customer();

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->with('OroCustomerBundle:Customer', 1)
            ->willReturn($customer);

        $this->env->expects($this->once())
            ->method('render')
            ->with('OroOrderBundle:Customer:orders_view.html.twig', ['entity' => $customer])
            ->willReturn('rendered');

        $scrollData = new ScrollData();

        $event = new BeforeListRenderEvent(
            $this->env,
            $scrollData,
            $customer
        );

        $this->listener->onCustomerView($event);

        $expectedData = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => [
                                0 => 'rendered',
                            ],
                        ],
                    ],
                    ScrollData::TITLE => 'oro.order.sales_orders.label.trans',
                    ScrollData::USE_SUB_BLOCK_DIVIDER => true,
                ],
            ],
        ];

        $this->assertEquals($expectedData, $scrollData->getData());
    }

    public function testOnCustomerViewWithoutId()
    {
        $this->request->expects($this->any())->method('get')->with('id')->willReturn(null);

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityReference');

        $event = new BeforeListRenderEvent($this->env, new ScrollData(), new \stdClass());

        $this->listener->onCustomerView($event);
    }

    public function testOnCustomerViewWithoutEntity()
    {
        $this->request->expects($this->any())->method('get')->with('id')->willReturn(1);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->with('OroCustomerBundle:Customer', 1)
            ->willReturn(null);

        $event = new BeforeListRenderEvent($this->env, new ScrollData(), new \stdClass());

        $this->env->expects($this->never())->method('render');

        $this->listener->onCustomerView($event);
    }

    public function testOnCustomerViewWithEmptyRequest()
    {
        $event = new BeforeListRenderEvent($this->env, new ScrollData(), new \stdClass());

        // remove request added in setUp method
        $this->requestStack->pop();

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityReference');

        $this->listener->onCustomerView($event);
    }
}
