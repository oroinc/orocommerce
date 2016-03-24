<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

/**
 * @codingStandardsIgnoreStart
 * @link https://registration.paypal.com/welcomePage.do?country=US&mode=try#ProcessorSelectionNode
 * @link https://developer.paypal.com/docs/classic/payflow/integration-guide/#processing-platforms-supporting-card-present-transactions
 * @codingStandardsIgnoreEnd
 */
class Partner
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
        self::PAYPAL,
        self::AMEX,
        self::MESP,
        self::NOVA,
        self::NASH,
        self::NORT,
        self::SOUT,
        self::MAPP,
        self::NDCE,
        self::HTLD,
        self::LITL,
        self::MONE,
        self::PAYT,
        self::TMPA,
        self::PPAY,
        self::SNET,
        self::VITA,
        self::TELN,
        self::FIFT,
        self::WPAY,
    ];

    /**
     * @return array
     */
    public static function getPartnersList()
    {
        return array_combine(self::$list, self::$labels);
    }
}
