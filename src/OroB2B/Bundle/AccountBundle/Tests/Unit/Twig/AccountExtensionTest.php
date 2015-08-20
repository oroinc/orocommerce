<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Twig;

use OroB2B\Bundle\AccountBundle\Twig\AccountExtension;
use OroB2B\Bundle\AccountBundle\Security\AccountUserProvider;

class AccountExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AccountExtension
     */
    protected $extension;

    /**
     * @var AccountUserProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->securityProvider = $this->getMockBuilder(
            'OroB2B\Bundle\AccountBundle\Security\AccountUserProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new AccountExtension(
            $this->securityProvider
        );
    }

    public function testGetFunctions()
    {
        $expectedFunctions = array(
            'is_granted_view_account_user' => 'isGrantedViewAccountUser',
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
        $this->assertEquals(AccountExtension::NAME, $this->extension->getName());
    }

    public function testIsGrantedViewAccountUser()
    {
        $object = new \stdClass();

        $this->securityProvider->expects($this->once())
            ->method('isGrantedViewAccountUser')
            ->with($object)
            ->willReturn(true)
        ;

        $this->assertTrue($this->extension->isGrantedViewAccountUser($object));
    }
}
