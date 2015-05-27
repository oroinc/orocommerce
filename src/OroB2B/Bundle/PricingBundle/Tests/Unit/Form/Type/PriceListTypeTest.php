<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;

class PriceListTypeTest extends FormIntegrationTestCase
{
    /**
     * @var PriceListType
     */
    protected $type;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AbstractQuery
     */
    protected $query;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->type = new PriceListType();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->type);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $currencySelectType = new CurrencySelectionTypeStub();
        $entityIdentifierType = new EntityIdentifierType($this->getRegistryForEntityIdentifierType());

        return [
            new PreloadedExtension(
                [
                    $currencySelectType->getName() => $currencySelectType,
                    $entityIdentifierType->getName() => $entityIdentifierType
                ],
                []
            )
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type);

        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('currencies'));
        $this->assertTrue($form->has('appendCustomers'));
        $this->assertTrue($form->has('removeCustomers'));
        $this->assertTrue($form->has('appendCustomerGroups'));
        $this->assertTrue($form->has('removeCustomerGroups'));
        $this->assertTrue($form->has('appendWebsites'));
        $this->assertTrue($form->has('removeWebsites'));
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param mixed $defaultData
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @param boolean $appendAndRemoveForms
     */
    public function testSubmit($defaultData, $submittedData, $expectedData, $appendAndRemoveForms = false)
    {
        if ($appendAndRemoveForms) {
            $this->query->expects($this->at(0))
                ->method('execute')
                ->willReturn([$this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\Customer', 1)]);
            $this->query->expects($this->at(1))
                ->method('execute')
                ->willReturn([$this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\Customer', 2)]);
            $this->query->expects($this->at(2))
                ->method('execute')
                ->willReturn([$this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup', 3)]);
            $this->query->expects($this->at(3))
                ->method('execute')
                ->willReturn([$this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup', 4)]);
            $this->query->expects($this->at(4))
                ->method('execute')
                ->willReturn([$this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', 5)]);
            $this->query->expects($this->at(5))
                ->method('execute')
                ->willReturn([$this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', 6)]);
        }

        if ($defaultData) {
            $existingPriceList = new PriceList();
            $class = new \ReflectionClass($existingPriceList);
            $prop  = $class->getProperty('id');
            $prop->setAccessible(true);

            $prop->setValue($existingPriceList, 42);
            $existingPriceList->setName($defaultData['name']);

            $defaultData = $existingPriceList;
        }

        $form = $this->factory->create($this->type, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        if (isset($existingPriceList)) {
            $this->assertEquals($existingPriceList, $form->getViewData());
        } else {
            $this->assertNull($form->getViewData());
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        /** @var PriceList $result */
        $result = $form->getData();
        $this->assertEquals($expectedData['name'], $result->getName());
        $this->assertEquals($expectedData['currencies'], $result->getCurrencies());
        $this->assertEquals($expectedData['appendCustomers'], $form->get('appendCustomers')->getData());
        $this->assertEquals($expectedData['removeCustomers'], $form->get('removeCustomers')->getData());
        $this->assertEquals($expectedData['appendCustomerGroups'], $form->get('appendCustomerGroups')->getData());
        $this->assertEquals($expectedData['removeCustomerGroups'], $form->get('removeCustomerGroups')->getData());
        $this->assertEquals($expectedData['appendWebsites'], $form->get('appendWebsites')->getData());
        $this->assertEquals($expectedData['removeWebsites'], $form->get('removeWebsites')->getData());
    }
    
    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'new price list' => [
                'defaultData' => null,
                'submittedData' => [
                    'name' => 'Test Price List',
                    'currencies' => [],
                    'appendCustomers' => [],
                    'removeCustomers' => [],
                    'appendCustomerGroups' => [],
                    'removeCustomerGroups' => [],
                    'appendWebsites' => [],
                    'removeWebsites' => [],
                ],
                'expectedData' => [
                    'name' => 'Test Price List',
                    'currencies' => [],
                    'default' => false,
                    'appendCustomers' => [],
                    'removeCustomers' => [],
                    'appendCustomerGroups' => [],
                    'removeCustomerGroups' => [],
                    'appendWebsites' => [],
                    'removeWebsites' => [],
                ]
            ],
            'update price list' => [
                'defaultData' => [
                    'name' => 'Test Price List',
                    'currencies' => ['USD', 'UAH'],
                    'appendCustomers' => [],
                    'removeCustomers' => [],
                    'appendCustomerGroups' => [],
                    'removeCustomerGroups' => [],
                    'appendWebsites' => [],
                    'removeWebsites' => [],
                ],
                'submittedData' => [
                    'name' => 'Test Price List 01',
                    'currencies' => ['USD', 'EUR'],
                    'appendCustomers' => [1],
                    'removeCustomers' => [2],
                    'appendCustomerGroups' => [3],
                    'removeCustomerGroups' => [4],
                    'appendWebsites' => [5],
                    'removeWebsites' => [6],
                ],
                'expectedData' => [
                    'name' => 'Test Price List 01',
                    'currencies' => ['USD', 'EUR'],
                    'appendCustomers' => [$this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\Customer', 1)],
                    'removeCustomers' => [$this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\Customer', 2)],
                    'appendCustomerGroups' => [
                        $this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup', 3)
                    ],
                    'removeCustomerGroups' => [
                        $this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup', 4)
                    ],
                    'appendWebsites' => [$this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', 5)],
                    'removeWebsites' => [$this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', 6)],
                ],
                'appendAndRemoveForms' => true,
            ]
        ];
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
            ->willReturn('id');

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $this->query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMockForAbstractClass();

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->any())
            ->method('where')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('setParameter')
            ->with('ids')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('getQuery')
            ->willReturn($this->query);

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $em->expects($this->any())
            ->method('getRepository')
            ->with($this->isType('string'))
            ->willReturn($repository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $registry */
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        return $registry;
    }

    public function testGetName()
    {
        $this->assertEquals(PriceListType::NAME, $this->type->getName());
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
