<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit;

use Oro\Bundle\WebsiteSearchBundle\WebsiteSearchBundle;

class WebsiteSearchBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testInitialization()
    {
        $bundle = new WebsiteSearchBundle();

        $this->assertInstanceOf('Oro\Bundle\WebsiteSearchBundle\WebsiteSearchBundle', $bundle);
    }
}
