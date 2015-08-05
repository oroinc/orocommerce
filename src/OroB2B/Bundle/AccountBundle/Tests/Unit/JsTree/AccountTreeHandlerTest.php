<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\JsTree;

use Doctrine\Common\Collections\ArrayCollection;

use PHPUnit_Framework_MockObject_MockObject as Mock;

use OroB2B\Bundle\AccountBundle\JsTree\AccountTreeHandler;
use OroB2B\Bundle\AccountBundle\Entity\Account;

class AccountTreeHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test createTree
     */
    public function testCreateTree()
    {
        $class = 'OroB2BAccountBundle:Account';

        $account1 = $this->createAccount(1, 'Waclaw Zagorski');
        $account2 = $this->createAccount(2, 'Mieczyslaw Krawicz');
        $account3 = $this->createAccount(3, 'Adam Brodzisz');
        $account4 = $this->createAccount(4, 'Jerzy Ficowski');

        $this->addChildren($account3, [$account4]);
        $this->addChildren($account1, [$account2, $account3]);

        /** @var Mock|\Doctrine\ORM\EntityRepository $repository */
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($account1);

        /** @var Mock|\Doctrine\Bundle\DoctrineBundle\Registry $registry */
        $registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->once())
            ->method('getRepository')
            ->with($class)
            ->willReturn($repository);

        $handler = new AccountTreeHandler($class, $registry);

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
     * @return Mock|Account
     */
    protected function createAccount($id, $name)
    {
        $account = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\Account')
            ->disableOriginalConstructor()
            ->getMock();

        $account->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        $account->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        return $account;
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
