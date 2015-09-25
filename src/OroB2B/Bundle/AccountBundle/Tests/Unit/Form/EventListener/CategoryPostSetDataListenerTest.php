<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\EventListener;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvent;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Form\EventListener\CategoryPostSetDataListener;

class CategoryPostSetDataListenerTest extends AbstractCategoryListenerTestCase
{
    /** @var CategoryPostSetDataListener */
    protected $listener;

    /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $form;

    /** @var Account|\PHPUnit_Framework_MockObject_MockObject */
    protected $account;

    /** @var AccountGroup|\PHPUnit_Framework_MockObject_MockObject */
    protected $accountGroup;

    protected function setUp()
    {
        $this->form = $this->getMock('Symfony\Component\Form\FormInterface');

        parent::setUp();
    }

    /**
     * @return CategoryPostSetDataListener
     */
    public function getListener()
    {
        return new CategoryPostSetDataListener($this->registry);
    }

    public function testOnPostSetData()
    {
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

    /**
     * @return array
     */
    public function onPostSetDataWithoutCategoryDataProvider()
    {
        return [
            ['categoryWithoutId' => new Category()],
            ['anotherClass' => new \stdClass()],
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

    /**
     * @param Category $category
     */
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
                            ['visibility' => AccountCategoryVisibility::VISIBLE],
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
                            ['visibility' => AccountGroupCategoryVisibility::VISIBLE],
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
     * @return CategoryVisibility|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCategoryVisibility()
    {
        $categoryVisibility = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility')
            ->setMethods(['getVisibility'])
            ->getMock();
        $categoryVisibility->expects($this->once())
            ->method('getVisibility')
            ->willReturn(CategoryVisibility::HIDDEN);

        return $categoryVisibility;
    }

    /**
     * @return AccountCategoryVisibility|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAccountCategoryVisibility()
    {
        /** @var Account|\PHPUnit_Framework_MockObject_MockObject $account */
        $this->account = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\Account');
        $this->account->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $visibility = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility')
            ->setMethods(['getVisibility', 'getAccount'])
            ->getMock();
        $visibility->expects($this->exactly(2))
            ->method('getAccount')
            ->willReturn($this->account);
        $visibility->expects($this->once())
            ->method('getVisibility')
            ->willReturn(AccountCategoryVisibility::VISIBLE);

        return $visibility;
    }

    /**
     * @return AccountGroupCategoryVisibility|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAccountGroupCategoryVisibility()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AccountGroup $accountGroup */
        $this->accountGroup = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountGroup');
        $this->accountGroup->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $visibility = $this->getMockBuilder(
            'OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility'
        )
            ->setMethods(['getVisibility', 'getAccountGroup'])
            ->getMock();
        $visibility->expects($this->exactly(2))
            ->method('getAccountGroup')
            ->willReturn($this->accountGroup);
        $visibility->expects($this->once())
            ->method('getVisibility')
            ->willReturn(AccountGroupCategoryVisibility::VISIBLE);

        return $visibility;
    }
}
