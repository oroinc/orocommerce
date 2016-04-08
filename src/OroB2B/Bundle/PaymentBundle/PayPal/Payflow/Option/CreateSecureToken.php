<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

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

            $account = new Account(false);
            $account->configureOption($resolver);
        }

        $returnUrl = new ReturnUrl();
        $returnUrl->configureOption($resolver);

        $errorUrl = new ErrorUrl();
        $errorUrl->configureOption($resolver);
    }
}
