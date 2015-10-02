<?php

namespace OroB2B\Bundle\FrontendBundle\Form\Type\Configuration;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class WebType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'oro_installer_backend_prefix',
                'text',
                array(
                    'label'         => 'orob2b_frontend.form.configuration.web.backend.prefix',
                    'constraints'   => array(
                        new Assert\NotBlank(),
                    ),
                )
            );
    }

    public function getName()
    {
        return 'orob2b_frontend_configuration_web';
    }
}