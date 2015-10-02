<?php

namespace OroB2B\Bundle\FrontendBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigureTypeExtension extends AbstractTypeExtension
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'backend',
                'orob2b_frontend_configuration_web',
                array(
                    'label' => 'orob2b_frontend.form.configuration.web.header'
                )
            );
    }

    /**
     * @return string
     */
    public function getExtendedType()
    {
        return 'oro_installer_configuration';
    }
}