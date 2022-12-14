<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Model;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ShippingOriginTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var ShippingOrigin */
    private $shippingOrigin;

    protected function setUp(): void
    {
        $this->shippingOrigin = new ShippingOrigin();
    }

    public function testGettersAndSetters()
    {
        self::assertPropertyAccessors(
            $this->shippingOrigin,
            [
                ['country', new Country('US')],
                ['region', new Region('test')],
                ['regionText', 'test region text'],
                ['postalCode', 'test postal code'],
                ['city', 'test city'],
                ['street', 'test street 1'],
                ['street2', 'test street 2'],
            ]
        );
    }

    public function testConstructor()
    {
        $model = new ShippingOrigin(
            [
                'country' => 'US',
                'region' => 'test',
                'region_text' => 'test region text',
                'postal_code' => 'test postal code',
                'city' => 'test city',
                'street' => 'test street 1',
                'street2' => 'test street 2'
            ]
        );

        $this->assertEquals('US', $model->getCountry());
        $this->assertEquals('test', $model->getRegion());
        $this->assertEquals('test region text', $model->getRegionText());
        $this->assertEquals('test postal code', $model->getPostalCode());
        $this->assertEquals('test city', $model->getCity());
        $this->assertEquals('test street 1', $model->getStreet());
        $this->assertEquals('test street 2', $model->getStreet2());
    }

    public function testIsSystem()
    {
        $this->assertTrue($this->shippingOrigin->isSystem());

        $this->shippingOrigin->setSystem(false);
        $this->assertFalse($this->shippingOrigin->isSystem());
    }
}
