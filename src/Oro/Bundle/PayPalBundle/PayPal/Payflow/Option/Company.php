<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Company options
 */
class Company implements OptionInterface
{
    public const COMPANYNAME = 'COMPANYNAME';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver->setDefined(self::COMPANYNAME)
            ->addAllowedTypes(self::COMPANYNAME, 'string');
    }
}
