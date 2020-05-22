<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\ContentWidgetTypeSelectType;
use Oro\Bundle\CMSBundle\Provider\ContentWidgetTypeProvider;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentWidgetTypeSelectTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContentWidgetTypeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $contentWidgetTypeProvider;

    /** @var ContentWidgetTypeSelectType */
    private $type;

    protected function setUp(): void
    {
        $this->contentWidgetTypeProvider = $this->createMock(ContentWidgetTypeProvider::class);

        $this->type = new ContentWidgetTypeSelectType($this->contentWidgetTypeProvider);
    }

    public function testConfigureOptions(): void
    {
        $this->contentWidgetTypeProvider->expects($this->once())
            ->method('getAvailableContentWidgetTypes')
            ->willReturn(
                [
                    'oro.type1.label' => 'testType1',
                    'oro.type2.label' => 'testType2',
                ]
            );

        $resolver = new OptionsResolver();

        $this->type->configureOptions($resolver);

        $this->assertEquals(
            [
                'choices' => [
                    'oro.type1.label' => 'testType1',
                    'oro.type2.label' => 'testType2'
                ],
                'placeholder' => 'oro.cms.contentwidget.form.choose_content_widget_type',
            ],
            $resolver->resolve([])
        );
    }

    public function testBuildView(): void
    {
        $view = new FormView();
        $form = $this->createMock(Form::class);

        $this->type->buildView($view, $form, ['configs' => [], 'choices' => []]);

        $this->assertEquals(
            [
                'configs' => ['placeholder' => 'oro.cms.contentwidget.form.no_available_content_widget_types'],
                'value' => null,
                'attr' => [],
            ],
            $view->vars
        );
    }

    public function testBuildViewWithEmptyChoices(): void
    {
        $view = new FormView();
        $form = $this->createMock(Form::class);

        $this->type->buildView($view, $form, ['configs' => [], 'choices' => ['label' => 'id']]);

        $this->assertEquals(
            [
                'configs' => [],
                'value' => null,
                'attr' => [],
            ],
            $view->vars
        );
    }

    public function testGetParent(): void
    {
        $this->assertEquals(Select2ChoiceType::class, $this->type->getParent());
    }

    public function testGetBlockPrefix(): void
    {
        $this->assertEquals('oro_cms_content_widget_type_select', $this->type->getBlockPrefix());
    }
}
