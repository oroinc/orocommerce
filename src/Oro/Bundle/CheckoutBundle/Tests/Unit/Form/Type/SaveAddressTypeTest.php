<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CheckoutBundle\Form\Type\SaveAddressType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class SaveAddressTypeTest extends FormIntegrationTestCase
{
    /**
     * @var  SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->securityFacade = $this->createMock('Oro\Bundle\SecurityBundle\SecurityFacade');
        parent::setUp();
    }

    public function testCreateByUserWithoutPermissions()
    {
        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValueMap(
                [
                    ['CREATE;entity:OroCustomerBundle:CustomerUserAddress', null, false],
                    ['CREATE;entity:OroCustomerBundle:CustomerAddress', null, false]
                ]
            ));

        $form = $this->factory->create(SaveAddressType::class);
        $this->assertInstanceOf(HiddenType::class, $form->getConfig()->getType()->getParent()->getInnerType());
        $this->assertEquals(0, $form->getConfig()->getData());
    }

    public function testCreateByUserWithPermissions()
    {
        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValueMap(
                [
                    ['CREATE;entity:OroCustomerBundle:CustomerUserAddress', null, true],
                    ['CREATE;entity:OroCustomerBundle:CustomerAddress', null, true]
                ]
            ));

        $form = $this->factory->create(SaveAddressType::class);
        $this->assertInstanceOf(CheckboxType::class, $form->getConfig()->getType()->getParent()->getInnerType());
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $type = new SaveAddressType($this->securityFacade);

        return [
            new PreloadedExtension([$type], [])
        ];
    }
}
