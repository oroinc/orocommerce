<?php

namespace OroB2B\Bundle\AttributeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

use OroB2B\Bundle\AttributeBundle\Form\DataTransformer\OptionRowTransformer;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Integer;
use OroB2B\Bundle\FallbackBundle\Form\Type\FallbackValueType;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocaleCollectionType;

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
                [
                    'label' => 'orob2b.fallback.value.default',
                    'constraints' => [new NotBlank()],
                    'validation_groups' => ['Default'],
                ]
            )
            ->add(self::IS_DEFAULT, $options['is_default_type'], ['required' => false])
            ->add(
                self::ORDER,
                'integer',
                [
                    'label' => 'orob2b.attribute.order',
                    'type' => 'text',
                    'constraints' => [new NotBlank(), new Integer()],
                    'validation_groups' => ['Default'],
                ]
            )
            ->add(
                self::LOCALES,
                LocaleCollectionType::NAME,
                [
                    'type' => 'text',
                    'value_type' => $options['value_type'],
                    'options' => ['constraints' => [new NotBlank()], 'validation_groups' => ['Default']]
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $data = $event->getData();
            if (!$data) {
                $data = [];
            }
            if (!isset($data[self::ORDER])) {
                $data[self::ORDER] = 0;
            }
            $event->setData($data);
        });

        $localized = $options['value_type'] != FallbackValueType::NAME;
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
