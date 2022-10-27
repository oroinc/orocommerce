<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\EventListener\FormViewListener;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FormViewListenerTest extends \PHPUnit\Framework\TestCase
{
    private const EXPECTED_SCROLL_DATA = [
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

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $env;

    /** @var FormViewListener */
    private $listener;

    /** @var RequestStack */
    private $requestStack;

    /**
     * {@inheritdoc}
     */
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
        $this->requestStack = new RequestStack();

        $this->listener = new FormViewListener($this->translator, $this->doctrineHelper, $this->requestStack);
    }

    public function testOnCustomerUserView(): void
    {
        $this->assertRequestCalled(1);

        $customerUser = new CustomerUser();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroCustomerBundle:CustomerUser', 1)
            ->willReturn($customerUser);

        $this->env->expects($this->once())
            ->method('render')
            ->with('@OroOrder/CustomerUser/orders_view.html.twig', ['entity' => $customerUser])
            ->willReturn('rendered');

        $scrollData = new ScrollData();

        $this->listener->onCustomerUserView(new BeforeListRenderEvent($this->env, $scrollData, new \stdClass()));

        $this->assertEquals(self::EXPECTED_SCROLL_DATA, $scrollData->getData());
    }

    public function testOnCustomerUserViewWithoutId(): void
    {
        $this->assertRequestCalled(null);

        $this->doctrineHelper->expects($this->never())
            ->method($this->anything());

        $this->env->expects($this->never())
            ->method($this->anything());

        $event = new BeforeListRenderEvent($this->env, new ScrollData(), new \stdClass());

        $this->listener->onCustomerUserView($event);
    }

    public function testOnCustomerUserViewWithoutEntity(): void
    {
        $this->assertRequestCalled(1);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroCustomerBundle:CustomerUser', 1)
            ->willReturn(null);

        $this->env->expects($this->never())
            ->method($this->anything());

        $this->listener->onCustomerUserView(new BeforeListRenderEvent($this->env, new ScrollData(), new \stdClass()));
    }

    public function testOnCustomerUserViewWithEmptyRequest(): void
    {
        $this->doctrineHelper->expects($this->never())
            ->method($this->anything());

        $this->env->expects($this->never())
            ->method($this->anything());

        $this->listener->onCustomerUserView(new BeforeListRenderEvent($this->env, new ScrollData(), new \stdClass()));
    }

    public function testOnCustomerView(): void
    {
        $this->assertRequestCalled(1);

        $customer = new Customer();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroCustomerBundle:Customer', 1)
            ->willReturn($customer);

        $this->env->expects($this->once())
            ->method('render')
            ->with('@OroOrder/Customer/orders_view.html.twig', ['entity' => $customer])
            ->willReturn('rendered');

        $scrollData = new ScrollData();

        $this->listener->onCustomerView(new BeforeListRenderEvent($this->env, $scrollData, $customer));

        $this->assertEquals(self::EXPECTED_SCROLL_DATA, $scrollData->getData());
    }

    public function testOnCustomerViewWithoutId(): void
    {
        $this->assertRequestCalled(null);

        $this->doctrineHelper->expects($this->never())
            ->method($this->anything());

        $this->env->expects($this->never())
            ->method($this->anything());

        $this->listener->onCustomerView(new BeforeListRenderEvent($this->env, new ScrollData(), new \stdClass()));
    }

    public function testOnCustomerViewWithoutEntity(): void
    {
        $this->assertRequestCalled(1);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroCustomerBundle:Customer', 1)
            ->willReturn(null);

        $this->env->expects($this->never())
            ->method($this->anything());

        $this->listener->onCustomerView(new BeforeListRenderEvent($this->env, new ScrollData(), new \stdClass()));
    }

    public function testOnCustomerViewWithEmptyRequest(): void
    {
        $this->doctrineHelper->expects($this->never())
            ->method($this->anything());

        $this->env->expects($this->never())
            ->method($this->anything());

        $this->listener->onCustomerView(new BeforeListRenderEvent($this->env, new ScrollData(), new \stdClass()));
    }

    public function testOnShoppingListView(): void
    {
        $this->assertRequestCalled(1);

        $shoppingList = new ShoppingList();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroShoppingListBundle:ShoppingList', 1)
            ->willReturn($shoppingList);

        $this->env->expects($this->once())
            ->method('render')
            ->with('@OroOrder/ShoppingList/orders_view.html.twig', ['entity' => $shoppingList])
            ->willReturn('rendered');

        $scrollData = new ScrollData();

        $this->listener->onShoppingListView(new BeforeListRenderEvent($this->env, $scrollData, $shoppingList));

        $this->assertEquals(self::EXPECTED_SCROLL_DATA, $scrollData->getData());
    }

    public function testOnShoppingListViewWithoutId(): void
    {
        $this->assertRequestCalled(null);

        $this->doctrineHelper->expects($this->never())
            ->method($this->anything());

        $this->env->expects($this->never())
            ->method($this->anything());

        $this->listener->onShoppingListView(new BeforeListRenderEvent($this->env, new ScrollData(), new \stdClass()));
    }

    public function testOnShoppingListViewWithoutEntity(): void
    {
        $this->assertRequestCalled(1);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroShoppingListBundle:ShoppingList', 1)
            ->willReturn(null);

        $this->env->expects($this->never())
            ->method($this->anything());

        $this->listener->onShoppingListView(new BeforeListRenderEvent($this->env, new ScrollData(), new \stdClass()));
    }

    public function testOnShoppingListViewWithEmptyRequest(): void
    {
        $this->doctrineHelper->expects($this->never())
            ->method($this->anything());

        $this->env->expects($this->never())
            ->method($this->anything());

        $this->listener->onShoppingListView(new BeforeListRenderEvent($this->env, new ScrollData(), new \stdClass()));
    }

    private function assertRequestCalled(?int $id): void
    {
        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn($id);

        $this->requestStack->push($request);
    }
}
