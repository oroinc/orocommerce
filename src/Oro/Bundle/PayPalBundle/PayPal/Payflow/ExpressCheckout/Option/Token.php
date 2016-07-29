<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsDependentInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class Token extends AbstractOption implements OptionsDependentInterface
{
    const TOKEN = 'TOKEN';

    /**
     * {@inheritdoc}
     */
    public function isApplicableDependent(array $options)
    {
        return isset($options[Action::ACTION]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureDependentOption(OptionsResolver $resolver, array $options)
    {
        if (in_array($options[Action::ACTION], [Action::GET_EC_DETAILS, Action::DO_EC], true)) {
            $resolver
                ->setRequired(Token::TOKEN);
        }

        $resolver
            ->setDefined(Token::TOKEN)
            ->addAllowedTypes(Token::TOKEN, 'string');
    }
}
