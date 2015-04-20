<?php

namespace OroB2B\Bundle\RFPBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RequestType extends AbstractType
{
    const NAME = 'orob2b_rfp_request_type';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', 'text')
            ->add('lastName', 'text')
            ->add('email', 'text')
            ->add('phone', 'text')
            ->add('company', 'text')
            ->add('role', 'text')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'OroB2B\Bundle\RFPBundle\Entity\Request'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
