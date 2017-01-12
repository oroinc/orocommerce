<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Twig;

use Oro\Bundle\CustomerBundle\Twig\CustomerExtension;
use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;

class CustomerExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CustomerExtension
     */
    protected $extension;

    /**
     * @var CustomerUserProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->securityProvider = $this->getMockBuilder(
            'Oro\Bundle\CustomerBundle\Security\CustomerUserProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new CustomerExtension(
            $this->securityProvider
        );
    }

    public function testGetFunctions()
    {
        $expectedFunctions = array(
            'is_granted_view_customer_user' => 'isGrantedViewCustomerUser',
        );

        /* @var $functions \Twig_Function_Method[] */
        $actualFunctions = $this->extension->getFunctions();
        $this->assertSameSize($expectedFunctions, $actualFunctions);

        foreach ($expectedFunctions as $twigFunction => $internalMethod) {
            $this->assertArrayHasKey($twigFunction, $actualFunctions);
            $this->assertInstanceOf('\Twig_Function_Method', $actualFunctions[$twigFunction]);
            $this->assertAttributeEquals($internalMethod, 'method', $actualFunctions[$twigFunction]);
        }
    }

    public function testGetName()
    {
        $this->assertEquals(CustomerExtension::NAME, $this->extension->getName());
    }

    public function testIsGrantedViewCustomerUser()
    {
        $object = new \stdClass();

        $this->securityProvider->expects($this->once())
            ->method('isGrantedViewCustomerUser')
            ->with($object)
            ->willReturn(true)
        ;

        $this->assertTrue($this->extension->isGrantedViewCustomerUser($object));
    }
}
