<?php

namespace Oro\Bundle\FrontendBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Oro\Bundle\InstallerBundle\Form\Type\ConfigurationType;
use Oro\Bundle\FrontendBundle\Form\Type\Configuration\WebType;

class ConfigurationTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'web',
            WebType::NAME,
            [
                'label' => 'oro_frontend.form.install_configuration.web.header'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ConfigurationType::NAME;
    }
}
