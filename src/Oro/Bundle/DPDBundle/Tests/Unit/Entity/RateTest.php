<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Entity;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Entity\Rate;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class RateTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new Rate(), [
            ['transport', new DPDTransport()],
            ['shippingService', new ShippingService()],
            ['country', new Country('DE')],
            ['region', new Region('DE-DE')],
            ['regionText', 'region text'],
            ['weightValue', 1.0],
            ['priceValue', '1.000'],
        ]);
    }

    public function testToString()
    {
        $entity = new Rate();
        $ss = (new ShippingService())->setCode('Classic');
        $entity->setShippingService($ss);
        $country = (new Country('DE'))->setName('DE');
        $entity->setCountry($country);
        $entity->setPriceValue('1.000');
        static::assertEquals('Classic, DE, *, * => 1.000', (string) $entity);
        $region = (new Region('DE-DE'))->setName('DE-DE');
        $entity->setRegion($region);
        static::assertEquals('Classic, DE, DE-DE, * => 1.000', (string) $entity);
        $entity->setRegion(null);
        $entity->setRegionText('region text');
        static::assertEquals('Classic, DE, region text, * => 1.000', (string) $entity);
        $entity->setRegion($region);
        static::assertEquals('Classic, DE, DE-DE, * => 1.000', (string) $entity);
        $entity->setWeightValue(1.0);
        static::assertEquals('Classic, DE, DE-DE, 1.00 => 1.000', (string) $entity);
    }
}
