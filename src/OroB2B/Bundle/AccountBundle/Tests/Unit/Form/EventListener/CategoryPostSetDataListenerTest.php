<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\EventListener;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvent;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Form\EventListener\CategoryPostSetDataListener;

class CategoryPostSetDataListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var  CategoryPostSetDataListener */
    protected $listener;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryVisibilityRepository;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */

    protected $accountCategoryVisibilityRepository;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $accountGroupCategoryVisibilityRepository;

    /** @var  FormInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $form;

    /** @var  Account| \PHPUnit_Framework_MockObject_MockObject */
    protected $account;

    /** @var  AccountGroup| \PHPUnit_Framework_MockObject_MockObject */
    protected $accountGroup;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->listener = new CategoryPostSetDataListener($this->registry);
    }

    public function testOnPostSetData()
    {
        $this->setRepositories();
        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')->disableOriginalConstructor()->getMock();
        /** @var Category $category */
        $category = $this->getEntity('OroB2B\Bundle\CatalogBundle\Entity\Category', 1);
        $event->expects($this->once())->method('getData')->willReturn($category);
        $event->expects($this->any())->method('getForm')->willReturn($this->form);

        $this->setCategoryVisibilityExpectations($category);
        $this->setAccountCategoryVisibilityExpectations($category);
        $this->setAccountGroupCategoryVisibilityExpectations($category);

        $this->listener->onPostSetData($event);
    }

    /**
     * @dataProvider onPostSetDataWithoutCategoryDataProvider
     * @param $category
     */
    public function testOnPostSetDataWithoutCategory($category)
    {
        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')->disableOriginalConstructor()->getMock();
        $event->expects($this->once())->method('getData')->willReturn($category);
        $this->registry->expects($this->never())->method('getManagerForClass');
        $this->listener->onPostSetData($event);
    }

    public function onPostSetDataWithoutCategoryDataProvider()
    {
        return [
            ['categoryWithoutId' => new Category()],
            ['anotherClass' => new \StdClass()],
            ['null' => null],
            ['false' => false],
            ['string' => 'Category'],
            ['integer' => 1],
        ];
    }

    /**
     * @param Category $category
     */
    protected function setCategoryVisibilityExpectations(Category $category)
    {
        $categoryVisibility = $this->getCategoryVisibility();
        $this->categoryVisibilityRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['category' => $category])
            ->willReturn($categoryVisibility);

        /** @var  FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->form->expects($this->at(0))->method('get')->with('categoryVisibility')->willReturn($form);
        $form->expects($this->once())->method('setData')->with(CategoryVisibility::HIDDEN);
    }

    protected function setAccountCategoryVisibilityExpectations(Category $category)
    {
        $accountCategoryVisibility = $this->getAccountCategoryVisibility();
        $this->accountCategoryVisibilityRepository->expects($this->once())
            ->method('findBy')
            ->with(['category' => $category])
            ->willReturn([$accountCategoryVisibility]);
        /** @var  FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->form->expects($this->at(1))->method('get')->with('visibilityForAccount')->willReturn($form);
        $form->expects($this->once())->method('setData')->with(
            [
                1 =>
                    [
                        'entity' => $this->account,
                        'data' =>
                            ['visibility' => 1],
                    ],
            ]
        );
    }

    /**
     * @param Category $category
     */
    protected function setAccountGroupCategoryVisibilityExpectations(Category $category)
    {
        $accountGroupCategoryVisibility = $this->getAccountGroupCategoryVisibility();
        $this->accountGroupCategoryVisibilityRepository->expects($this->once())
            ->method('findBy')
            ->with(['category' => $category])
            ->willReturn([$accountGroupCategoryVisibility]);
        /** @var  FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->form->expects($this->at(2))->method('get')->with('visibilityForAccountGroup')->willReturn($form);
        $form->expects($this->once())->method('setData')->with(
            [
                1 =>
                    [
                        'entity' => $this->accountGroup,
                        'data' =>
                            ['visibility' => 1],
                    ],
            ]
        );
    }

    /**
     * @param string $className
     * @param int $id
     * @param string $primaryKey
     * @return object
     */
    protected function getEntity($className, $id, $primaryKey = 'id')
    {
        static $entities = [];

        if (!isset($entities[$className])) {
            $entities[$className] = [];
        }

        if (!isset($entities[$className][$id])) {
            $entities[$className][$id] = new $className;
            $reflectionClass = new \ReflectionClass($className);
            $method = $reflectionClass->getProperty($primaryKey);
            $method->setAccessible(true);
            $method->setValue($entities[$className][$id], $id);
        }

        return $entities[$className][$id];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CategoryVisibility
     */
    protected function getCategoryVisibility()
    {
        $categoryVisibility = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\CategoryVisibility')
            ->setMethods(['getVisibility'])
            ->getMock();
        $categoryVisibility->expects($this->once())
            ->method('getVisibility')
            ->willReturn(CategoryVisibility::HIDDEN);

        return $categoryVisibility;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AccountCategoryVisibility
     */
    protected function getAccountCategoryVisibility()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Account $account */
        $this->account = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\Account');
        $this->account->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $visibility = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility')
            ->setMethods(['getVisibility', 'getAccount'])
            ->getMock();
        $visibility->expects($this->exactly(2))
            ->method('getAccount')
            ->willReturn($this->account);
        $visibility->expects($this->once())
            ->method('getVisibility')
            ->willReturn($this->getEnumValue());

        return $visibility;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AccountGroupCategoryVisibility
     */
    protected function getAccountGroupCategoryVisibility()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AccountGroup $accountGroup */
        $this->accountGroup = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountGroup');
        $this->accountGroup->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $visibility = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility')
            ->setMethods(['getVisibility', 'getAccountGroup'])
            ->getMock();
        $visibility->expects($this->exactly(2))
            ->method('getAccountGroup')
            ->willReturn($this->accountGroup);
        $visibility->expects($this->once())
            ->method('getVisibility')
            ->willReturn($this->getEnumValue());

        return $visibility;
    }

    protected function setRepositories()
    {
        $this->categoryVisibilityRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->accountCategoryVisibilityRepository = clone $this->categoryVisibilityRepository;
        $this->accountGroupCategoryVisibilityRepository = clone $this->categoryVisibilityRepository;

        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $em */
        $em = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $em->expects($this->at(0))
            ->method('getRepository')
            ->with('OroB2BAccountBundle:CategoryVisibility')
            ->willReturn($this->categoryVisibilityRepository);
        $em->expects($this->at(1))
            ->method('getRepository')
            ->with('OroB2BAccountBundle:AccountCategoryVisibility')
            ->willReturn($this->accountCategoryVisibilityRepository);
        $em->expects($this->at(2))
            ->method('getRepository')
            ->with('OroB2BAccountBundle:AccountGroupCategoryVisibility')
            ->willReturn($this->accountGroupCategoryVisibilityRepository);

        $this->registry->expects($this->at(0))
            ->method('getManagerForClass')
            ->with('OroB2BAccountBundle:CategoryVisibility')
            ->willReturn($em);
        $this->registry->expects($this->at(1))
            ->method('getManagerForClass')
            ->with('OroB2BAccountBundle:AccountCategoryVisibility')
            ->willReturn($em);
        $this->registry->expects($this->at(2))
            ->method('getManagerForClass')
            ->with('OroB2BAccountBundle:AccountGroupCategoryVisibility')
            ->willReturn($em);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AbstractEnumValue
     */
    protected function getEnumValue()
    {
        $enumValue = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue')
            ->disableOriginalConstructor()
            ->getMock();
        $enumValue->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        return $enumValue;
    }
}
