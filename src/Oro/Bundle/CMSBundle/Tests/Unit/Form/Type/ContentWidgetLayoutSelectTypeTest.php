<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\ContentWidgetLayoutSelectType;
use Oro\Bundle\CMSBundle\Provider\ContentWidgetLayoutProvider;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ContentWidgetTypeStub;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentWidgetLayoutSelectTypeTest extends FormIntegrationTestCase
{
    private ContentWidgetLayoutProvider|\PHPUnit\Framework\MockObject\MockObject $widgetLayoutProvider;

    private ContentWidgetLayoutSelectType $formType;

    protected function setUp(): void
    {
        $this->widgetLayoutProvider = $this->createMock(ContentWidgetLayoutProvider::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->formType = new ContentWidgetLayoutSelectType($this->widgetLayoutProvider, $translator);

        parent::setUp();
    }

    public function testGetParent(): void
    {
        self::assertEquals(Select2ChoiceType::class, $this->formType->getParent());
    }

    public function testSubmit(): void
    {
        $this->widgetLayoutProvider->expects(self::once())
            ->method('getWidgetLayouts')
            ->with(ContentWidgetTypeStub::getName())
            ->willReturn(
                [
                    'first' => 'oro.widget.layout.first.label',
                    'second' => 'oro.widget.layout.second.label',
                    'third' => 'oro.widget.layout.third.label',
                ]
            );

        $form = $this->factory->create(
            ContentWidgetLayoutSelectType::class,
            null,
            ['widget_type' => ContentWidgetTypeStub::getName()]
        );

        self::assertEquals('first', $form->getData());

        $submittedData = 'second';

        $form->submit($submittedData);
        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($submittedData, $form->getData());
    }

    public function testFinishViewWithChoices(): void
    {
        $formView = new FormView();
        $formView->vars['choices'] = [new ChoiceView('data', 'value', 'label')];
        $formView->vars['required'] = false;

        $this->formType->finishView($formView, $this->createMock(FormInterface::class), []);

        self::assertArrayHasKey('attr', $formView->vars);
        self::assertArrayNotHasKey('class', $formView->vars['attr']);
        self::assertTrue($formView->vars['required']);
    }

    public function testFinishViewWithoutChoices(): void
    {
        $formView = new FormView();
        $formView->vars['choices'] = [];
        $formView->vars['required'] = false;

        $this->formType->finishView($formView, $this->createMock(FormInterface::class), []);

        self::assertArrayHasKey('attr', $formView->vars);
        self::assertArrayHasKey('class', $formView->vars['attr']);
        self::assertEquals('hide ', $formView->vars['attr']['class']);
        self::assertFalse($formView->vars['required']);
    }

    public function testFinishViewWithClass(): void
    {
        $formView = new FormView();
        $formView->vars['choices'] = [];
        $formView->vars['attr']['class'] = 'test';

        $this->formType->finishView($formView, $this->createMock(FormInterface::class), []);

        self::assertArrayHasKey('attr', $formView->vars);
        self::assertArrayHasKey('class', $formView->vars['attr']);
        self::assertEquals('hide test', $formView->vars['attr']['class']);
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    ContentWidgetLayoutSelectType::class => $this->formType,
                ],
                []
            )
        ];
    }
}
