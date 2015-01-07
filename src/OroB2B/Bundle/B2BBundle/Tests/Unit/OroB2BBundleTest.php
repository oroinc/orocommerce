<?php

namespace OroB2B\Bundle\B2BBundle\Tests\Unit;

use OroB2B\Bundle\B2BBundle\OroB2BBundle;

class OroB2BBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $bundle = new OroB2BBundle();
        $this->assertInstanceOf('\Symfony\Component\HttpKernel\Bundle\Bundle', $bundle);
    }
}
