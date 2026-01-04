<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Url as BaseUrl;

class Url extends BaseUrl implements AliasAwareConstraintInterface
{
    public const ALIAS = 'url';

    #[\Override]
    public function validatedBy(): string
    {
        return 'Symfony\Component\Validator\Constraints\UrlValidator';
    }

    #[\Override]
    public function getAlias()
    {
        return self::ALIAS;
    }
}
