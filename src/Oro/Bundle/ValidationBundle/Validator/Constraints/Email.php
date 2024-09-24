<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Email as BaseEmail;

class Email extends BaseEmail implements AliasAwareConstraintInterface
{
    const ALIAS = 'email';

    #[\Override]
    public function validatedBy(): string
    {
        return 'Symfony\Component\Validator\Constraints\EmailValidator';
    }

    #[\Override]
    public function getAlias()
    {
        return self::ALIAS;
    }
}
