<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\ShippingBundle\Model\ShippingOrigin;

class ShippingOriginTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var ShippingOrigin */
    protected $shippingOrigin;

    protected function setUp()
    {
        $this->shippingOrigin = new ShippingOrigin();
    }

    protected function tearDown()
    {
        unset($this->shippingOrigin);
    }

    public function testGettersAndSetters()
    {
        static::assertPropertyAccessors(
            $this->shippingOrigin,
            [
                ['country', new Country('us')],
                ['region', new Region('test')],
                ['regionText', 'test region text'],
                ['postalCode', 'test postal code'],
                ['city', 'test city'],
                ['street', 'test street 1'],
                ['street2', 'test street 2'],
            ]
        );
    }

    public function testIsSystem()
    {
        self::assertFalse($this->shippingOrigin->isSystem());
        $this->shippingOrigin->setSystem(true);
        self::assertTrue($this->shippingOrigin->isSystem());
    }
}
