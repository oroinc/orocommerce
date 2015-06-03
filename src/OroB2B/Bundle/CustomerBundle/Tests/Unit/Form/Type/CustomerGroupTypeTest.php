<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;

use OroB2B\Bundle\CustomerBundle\Form\Type\CustomerGroupType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;

class CustomerGroupTypeTest extends FormIntegrationTestCase
{
    /**
     * @var CustomerGroupType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AbstractQuery
     */
    protected $query;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new CustomerGroupType();
    }

    protected function tearDown()
    {
        unset($this->formType, $this->query);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $registry = $this->getRegistryForEntityIdentifierType();

        $entityType = new EntityType($registry);
        $entityIdentifierType = new EntityIdentifierType($registry);
        $priceListSelectStub = new PriceListSelectTypeStub();


        return [
            new PreloadedExtension(
                [
                    $entityType->getName() => $entityType,
                    $priceListSelectStub->getName() => $priceListSelectStub,
                    $entityIdentifierType->getName() => $entityIdentifierType
                ],
                []
            )
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
        $this->query->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturn(
                [
                    $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', 1),
                    $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', 2)
                ]
            );

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
                    'name' => 'customer_group_name',
                    'priceList' => null
                ],
                'expectedData' => [
                    'name' => 'customer_group_name',
                    'priceList' => null
                ]
            ],
            'with list' => [
                'options' => [],
                'defaultData' => [],
                'viewData' => [],
                'submittedData' => [
                    'name' => 'customer_group_name',
                    'priceList' => 1
                ],
                'expectedData' => [
                    'name' => 'customer_group_name',
                    'priceList' => $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', 2)
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

        $this->query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMockForAbstractClass();

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($this->query));

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb));

        $em->expects($this->any())
            ->method('getRepository')
            ->with($this->isType('string'))
            ->will($this->returnValue($repo));

        /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $registry */
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($em));

        return $registry;
    }

    /**
     * @param string $className
     * @param int $id
     * @return object
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;

        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);

        return $entity;
    }
}
