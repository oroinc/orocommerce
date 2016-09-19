<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit;

use Oro\Bundle\WebsiteSearchBundle\OroWebsiteSearchBundle;
use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\OroWebsiteSearchExtension;

class OroWebsiteSearchBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testGetContainerExtension()
    {
        $bundle = new OroWebsiteSearchBundle();

        $this->assertInstanceOf(OroWebsiteSearchExtension::class, $bundle->getContainerExtension());
    }
}
