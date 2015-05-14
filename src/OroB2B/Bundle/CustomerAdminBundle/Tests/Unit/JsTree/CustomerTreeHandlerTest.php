<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Tests\Unit\JsTree;

use Doctrine\Common\Collections\ArrayCollection;

use PHPUnit_Framework_MockObject_MockObject as Mock;

use OroB2B\Bundle\CustomerAdminBundle\JsTree\CustomerTreeHandler;
use OroB2B\Bundle\CustomerAdminBundle\Entity\Customer;

class CustomerTreeHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test createTree
     */
    public function testCreateTree()
    {
        $class = 'OroB2BCustomerAdminBundle:Customer';

        $customer1 = $this->createCustomer(1, 'Waclaw Zagorski');
        $customer2 = $this->createCustomer(2, 'Mieczyslaw Krawicz');
        $customer3 = $this->createCustomer(3, 'Adam Brodzisz');
        $customer4 = $this->createCustomer(4, 'Jerzy Ficowski');

        $this->addChildren($customer3, [$customer4]);
        $this->addChildren($customer1, [$customer2, $customer3]);

        /** @var Mock|\Doctrine\ORM\EntityRepository $repository */
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($customer1);

        /** @var Mock|\Doctrine\Bundle\DoctrineBundle\Registry $registry */
        $registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->once())
            ->method('getRepository')
            ->with($class)
            ->willReturn($repository);

        $handler = new CustomerTreeHandler($class, $registry);

        $this->assertEquals(
            [
                [
                    'id' => '2',
                    'parent' => '#',
                    'text' => 'Mieczyslaw Krawicz',
                    'state' => [
                        'opened' => false,
                    ],
                ],
                [
                    'id' => '3',
                    'parent' => '#',
                    'text' => 'Adam Brodzisz',
                    'state' => [
                        'opened' => true,
                    ],
                ],
                [
                    'id' => '4',
                    'parent' => '3',
                    'text' => 'Jerzy Ficowski',
                    'state' => [
                        'opened' => false,
                    ],
                ],
            ],
            $handler->createTree(1)
        );
    }

    /**
     * @param int $id
     * @param string $name
     *
     * @return Mock|Customer
     */
    protected function createCustomer($id, $name)
    {
        $customer = $this->getMockBuilder('OroB2B\Bundle\CustomerAdminBundle\Entity\Customer')
            ->disableOriginalConstructor()
            ->getMock();

        $customer->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        $customer->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        return $customer;
    }

    /**
     * @param Mock $parent
     * @param array $children
     */
    protected function addChildren(Mock $parent, array $children = [])
    {
        foreach ($children as $child) {
            /** @var Mock $child */
            $child->expects($this->any())
                ->method('getParent')
                ->willReturn($parent);
            $child->expects($this->any())
                ->method('getChildren')
                ->willReturn(new ArrayCollection());
        }

        $parent->expects($this->any())
            ->method('getChildren')
            ->willReturn(new ArrayCollection($children));
    }
}
