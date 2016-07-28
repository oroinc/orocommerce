<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class ReturnUrl implements OptionInterface
{
    const RETURNURL = 'RETURNURL';

    /** @var bool */
    protected $required;

    /** @param bool $required */
    public function __construct($required = true)
    {
        $this->required = $required;
    }

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        if ($this->required) {
            $resolver
                ->setRequired(ReturnUrl::RETURNURL);
        }

        $resolver
            ->setDefined(ReturnUrl::RETURNURL)
            ->addAllowedTypes(ReturnUrl::RETURNURL, 'string');
    }
}
