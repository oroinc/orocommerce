<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\InventoryBundle\EventListener\CategoryUpcomingFormViewListener;
use Oro\Bundle\UIBundle\Tests\Unit\Fallback\AbstractFallbackFieldsFormViewTest;
use Twig\Environment;

class CategoryUpcomingFormViewListenerTest extends AbstractFallbackFieldsFormViewTest
{
    /** @var CategoryUpcomingFormViewListener */
    protected $fallbackFieldsFormView;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fallbackFieldsFormView = new CategoryUpcomingFormViewListener(
            $this->requestStack,
            $this->doctrine,
            $this->translator
        );
    }

    public function testOnCategoryEdit()
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
                ['dataBlocks' => [1 => ['title' => 'oro.catalog.sections.default_options.trans']]]
            );
        $this->scrollData->expects($this->once())
            ->method('addSubBlockData');
        $this->event->expects($this->once())
            ->method('getScrollData')
            ->willReturn($this->scrollData);

        $this->fallbackFieldsFormView->onCategoryEdit($this->event);
    }
}
