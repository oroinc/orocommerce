<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\DependencyInjection;

use OroB2B\Bundle\ProductBundle\DependencyInjection\OroB2BProductExtension;

class OroB2BProductExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $extension = new OroB2BProductExtension();

        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Extension\ExtensionInterface', $extension);

        $this->assertInstanceOf(
            'Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface',
            $extension
        );
    }
}
