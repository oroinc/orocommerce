<?php

namespace OroB2B\Bundle\FrontendBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\InstallerBundle\Form\Type\ConfigurationType;

use OroB2B\Bundle\FrontendBundle\Form\Type\Configuration\WebSettingsType;

class ConfigureTypeExtension extends AbstractTypeExtension
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'websettings',
            WebSettingsType::NAME,
            [
                'label' => 'orob2b_frontend.form.install_configuration.web_settings.header'
            ]
        );
    }

    /**
     * @return string
     */
    public function getExtendedType()
    {
        return ConfigurationType::NAME;
    }
}
