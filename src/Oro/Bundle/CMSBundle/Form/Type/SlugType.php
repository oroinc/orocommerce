<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ValidationBundle\Validator\Constraints\UrlSafe;

class SlugType extends AbstractType
{
    const NAME = 'oro_slug';
    const MODE_NEW = 'new';
    const MODE_OLD = 'old';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'slug',
                TextType::class,
                [
                    'label' => 'oro.redirect.slug.entity_label',
                    'constraints' => [new UrlSafe()]
                ]
            );

        if ($options['type'] == 'update') {
            $builder
                ->add('mode', ChoiceType::class, [
                    'choices' => [
                        self::MODE_OLD => 'oro.cms.slug.leave_as_is',
                        self::MODE_NEW => 'oro.cms.slug.update_to'
                    ],
                    'data' => self::MODE_OLD,
                    'required' => true,
                    'expanded' => true,
                    'multiple' => false
                ])
                ->add('redirect', CheckboxType::class, [
                    'required' => false, 'label' => 'oro.cms.slug.redirect'
                ]);
        } else {
            $builder
                ->add('mode', HiddenType::class, [
                    'data' => self::MODE_NEW
                ]);
        }

        // Disable slug validation if previous slug using
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (is_array($data) && $data['mode'] == 'old') {
                $event->getForm()
                    ->remove('slug')
                    ->add(
                        'slug',
                        'text',
                        [
                            'label' => 'oro.redirect.slug.entity_label',
                            'constraints' => [new UrlSafe()]
                        ]
                    );
            }
        });
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'type',
        ]);

        $resolver->setDefaults([
            'data_class' => null,
            'options' => [],
            'type' => 'create',
            'current_slug' => '',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['type'] = $options['type'];
        $view->vars['current_slug'] = $options['current_slug'];
    }
}
