<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

abstract class AbstractAccountUserAwareExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var AbstractTypeExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
    }

    public function assertOptionsNotChangedForNonAccountUser()
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())
            ->method('getUser');
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver */
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $resolver->expects($this->never())
            ->method($this->anything());

        $this->extension->configureOptions($resolver);
    }

    /**
     * @param object|null $user
     */
    protected function assertAccountUserTokenCall($user = null)
    {
        if (!$user) {
            $user = new AccountUser();
        }
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));
    }
}
