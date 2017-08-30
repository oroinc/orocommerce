<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormView;
use Symfony\Component\Translation\TranslatorInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\EventListener\OrderViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

class OrderViewListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var OrderViewListener */
    protected $listener;

    public function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->listener = new OrderViewListener($this->translator);
    }

    public function testOnView()
    {
        $this->translator->expects($this->once())->method('trans');
        $template = 'test html template';
        $environment = $this->prepareEnvironment($template);
        $scrollData = $this->prepareScrollData($template);
        $this->listener->onView(new BeforeListRenderEvent($environment, $scrollData, new Order()));
    }

    public function testOnEditWhenNoDiscountBlockExist()
    {
        $formView = $this->createMock(FormView::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Scroll data must contain block with id "discounts"');

        /** @var \Twig_Environment|\PHPUnit_Framework_MockObject_MockObject $environment */
        $environment = $this->createMock(\Twig_Environment::class);
        $environment
            ->expects($this->never())
            ->method('render');

        $this->listener->onEdit(new BeforeListRenderEvent($environment, new ScrollData(), new Order(), $formView));
    }

    public function testOnEdit()
    {
        $translatedTitle = 'Discount and Promotions';
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('oro.promotion.sections.promotion_and_discounts.label')
            ->willReturn($translatedTitle);

        $template = 'test html template';
        $scrollData = new ScrollData();
        $scrollData->addNamedBlock('discounts', 'Discounts');

        $formView = $this->createMock(FormView::class);

        /** @var \Twig_Environment|\PHPUnit_Framework_MockObject_MockObject $environment */
        $environment = $this->createMock(\Twig_Environment::class);
        $environment->expects($this->once())
            ->method('render')
            ->with('OroPromotionBundle:Order:applied_promotions_and_coupons.html.twig', ['form' => $formView])
            ->willReturn($template);

        $event = new BeforeListRenderEvent($environment, $scrollData, new Order(), $formView);
        $this->listener->onEdit($event);

        $expectedScrollData = new ScrollData();
        $expectedScrollData->addNamedBlock('discounts', $translatedTitle);
        $subBlockId = $expectedScrollData->addSubBlock('discounts');
        $expectedScrollData->addSubBlockData('discounts', $subBlockId, $template);

        $this->assertEquals($expectedScrollData, $event->getScrollData());
    }

    /**
     * @param string $template
     * @return \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment
     */
    protected function prepareEnvironment($template)
    {
        $environment = $this->createMock(\Twig_Environment::class);
        $environment->expects($this->once())->method('render')->willReturn($template);
        return $environment;
    }

    /**
     * @param string $template
     * @return \PHPUnit_Framework_MockObject_MockObject|ScrollData
     */
    protected function prepareScrollData($template)
    {
        $blockId = 123;
        $subblockId = 456;
        $scrollData = $this->createMock(ScrollData::class);
        $scrollData->expects($this->once())->method('addBlock')->willReturn($blockId);
        $scrollData->expects($this->once())->method('addSubBlock')->willReturn($subblockId);
        $scrollData->expects($this->once())->method('addSubBlockData')->with($blockId, $subblockId, $template);
        return $scrollData;
    }
}
