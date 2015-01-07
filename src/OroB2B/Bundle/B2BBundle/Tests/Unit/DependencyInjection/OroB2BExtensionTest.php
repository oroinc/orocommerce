<?php

namespace OroB2B\Bundle\B2BBundle\Tests\Unit;

use OroB2B\Bundle\B2BBundle\DependencyInjection\OroB2BExtension;

class OroB2BExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $extension = new OroB2BExtension();

        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Extension\ExtensionInterface', $extension);

        $this->assertInstanceOf(
            'Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface',
            $extension
        );
    }
}
