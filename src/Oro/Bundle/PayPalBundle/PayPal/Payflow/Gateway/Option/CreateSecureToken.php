<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractBooleanOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\ReturnUrl;

/**
 * Configures secure token creation option for PayPal Payflow Gateway transactions.
 *
 * Controls whether to create a secure token for transparent redirect payment forms,
 * adjusting related options like customer account and return URL requirements.
 */
class CreateSecureToken extends AbstractBooleanOption
{
    public const CREATESECURETOKEN = 'CREATESECURETOKEN';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(CreateSecureToken::CREATESECURETOKEN)
            ->setNormalizer(
                CreateSecureToken::CREATESECURETOKEN,
                $this->getNormalizer(CreateSecureToken::YES, CreateSecureToken::NO)
            );

        if ($resolver->isRequired(Customer::ACCT)) {
            $resolver->remove(Customer::ACCT);
            $resolver->addOption(new Customer(false));
        }

        $resolver
            ->addOption(new ReturnUrl(false))
            ->addOption(new ErrorUrl());
    }
}
