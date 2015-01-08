<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit;

use OroB2B\Bundle\ProductBundle\OroB2BProductBundle;

class OroB2BProductBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $bundle = new OroB2BProductBundle();
        $this->assertInstanceOf('\Symfony\Component\HttpKernel\Bundle\Bundle', $bundle);
    }
}
