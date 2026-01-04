<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;

class QuoteAddressProvider extends OrderAddressProvider
{
    public const ADDRESS_TYPE_SHIPPING = 'shipping';

    public const ADMIN_ACL_POSTFIX = '_backend';

    public const ACCOUNT_ADDRESS_ANY = 'customer_any';
    public const ACCOUNT_USER_ADDRESS_DEFAULT = 'customer_user_default';
    public const ACCOUNT_USER_ADDRESS_ANY = 'customer_user_any';

    public const ADDRESS_SHIPPING_ACCOUNT_USE_ANY = 'oro_quote_address_shipping_customer_use_any';
    public const ADDRESS_SHIPPING_ACCOUNT_USER_USE_DEFAULT = 'oro_quote_address_shipping_customer_user_use_default';
    public const ADDRESS_SHIPPING_ACCOUNT_USER_USE_ANY = 'oro_quote_address_shipping_customer_user_use_any';

    /**
     * @var array
     */
    protected $permissionsByType = [
        self::ADDRESS_TYPE_SHIPPING => [
            self::ACCOUNT_ADDRESS_ANY => self::ADDRESS_SHIPPING_ACCOUNT_USE_ANY,
            self::ACCOUNT_USER_ADDRESS_DEFAULT => self::ADDRESS_SHIPPING_ACCOUNT_USER_USE_DEFAULT,
            self::ACCOUNT_USER_ADDRESS_ANY => self::ADDRESS_SHIPPING_ACCOUNT_USER_USE_ANY,
        ]
    ];

    /**
     * @param string $type
     * @throws \InvalidArgumentException
     */
    #[\Override]
    public static function assertType($type)
    {
        $supportedTypes = [self::ADDRESS_TYPE_SHIPPING];
        if (!in_array($type, $supportedTypes, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unknown type "%s", known types are: %s',
                    $type,
                    implode(', ', $supportedTypes)
                )
            );
        }
    }
}
