<?php

namespace Oro\Bundle\CommerceBundle\Form\Type;

use Oro\Bundle\CommerceBundle\ContentWidget\Provider\ScorecardsRegistryInterface;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Covers logic of selecting scorecards for content widget.
 */
class ScorecardSelectType extends AbstractType
{
    public function __construct(
        private readonly ScorecardsRegistryInterface $scorecardsRegistry
    ) {
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (empty($options['choices'])) {
            $options['configs']['placeholder'] =
                'oro.commerce.content_widget_type.scorecard.form.no_available_scorecards';
        }

        $view->vars = \array_replace_recursive($view->vars, ['configs' => $options['configs']]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'choices' => $this->getScorecardChoices(),
                'placeholder' => 'oro.commerce.content_widget_type.scorecard.form.choose_scorecard',
            ]
        );
    }

    #[\Override]
    public function getParent(): string
    {
        return Select2ChoiceType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_commerce_scorecard_content_widget_type_select';
    }

    private function getScorecardChoices(): array
    {
        $choices = [];
        foreach ($this->scorecardsRegistry->getProviders() as $provider) {
            $choices[$provider->getLabel()] = $provider->getName();
        }

        return $choices;
    }
}
