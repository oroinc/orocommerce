<?php

namespace OroB2B\Bundle\CMSBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\ValidationBundle\Validator\Constraints\UrlSafe;

class SlugType extends AbstractType
{
    const NAME = 'orob2b_slug';
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
                'text',
                [
                    'label' => 'orob2b.redirect.slug.entity_label',
                    'constraints' => [new UrlSafe()]
                ]
            );

        if ($options['type'] == 'update') {
            $builder
                ->add('mode', 'choice', [
                    'choices' => [
                        self::MODE_OLD => 'orob2b.cms.slug.leave_as_is',
                        self::MODE_NEW => 'orob2b.cms.slug.update_to'
                    ],
                    'data' => self::MODE_OLD,
                    'required' => true,
                    'expanded' => true,
                    'multiple' => false
                ])
                ->add('redirect', 'checkbox', [
                    'required' => false, 'label' => 'orob2b.cms.slug.redirect'
                ]);
        } else {
            $builder
                ->add('mode', 'hidden', [
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
                            'label' => 'orob2b.redirect.slug.entity_label',
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
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired([
            'type',
        ]);

        $resolver->setDefaults([
            'data_class' => null,
            'options' => [],
            'type' => 'create',
            'current_slug' => '',
            'parent_slug' => '',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['type'] = $options['type'];
        $view->vars['current_slug'] = $options['current_slug'];
        $view->vars['parent_slug'] = $options['parent_slug'];
    }
}
