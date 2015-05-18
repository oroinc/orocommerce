<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;

use OroB2B\Bundle\CustomerBundle\Form\Type\CustomerGroupType;

class CustomerGroupTypeTest extends FormIntegrationTestCase
{
    /**
     * @var CustomerGroupType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new CustomerGroupType();
    }

    protected function tearDown()
    {
        unset($this->em, $this->formType);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $registry = $this->getRegistryForEntityIdentifierType();

        $entityIdentifierType = new EntityIdentifierType($registry);

        return [
            new PreloadedExtension([$entityIdentifierType->getName() => $entityIdentifierType], [])
        ];
    }

    /**
     * @param array $options
     * @param mixed $defaultData
     * @param mixed $viewData
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        array $options,
        array $defaultData,
        array $viewData,
        array $submittedData,
        array $expectedData
    ) {
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertTrue($form->has('appendCustomers'));
        $this->assertTrue($form->has('removeCustomers'));

        $formConfig = $form->getConfig();
        $this->assertNull($formConfig->getOption('data_class'));

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'default' => [
                'options' => [],
                'defaultData' => [],
                'viewData' => [],
                'submittedData' => [
                    'name' => 'customer_group_name'
                ],
                'expectedData' => [
                    'name' => 'customer_group_name'
                ]
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->formType->getName());
        $this->assertEquals('orob2b_customer_group_type', $this->formType->getName());
    }

    /**
     * @return ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRegistryForEntityIdentifierType()
    {
        $metadata = $this->getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));

        /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $registry */
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([['OroB2B\Bundle\CustomerBundle\Entity\Customer', $em]]);

        return $registry;
    }
}
