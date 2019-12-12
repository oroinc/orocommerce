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
    /** @var ContentWidgetLayoutProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $widgetLayoutProvider;

    /** @var ContentWidgetLayoutSelectType */
    private $formType;

    protected function setUp(): void
    {
        $this->widgetLayoutProvider = $this->createMock(ContentWidgetLayoutProvider::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->formType = new ContentWidgetLayoutSelectType($this->widgetLayoutProvider, $translator);

        parent::setUp();
    }

    public function testGetParent(): void
    {
        $this->assertEquals(Select2ChoiceType::class, $this->formType->getParent());
    }

    public function testSubmit(): void
    {
        $this->widgetLayoutProvider->expects($this->once())
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

        $submittedData = 'second';

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($submittedData, $form->getData());
    }

    public function testFinishViewWithChoices(): void
    {
        $formView = new FormView();
        $formView->vars['choices'] = [new ChoiceView('data', 'value', 'label')];

        $this->formType->finishView($formView, $this->createMock(FormInterface::class), []);

        $this->assertArrayHasKey('attr', $formView->vars);
        $this->assertArrayNotHasKey('class', $formView->vars['attr']);
    }

    public function testFinishViewWithoutChoices(): void
    {
        $formView = new FormView();
        $formView->vars['choices'] = [];

        $this->formType->finishView($formView, $this->createMock(FormInterface::class), []);

        $this->assertArrayHasKey('attr', $formView->vars);
        $this->assertArrayHasKey('class', $formView->vars['attr']);
        $this->assertEquals('hide ', $formView->vars['attr']['class']);
    }

    public function testFinishViewWithClass(): void
    {
        $formView = new FormView();
        $formView->vars['choices'] = [];
        $formView->vars['attr']['class'] = 'test';

        $this->formType->finishView($formView, $this->createMock(FormInterface::class), []);

        $this->assertArrayHasKey('attr', $formView->vars);
        $this->assertArrayHasKey('class', $formView->vars['attr']);
        $this->assertEquals('hide test', $formView->vars['attr']['class']);
    }

    /**
     * {@inheritdoc}
     */
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
