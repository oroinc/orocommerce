<?php

namespace OroB2B\Bundle\UserBundle\Tests\Security;

use OroB2B\Bundle\UserBundle\Entity\User;

class EmailProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testFindUser()
    {
        $email = 'test@example.com';

        $provider = $this->getMockBuilder('OroB2B\Bundle\UserBundle\Security\EmailProvider')
            ->disableOriginalConstructor()
            ->setMethods(['findUser'])
            ->getMock();
        $provider->expects($this->once())
            ->method('findUser')
            ->with($email)
            ->willReturn(new User());

        $class = new \ReflectionClass($provider);
        $method = $class->getMethod('findUser');
        $method->setAccessible(true);

        $this->assertInstanceOf('FOS\UserBundle\Model\UserInterface', $method->invokeArgs($provider, [$email]));
    }
}
