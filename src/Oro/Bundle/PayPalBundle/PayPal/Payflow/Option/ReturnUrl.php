<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Configures return URL option for PayPal Payflow transactions.
 *
 * Specifies the URL to redirect to after successful transaction completion,
 * with configurable requirement based on transaction type.
 */
class ReturnUrl implements OptionInterface
{
    public const RETURNURL = 'RETURNURL';

    /** @var bool */
    protected $required;

    /** @param bool $required */
    public function __construct($required = true)
    {
        $this->required = $required;
    }

    #[\Override]
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
