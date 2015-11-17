<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\SystemConfig;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigBag;

class PriceListConfigBagTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $configs = new ArrayCollection([]);
        $bag = new PriceListConfigBag();

        $bag->setConfigs($configs);
        $this->assertSame($configs, $bag->getConfigs());
    }

    public function testAddConfig()
    {
        $model = new PriceListConfigBag();
        $priceList = new PriceListConfig();
        $model->addConfig($priceList);

        $this->assertTrue($model->getConfigs()->contains($priceList));
    }
}
