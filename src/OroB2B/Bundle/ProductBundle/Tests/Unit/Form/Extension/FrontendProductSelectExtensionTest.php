<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Extension;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Extension\AbstractAccountUserAwareExtensionTest;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Form\Extension\FrontendProductSelectExtension;

class FrontendProductSelectExtensionTest extends AbstractAccountUserAwareExtensionTest
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var FrontendProductSelectExtension
     */
    protected $extension;

    protected function setUp()
    {
        parent::setUp();

        $this->extension = new FrontendProductSelectExtension($this->tokenStorage);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(ProductSelectType::NAME, $this->extension->getExtendedType());
    }

    public function testConfigureOptionsNonAccountUser()
    {
        $this->assertOptionsNotChangedForNonAccountUser();
    }

    public function testConfigureOptionsAccountUser()
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue(new AccountUser()));
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver */
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefault')
            ->with('grid_name', 'products-select-grid-frontend');

        $this->extension->configureOptions($resolver);
    }
}
