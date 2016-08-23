<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\AccountBundle\Form\EventListener\VisibilityPostSetDataListener;

class PostSetDataVisibilityListenerTest extends AbstractVisibilityListenerTestCase
{
    /** @var VisibilityPostSetDataListener */
    protected $listener;

    /** @var Account|\PHPUnit_Framework_MockObject_MockObject */
    protected $account;

    /** @var AccountGroup|\PHPUnit_Framework_MockObject_MockObject */
    protected $accountGroup;

    /**
     * @return VisibilityPostSetDataListener
     */
    public function getListener()
    {
        return new VisibilityPostSetDataListener($this->registry);
    }

    public function testOnPostSetData()
    {
        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')->disableOriginalConstructor()->getMock();
        /** @var Category $category */
        $category = $this->getEntity('Oro\Bundle\CatalogBundle\Entity\Category', 1);
        $this->form->expects($this->atLeast(1))->method('getData')->willReturn($category);
        $event->expects($this->any())->method('getForm')->willReturn($this->form);

        $allForm = $this->setCategoryVisibilityExpectations(
            $category,
            $this->getCategoryVisibility(),
            CategoryVisibility::HIDDEN
        );
        $accountForm = $this->setAccountCategoryVisibilityExpectations($category);
        $accountGroupForm = $this->setAccountGroupCategoryVisibilityExpectations($category);

        $this->form->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap(
                [
                    ['all', $allForm],
                    ['account', $accountForm],
                    ['accountGroup', $accountGroupForm],
                ]
            );

        $this->listener->onPostSetData($event);
    }

    /**
     * @param Category $category
     * @param string $visibility
     * @dataProvider onPostSetDataWithDefaultCategoryVisibilityDataProvider
     */
    public function testOnPostSetDataWithDefaultCategoryVisibility(Category $category, $visibility)
    {
        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')->disableOriginalConstructor()->getMock();
        $this->form->expects($this->atLeast(1))->method('getData')->willReturn($category);
        $event->expects($this->any())->method('getForm')->willReturn($this->form);

        $allForm = $this->setCategoryVisibilityExpectations($category, null, $visibility);
        $accountForm = $this->setAccountCategoryVisibilityExpectations($category);
        $accountGroupForm = $this->setAccountGroupCategoryVisibilityExpectations($category);

        $this->form->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap(
                [
                    ['all', $allForm],
                    ['account', $accountForm],
                    ['accountGroup', $accountGroupForm],
                ]
            );

        $this->listener->onPostSetData($event);
    }

    /**
     * @return array
     */
    public function onPostSetDataWithDefaultCategoryVisibilityDataProvider()
    {
        /** @var Category $rootCategory */
        $rootCategory = $this->getEntity('Oro\Bundle\CatalogBundle\Entity\Category', 1);
        $subCategory = clone($rootCategory);
        $subCategory->setParentCategory($rootCategory);

        return [
            'root' => [
                'category' => $rootCategory,
                'visibility' => CategoryVisibility::CONFIG,
            ],
            'sub' => [
                'category' => $subCategory,
                'visibility' => CategoryVisibility::PARENT_CATEGORY,
            ],
        ];
    }

    /**
     * @dataProvider onPostSetDataWithoutCategoryDataProvider
     * @param $category
     */
    public function testOnPostSetDataWithoutCategory($category)
    {
        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')->disableOriginalConstructor()->getMock();
        $this->form->expects($this->atLeast(1))->method('getData')->willReturn($category);
        $event->expects($this->any())->method('getForm')->willReturn($this->form);
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
            ['null' => null],
            ['false' => false],
            ['string' => 'Category'],
            ['integer' => 1],
        ];
    }

    /**
     * @param Category $category
     * @param CategoryVisibility $categoryVisibility
     * @param string $expectedVisibility
     * @return FormInterface
     */
    protected function setCategoryVisibilityExpectations(
        Category $category,
        CategoryVisibility $categoryVisibility = null,
        $expectedVisibility = CategoryVisibility::PARENT_CATEGORY
    ) {
        $criteria = $this->addWebsiteCriteria(['category' => $category]);
        $this->categoryVisibilityRepository->expects($this->once())
            ->method('findOneBy')
            ->with($criteria)
            ->willReturn($categoryVisibility);

        /** @var  FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())->method('setData')->with($expectedVisibility);
        return $form;
    }

    /**
     * @param Category $category
     * @return FormInterface
     */
    protected function setAccountCategoryVisibilityExpectations(Category $category)
    {
        $accountCategoryVisibility = $this->getAccountCategoryVisibility();
        $criteria = $this->addWebsiteCriteria(['category' => $category]);
        $this->accountCategoryVisibilityRepository->expects($this->once())
            ->method('findBy')
            ->with($criteria)
            ->willReturn([$accountCategoryVisibility]);
        /** @var  FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
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
        return $form;
    }

    /**
     * @param Category $category
     * @return FormInterface
     */
    protected function setAccountGroupCategoryVisibilityExpectations(Category $category)
    {
        $accountGroupCategoryVisibility = $this->getAccountGroupCategoryVisibility();
        $criteria = $this->addWebsiteCriteria(['category' => $category]);
        $this->accountGroupCategoryVisibilityRepository->expects($this->once())
            ->method('findBy')
            ->with($criteria)
            ->willReturn([$accountGroupCategoryVisibility]);
        /** @var  FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
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
        return $form;
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
        $categoryVisibility = $this->getMockBuilder('Oro\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility')
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
        $this->account = $this->getMock('Oro\Bundle\AccountBundle\Entity\Account');
        $this->account->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $visibility = $this->getMockBuilder('Oro\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility')
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
        $this->accountGroup = $this->getMock('Oro\Bundle\AccountBundle\Entity\AccountGroup');
        $this->accountGroup->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $visibility = $this->getMockBuilder(
            'Oro\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility'
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
