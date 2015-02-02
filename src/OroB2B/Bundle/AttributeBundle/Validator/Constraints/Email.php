<?php

namespace OroB2B\Bundle\AttributeBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Email as BaseEmail;

class Email extends BaseEmail implements AttributeConstraintInterface
{
    const ALIAS = 'email';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'Symfony\Component\Validator\Constraints\EmailValidator';
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }
}
