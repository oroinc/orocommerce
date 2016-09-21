<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;

class QuoteAddressProvider extends OrderAddressProvider
{
    const ADDRESS_TYPE_SHIPPING = 'shipping';

    const ADMIN_ACL_POSTFIX = '_backend';

    const ACCOUNT_ADDRESS_ANY = 'account_any';
    const ACCOUNT_USER_ADDRESS_DEFAULT = 'account_user_default';
    const ACCOUNT_USER_ADDRESS_ANY = 'account_user_any';

    const ADDRESS_SHIPPING_ACCOUNT_USE_ANY = 'oro_quote_address_shipping_account_use_any';
    const ADDRESS_SHIPPING_ACCOUNT_USER_USE_DEFAULT = 'oro_quote_address_shipping_account_user_use_default';
    const ADDRESS_SHIPPING_ACCOUNT_USER_USE_ANY = 'oro_quote_address_shipping_account_user_use_any';

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
