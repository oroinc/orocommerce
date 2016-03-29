<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @codingStandardsIgnoreStart
 * @link https://registration.paypal.com/welcomePage.do?country=US&mode=try#ProcessorSelectionNode
 * @link https://developer.paypal.com/docs/classic/payflow/integration-guide/#processing-platforms-supporting-card-present-transactions
 * @codingStandardsIgnoreEnd
 */
class Partner extends AbstractOption
{
    const PARTNER = 'PARTNER';

    const PAYPAL = 'PayPal';
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
    const WPAY = 'WPAY';

    /**
     * @var array
     */
    public static $labels = [
        'PayPal',
        'American Express',
        'Cielo Payments',
        'Elavon',
        'FDMS Nashville',
        'FDMS North',
        'FDMS South',
        'Global Payments - Central',
        'Global Payments - East',
        'Heartland',
        'Litle & Co',
        'Moneris',
        'Paymentech - Salem',
        'Paymentech - Tampa',
        'Planet Payment',
        'SecureNet',
        'TSYS',
        'TeleCheck 2',
        'Vantiv',
        'World Pay',
    ];

    /**
     * @var array
     */
    public static $list = [
        Partner::PAYPAL,
        Partner::AMEX,
        Partner::MESP,
        Partner::NOVA,
        Partner::NASH,
        Partner::NORT,
        Partner::SOUT,
        Partner::MAPP,
        Partner::NDCE,
        Partner::HTLD,
        Partner::LITL,
        Partner::MONE,
        Partner::PAYT,
        Partner::TMPA,
        Partner::PPAY,
        Partner::SNET,
        Partner::VITA,
        Partner::TELN,
        Partner::FIFT,
        Partner::WPAY,
    ];

    /**
     * @return array
     */
    public static function getPartnersList()
    {
        return array_combine(Partner::$list, Partner::$labels);
    }

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(Partner::PARTNER)
            ->addAllowedValues(Partner::PARTNER, Partner::$list);
    }
}
