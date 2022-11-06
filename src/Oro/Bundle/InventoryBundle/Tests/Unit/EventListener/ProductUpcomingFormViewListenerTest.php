<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\InventoryBundle\EventListener\ProductUpcomingFormViewListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Tests\Unit\Fallback\AbstractFallbackFieldsFormViewTest;
use Oro\Bundle\UIBundle\View\ScrollData;
use Twig\Environment;

class ProductUpcomingFormViewListenerTest extends AbstractFallbackFieldsFormViewTest
{
    /** @var ProductUpcomingFormViewListener */
    protected $fallbackFieldsFormView;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fallbackFieldsFormView = new ProductUpcomingFormViewListener(
            $this->requestStack,
            $this->doctrine,
            $this->translator
        );
    }

    public function testOnProductView()
    {
        $env = $this->createMock(Environment::class);
        $env->expects($this->once())
            ->method('render')
            ->willReturn('Rendered template');
        $this->event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $this->scrollData->expects($this->once())
            ->method('addSubBlockData')
            ->with('test block id', 0, 'Rendered template');

        $this->event->expects($this->once())
            ->method('getScrollData')
            ->willReturn($this->scrollData);
        $this->event->expects($this->once())
            ->method('getEntity')
            ->willReturn(new Product());

        $this->scrollData->expects($this->once())
            ->method('getData')
            ->willReturn([
                ScrollData::DATA_BLOCKS => [
                    'test block id' => [ScrollData::TITLE => 'oro.product.sections.inventory.trans']
                ]
            ]);

        $this->fallbackFieldsFormView->onProductView($this->event);
    }

    public function testOnProductEdit()
    {
        $env = $this->createMock(Environment::class);
        $env->expects($this->once())
            ->method('render')
            ->willReturn('Rendered template');

        $this->event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);
        $this->scrollData->expects($this->once())
            ->method('getData')
            ->willReturn(
                ['dataBlocks' => [1 => ['title' => 'oro.product.sections.inventory.trans']]]
            );
        $this->scrollData->expects($this->once())
            ->method('addSubBlockData');
        $this->event->expects($this->once())
            ->method('getScrollData')
            ->willReturn($this->scrollData);

        $this->fallbackFieldsFormView->onProductEdit($this->event);
    }
}
