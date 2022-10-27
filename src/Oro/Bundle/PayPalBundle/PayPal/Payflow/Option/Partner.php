<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor;

// @codingStandardsIgnoreStart
/**
 * @link https://registration.paypal.com/welcomePage.do?country=US&mode=try#ProcessorSelectionNode
 * @link https://developer.paypal.com/docs/classic/payflow/integration-guide/#processing-platforms-supporting-card-present-transactions
 */
// @codingStandardsIgnoreEnd

/**
 * Partner option, contains all available partners in PayPal.
 */
class Partner extends AbstractOption
{
    const PARTNER = 'PARTNER';

    const AMEX = 'AMEX';
    const MESP = 'MESP';
    const NOVA = 'NOVA';
    const NASH = 'NASH';
    const NORT = 'NORT';
    const SOUT = 'SOUT';
    const MAPP = 'MAPP';
    const NDCE = 'NDCE';
    const HTLD = 'HTLD';
    const LITL = 'LITL';
    const MONE = 'MONE';
    const PAYT = 'PAYT';
    const TMPA = 'TMPA';
    const PPAY = 'PPAY';
    const SNET = 'SNET';
    const VITA = 'VITA';
    const TELN = 'TELN';
    const FIFT = 'FIFT';
    const VSA  = 'VSA';
    const WPAY = 'WPAY';
    const PAYPAL = Processor\PayPal::CODE;
    const PAYPALCA = Processor\PayPalCA::CODE;

    /**
     * @var array
     */
    public static $partners = [
        Partner::PAYPAL => Processor\PayPal::NAME,
        Partner::PAYPALCA => Processor\PayPalCA::NAME,
        Partner::AMEX => 'American Express',
        Partner::MESP => 'Cielo Payments',
        Partner::NOVA => 'Elavon',
        Partner::NASH => 'FDMS Nashville',
        Partner::NORT => 'FDMS North',
        Partner::SOUT => 'FDMS South',
        Partner::MAPP => 'Global Payments - Central',
        Partner::NDCE => 'Global Payments - East',
        Partner::HTLD => 'Heartland',
        Partner::LITL => 'Litle & Co',
        Partner::MONE => 'Moneris',
        Partner::PAYT => 'Paymentech - Salem',
        Partner::TMPA => 'Paymentech - Tampa',
        Partner::PPAY => 'Planet Payment',
        Partner::SNET => 'SecureNet',
        Partner::VITA => 'TSYS',
        Partner::TELN => 'TeleCheck 2',
        Partner::FIFT => 'Vantiv',
        Partner::VSA  => 'VSA',
        Partner::WPAY => 'World Pay',
    ];

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(Partner::PARTNER)
            ->addAllowedValues(Partner::PARTNER, array_keys(Partner::$partners));
    }
}
