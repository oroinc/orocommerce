<?php

namespace Oro\Bundle\FrontendBundle\Tests\Unit\Form\Extension\Stub;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\InstallerBundle\Form\Type\ConfigurationType;

class ConfigurationTypeStub extends ConfigurationType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }
}
