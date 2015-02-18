<?php

namespace OroB2B\Bundle\AttributeBundle\Form\Type;

use OroB2B\Bundle\AttributeBundle\Form\DataTransformer\OptionRowTransformer;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Integer;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class OptionRowType extends AbstractType
{
    const NAME = 'orob2b_option_row';
    const MASTER_OPTION_ID = 'master_option_id';
    const DEFAULT_VALUE = 'default';
    const IS_DEFAULT = 'is_default';
    const ORDER = 'order';
    const LOCALES = 'locales';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(self::MASTER_OPTION_ID, 'hidden') // used to pass master option id
            ->add(
                self::DEFAULT_VALUE,
                'text',
                ['label' => 'orob2b.attribute.default', 'constraints' => [new NotBlank()]]
            )
            ->add(self::IS_DEFAULT, $options['is_default_type'], ['required' => false])
            ->add(
                self::ORDER,
                'integer',
                [
                    'label' => 'orob2b.attribute.order',
                    'type' => 'text',
                    'constraints' => [new NotBlank(), new Integer()]
                ]
            )
            ->add(
                self::LOCALES,
                LocaleCollectionType::NAME,
                [
                    'type' => 'text',
                    'value_type' => $options['value_type'],
                    'options' => ['constraints' => [new NotBlank()]]
                ]
            )
        ;

        $localized = $options['value_type'] != 'orob2b_attribute_fallback_value';
        $builder->addViewTransformer(new OptionRowTransformer($localized));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'value_type' => FallbackValueType::NAME,
            'is_default_type' => 'checkbox'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['is_default_type'] = $options['is_default_type'];
    }
}
