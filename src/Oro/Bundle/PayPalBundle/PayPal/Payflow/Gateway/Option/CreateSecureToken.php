<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractBooleanOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\ReturnUrl;

class CreateSecureToken extends AbstractBooleanOption
{
    const CREATESECURETOKEN = 'CREATESECURETOKEN';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(CreateSecureToken::CREATESECURETOKEN)
            ->setNormalizer(
                CreateSecureToken::CREATESECURETOKEN,
                $this->getNormalizer(CreateSecureToken::YES, CreateSecureToken::NO)
            );

        if ($resolver->isRequired(Account::ACCT)) {
            $resolver->remove(Account::ACCT);
            $resolver->addOption(new Account(false));
        }

        $resolver
            ->addOption(new ReturnUrl(false))
            ->addOption(new ErrorUrl());
    }
}
