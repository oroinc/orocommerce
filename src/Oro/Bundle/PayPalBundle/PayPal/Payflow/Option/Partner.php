<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Processor;

// phpcs:disable
/**
 * @link https://registration.paypal.com/welcomePage.do?country=US&mode=try#ProcessorSelectionNode
 * @link https://developer.paypal.com/docs/classic/payflow/integration-guide/#processing-platforms-supporting-card-present-transactions
 */
// phpcs:enable

/**
 * Partner option, contains all available partners in PayPal.
 */
class Partner extends AbstractOption
{
    public const PARTNER = 'PARTNER';

    public const AMEX = 'AMEX';
    public const MESP = 'MESP';
    public const NOVA = 'NOVA';
    public const NASH = 'NASH';
    public const NORT = 'NORT';
    public const SOUT = 'SOUT';
    public const MAPP = 'MAPP';
    public const NDCE = 'NDCE';
    public const HTLD = 'HTLD';
    public const LITL = 'LITL';
    public const MONE = 'MONE';
    public const PAYT = 'PAYT';
    public const TMPA = 'TMPA';
    public const PPAY = 'PPAY';
    public const SNET = 'SNET';
    public const VITA = 'VITA';
    public const TELN = 'TELN';
    public const FIFT = 'FIFT';
    public const VSA  = 'VSA';
    public const WPAY = 'WPAY';
    public const PAYPAL = Processor\PayPal::CODE;
    public const PAYPALCA = Processor\PayPalCA::CODE;

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

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(Partner::PARTNER)
            ->addAllowedValues(Partner::PARTNER, array_keys(Partner::$partners));
    }
}
