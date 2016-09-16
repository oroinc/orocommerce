<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Model;

use Oro\Bundle\PaymentBundle\Model\AddressOptionModel;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class AddressOptionModelTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['firstName', 'First name'],
            ['lastName', 'Last name'],
            ['street', 'Street'],
            ['street2', 'Street2'],
            ['city', 'City'],
            ['regionCode', 'State'],
            ['postalCode', 'Zip Code'],
            ['countryIso2', 'US']
        ];
        $this->assertPropertyAccessors(new AddressOptionModel(), $properties);
    }
}
