<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Entity;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AccountBundle\Entity\AccountAddress;
use Oro\Bundle\AccountBundle\Entity\AccountUserAddress;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;

class QuoteAddressTest extends AbstractTest
{
    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['accountAddress', new AccountAddress()],
            ['accountUserAddress', new AccountUserAddress()],
            ['region', new Region('combineCode')],
            ['country', new Country('en-US')],
            ['label', 'QuoteAddress'],
            ['street', 'street'],
            ['street2', 'street2'],
            ['city', 'city'],
            ['postalCode', 'postal_code'],
            ['organization', 'organization'],
            ['regionText', 'Region'],
            ['namePrefix', 'Name prefix'],
            ['firstName', 'First name'],
            ['middleName', 'Middle name'],
            ['lastName', 'Last name'],
            ['nameSuffix', 'Name suffix'],
            ['created', $now],
            ['updated', $now],
            ['phone', '11111111111']
        ];

        static::assertPropertyAccessors(new QuoteAddress(), $properties);
    }
}
