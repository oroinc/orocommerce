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
            ->add('firstName', 'text', [
                'label' => 'orob2b.rfp.request.first_name.label'
            ])
            ->add('lastName', 'text', [
                'label' => 'orob2b.rfp.request.last_name.label'
            ])
            ->add('email', 'text', [
                'label' => 'orob2b.rfp.request.email.label'
            ])
            ->add('phone', 'text', [
                'label' => 'orob2b.rfp.request.phone.label'
            ])
            ->add('company', 'text', [
                'label' => 'orob2b.rfp.request.company.label'
            ])
            ->add('role', 'text', [
                'label' => 'orob2b.rfp.request.role.label'
            ])
            ->add('body', 'textarea', [
                'label' => 'orob2b.rfp.request.body.label'
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'OroB2B\Bundle\RFPBundle\Entity\Request'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
