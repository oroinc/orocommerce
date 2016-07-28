<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class CancelUrl implements OptionInterface
{
    const CANCELURL = 'CANCELURL';

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
                ->setRequired(CancelUrl::CANCELURL);
        }

        $resolver
            ->setDefined(CancelUrl::CANCELURL)
            ->addAllowedTypes(CancelUrl::CANCELURL, 'string');
    }
}
