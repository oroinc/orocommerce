<?php

namespace Oro\Bundle\CommerceBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CommerceBundle\ContentWidget\Provider\ScorecardInterface;
use Oro\Bundle\CommerceBundle\ContentWidget\Provider\ScorecardsRegistryInterface;
use Oro\Bundle\CommerceBundle\Form\Type\ScorecardSelectType;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ScorecardSelectTypeTest extends TestCase
{
    private ScorecardInterface&MockObject $scorecard;
    private ScorecardInterface&MockObject $scorecard2;
    private ScorecardsRegistryInterface&MockObject $scorecardRegistry;

    private ScorecardSelectType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->scorecard = $this->createMock(ScorecardInterface::class);
        $this->scorecard2 = $this->createMock(ScorecardInterface::class);
        $this->scorecardRegistry = $this->createMock(ScorecardsRegistryInterface::class);

        $this->scorecard->expects(self::any())
            ->method('getName')
            ->willReturn('scorecard');

        $this->scorecard->expects(self::any())
            ->method('getLabel')
            ->willReturn('Scorecard');

        $this->scorecard2->expects(self::any())
            ->method('getName')
            ->willReturn('scorecard2');

        $this->scorecard2->expects(self::any())
            ->method('getLabel')
            ->willReturn('Scorecard2');

        $this->type = new ScorecardSelectType($this->scorecardRegistry);
    }

    public function testGetParent(): void
    {
        self::assertSame(Select2ChoiceType::class, $this->type->getParent());
    }

    public function testGetBlockPrefix(): void
    {
        self::assertSame('oro_commerce_scorecard_content_widget_type_select', $this->type->getBlockPrefix());
    }

    public function testConfigureOptions(): void
    {
        $this->scorecardRegistry->expects(self::any())
            ->method('getProviders')
            ->willReturn([$this->scorecard, $this->scorecard2]);

        $resolver = new OptionsResolver();
        $this->type->configureOptions($resolver);

        self::assertSame(
            [
                'choices' => ['Scorecard' => 'scorecard', 'Scorecard2' => 'scorecard2'],
                'placeholder' => 'oro.commerce.content_widget_type.scorecard.form.choose_scorecard',
            ],
            $resolver->resolve()
        );
    }

    public function testBuildView(): void
    {
        $view = new FormView();
        $form = $this->createMock(Form::class);

        $this->type->buildView($view, $form, ['configs' => [], 'choices' => []]);

        self::assertSame(
            [
                'value' => null,
                'attr' => [],
                'configs' => [
                    'placeholder' => 'oro.commerce.content_widget_type.scorecard.form.no_available_scorecards'
                ],
            ],
            $view->vars
        );
    }

    public function testBuildViewWithEmptyChoices(): void
    {
        $view = new FormView();
        $form = $this->createMock(Form::class);

        $this->type->buildView($view, $form, ['configs' => [], 'choices' => ['label' => 'id']]);

        self::assertSame(
            [
                'value' => null,
                'attr' => [],
                'configs' => [],
            ],
            $view->vars
        );
    }
}
