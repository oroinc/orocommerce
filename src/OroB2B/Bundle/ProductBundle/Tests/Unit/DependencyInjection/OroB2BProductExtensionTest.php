<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\DependencyInjection;

use OroB2B\Bundle\ProductBundle\DependencyInjection\OroB2BProductExtension;

class OroB2BProductExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $expectedParameters = [
        'orob2b_product.product.class' => 'OroB2B\Bundle\ProductBundle\Entity\Product'
    ];

    public function testLoad()
    {
        $actualParameters  = [];

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->setMethods(['setParameter'])
            ->getMock();

        $container->expects($this->any())
            ->method('setParameter')
            ->will(
                $this->returnCallback(
                    function ($name, $value) use (&$actualParameters) {
                        $actualParameters[$name] = $value;
                    }
                )
            );

        $extension = new OroB2BProductExtension();
        $extension->load([], $container);

        foreach ($this->expectedParameters as $parameterName => $parameterValue) {
            $this->assertArrayHasKey($parameterName, $actualParameters);
            $this->assertEquals($parameterValue, $actualParameters[$parameterName]);
        }
    }
}
