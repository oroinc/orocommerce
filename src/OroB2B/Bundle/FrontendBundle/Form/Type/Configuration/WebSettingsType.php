<?php

namespace OroB2B\Bundle\FrontendBundle\Form\Type\Configuration;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class WebSettingsType extends AbstractType
{
    const NAME = 'orob2b_frontend_install_configuration_web_settings';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'oro_installer_websettings_backend_prefix',
            'text',
            [
                'label' => 'orob2b_frontend.form.install_configuration.web_settings.backend_prefix',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex(['pattern' => '~^/\w+$~', 'message' => 'orob2b_frontend.regex.backend_prefix'])
                ],
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
