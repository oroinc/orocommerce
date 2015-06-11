<?php

namespace OroB2B\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FrontendRolesType extends AbstractType
{
    const NAME = 'orob2b_frontend_roles';

    /**
     * @var array
     */
    protected $roles = [];

    /**
     * @param array $frontendRoles
     */
    public function __construct(array $frontendRoles)
    {
        $this->roles = $frontendRoles;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($builder) {
                $form = $event->getForm();

                /** @var FormInterface $child */
                foreach ($form as $child) {
                    $options = $child->getConfig()->getOptions();
                    $value = $options['value'];

                    if (isset($this->roles[$value]['description'])) {
                        $options['tooltip'] = $this->roles[$value]['description'];

                        if (array_key_exists('auto_initialize', $options)) {
                            $options['auto_initialize'] = false;
                        }

                        $form->add(
                            $form->getConfig()->getFormFactory()->createNamed(
                                $child->getName(),
                                'checkbox',
                                null,
                                $options
                            )
                        );
                    }
                }
            }
        );
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $choices = [];
        foreach ($this->roles as $key => $value) {
            $choices[$key] = $value['label'];
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'multiple' => true,
            'expanded' => true
        ]);
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
