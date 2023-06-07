<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Company options
 */
class Company implements OptionInterface
{
    public const COMPANYNAME = 'COMPANYNAME';

    public function configureOption(OptionsResolver $resolver)
    {
        $resolver->setDefined(self::COMPANYNAME)
            ->addAllowedTypes(self::COMPANYNAME, 'string');
    }
}
