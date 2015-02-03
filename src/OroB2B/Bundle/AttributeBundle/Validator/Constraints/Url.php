<?php

namespace OroB2B\Bundle\AttributeBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Url as BaseUrl;

class Url extends BaseUrl implements AttributeConstraintInterface
{
    const ALIAS = 'url';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'Symfony\Component\Validator\Constraints\UrlValidator';
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }
}
