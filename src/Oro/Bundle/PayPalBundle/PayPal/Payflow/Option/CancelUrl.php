<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Configures cancel URL option for PayPal Payflow transactions.
 *
 * Specifies the URL to redirect to when a customer cancels the transaction,
 * with configurable requirement based on transaction type.
 */
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

    #[\Override]
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
