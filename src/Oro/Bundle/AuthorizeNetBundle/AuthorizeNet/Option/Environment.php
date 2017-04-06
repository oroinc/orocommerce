<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class Environment implements OptionInterface
{
    const ENVIRONMENT = 'environment';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(Environment::ENVIRONMENT)
            ->addAllowedValues(
                Environment::ENVIRONMENT,
                [
                    \net\authorize\api\constants\ANetEnvironment::SANDBOX,
                    \net\authorize\api\constants\ANetEnvironment::PRODUCTION
                ]
            );
    }
}
