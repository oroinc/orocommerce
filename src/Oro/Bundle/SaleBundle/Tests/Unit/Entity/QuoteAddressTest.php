<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Entity;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class QuoteAddressTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['customerAddress', new CustomerAddress()],
            ['customerUserAddress', new CustomerUserAddress()],
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
