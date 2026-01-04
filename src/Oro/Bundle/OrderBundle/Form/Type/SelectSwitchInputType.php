<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SelectSwitchInputType extends AbstractType
{
    public const NAME = 'oro_select_switch_input';
    public const MODE_SELECT = 'select';
    public const MODE_INPUT = 'input';

    /**
     *
     * @throws AccessException
     */
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setRequired(['mode']);
        $resolver->setDefaults(
            [
                'placeholder' => false,
                'mode' => self::MODE_SELECT,
                'page_component' => 'oroorder/js/app/components/select-switch-input-component',
                'page_component_options' => [],
            ]
        );
        $resolver->setAllowedTypes('mode', 'string');
        $resolver->setAllowedTypes('page_component_options', 'array');
        $resolver->setAllowedTypes('page_component', 'string');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['page_component'] = $options['page_component'];

        $component_options = [
            'choices' => $options['choices'],
            'mode' => $options['mode'],
            'value' => $form->getData()

        ];
        $view->vars['page_component_options'] = array_merge($options['page_component_options'], $component_options);
    }
}
