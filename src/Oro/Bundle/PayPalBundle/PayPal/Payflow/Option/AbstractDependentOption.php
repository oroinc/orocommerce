<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

abstract class AbstractDependentOption implements OptionsDependentInterface
{
    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
    }

    /** {@inheritdoc} */
    public function isApplicableDependent(array $options)
    {
    }

    /** {@inheritdoc} */
    public function configureDependentOption(OptionsResolver $resolver, array $options)
    {
    }
}
