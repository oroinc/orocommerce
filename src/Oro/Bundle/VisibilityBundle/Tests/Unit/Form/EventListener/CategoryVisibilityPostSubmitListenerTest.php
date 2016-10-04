<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\EventListener;

use Symfony\Component\Form\FormInterface;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\VisibilityBundle\Form\Type\EntityVisibilityType;
use Oro\Bundle\VisibilityBundle\Form\EventListener\CategoryVisibilityPostSubmitListener;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\CatalogBundle\Entity\Category;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CategoryPostSubmitVisibilityListenerTest extends AbstractVisibilityListenerTestCase
{
    const CATEGORY_ID = 123;

    /** @var CategoryVisibilityPostSubmitListener */
    protected $listener;

    /**
     * @return CategoryVisibilityPostSubmitListener
     */
    public function getListener()
    {
        $listener = new CategoryVisibilityPostSubmitListener($this->registry);
        $listener->setVisibilityField(EntityVisibilityType::VISIBILITY);

        return $listener;
    }

    public function testInvalidForm()
    {
        $this->form->expects($this->once())->method('isValid')->willReturn(false);
        $event = $this->getFormAwareEventMock();
        $this->form->expects($this->once())->method('getData');
        $this->listener->onPostSubmit($event);
    }

    public function testOnPostSubmit()
    {
        $event = $this->getEventMock();

        /** @var Category $category */
        $category = $this->getEntity('Oro\Bundle\CatalogBundle\Entity\Category', self::CATEGORY_ID);
        $this->form->expects($this->atLeast(1))->method('getData')->willReturn($category);
        $event->expects($this->any())->method('getForm')->willReturn($this->form);

        $this->prepareCategoryVisibilityRepository($category);
        $this->prepareAccountVisibilityRepository();
        $this->prepareAccountGroupVisibilityRepository();

        $this->em->expects($this->once())
            ->method('flush');

        $this->listener->onPostSubmit($event);
    }

    /**
     * @param Category $category
     * @param string $visibility
     * @dataProvider onPostSubmitRemoveDefaultDataProvider
     */
    public function testOnPostSubmitRemoveDefault(Category $category, $visibility)
    {
        $this->accountCategoryVisibilityRepository->expects($this->any())->method('findBy')->willReturn([]);
        $this->accountGroupCategoryVisibilityRepository->expects($this->any())->method('findBy')->willReturn([]);

        $this->form->expects($this->once())->method('isValid')->willReturn(true);
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())->method('getData')->willReturnOnConsecutiveCalls(
            $visibility,
            new ArrayCollection(),
            new ArrayCollection()
        );
        $this->form->expects($this->exactly(3))->method('get')->willReturn($form);

        $event = $this->getFormAwareEventMock();

        $this->form->expects($this->atLeast(1))
            ->method('getData')
            ->willReturn($category);

        $this->prepareCategoryVisibilityRepository($category, $visibility);

        $this->em->expects($this->once())->method('remove');
        $this->em->expects($this->once())->method('flush');

        $this->listener->onPostSubmit($event);
    }

    /**
     * @return array
     */
    public function onPostSubmitRemoveDefaultDataProvider()
    {
        /** @var Category $rootCategory */
        $rootCategory = $this->getEntity('Oro\Bundle\CatalogBundle\Entity\Category', self::CATEGORY_ID);
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

    public function testOnPostSubmitWithEmptyData()
    {
        $this->form->expects($this->once())->method('isValid')->willReturn(true);
        $event = $this->getFormAwareEventMock();
        $this->form->expects($this->once())
            ->method('getData')
            ->willReturn(null);
        $this->listener->onPostSubmit($event);
    }

    /**
     * @return AfterFormProcessEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEventMock()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $categoryFormMock */
        $categoryFormMock = $this->getCategoryVisibilityFormMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $visibilityForAccountFormMock */
        $visibilityForAccountFormMock = $this
            ->getCommonVisibilityFormMock(
                'Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility',
                'Oro\Bundle\AccountBundle\Entity\Account'
            );

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $visibilityForAccountGroupFormMock */
        $visibilityForAccountGroupFormMock = $this
            ->getCommonVisibilityFormMock(
                'Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility',
                'Oro\Bundle\AccountBundle\Entity\AccountGroup'
            );

        $this->form->expects($this->once())->method('isValid')->willReturn(true);
        $this->form->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap(
                [
                    ['all', $categoryFormMock],
                    ['account', $visibilityForAccountFormMock],
                    ['accountGroup', $visibilityForAccountGroupFormMock],
                ]
            );
        $event = $this->getFormAwareEventMock();

        return $event;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected function getCategoryVisibilityFormMock()
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('getData')
            ->willReturn(CategoryVisibility::VISIBLE);

        return $form;
    }

    /**
     * @param Category $category
     *
     * @param string $visibility
     * @return string
     */
    protected function prepareCategoryVisibilityRepository(
        Category $category,
        $visibility = CategoryVisibility::VISIBLE
    ) {
        /** @var CategoryVisibility|\PHPUnit_Framework_MockObject_MockObject $categoryVisibility */
        $categoryVisibility = $this
            ->getMockBuilder('Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility')
            ->setMethods(['setVisibility', 'getVisibility'])
            ->getMock();
        $categoryVisibility->expects($this->any())->method('setVisibility')->with($visibility);
        $categoryVisibility->expects($this->any())->method('getVisibility')->willReturn($visibility);
        $criteria = $this->addWebsiteCriteria(['category' => $category]);
        $this->categoryVisibilityRepository->expects($this->once())
            ->method('findOneBy')
            ->with($criteria)
            ->willReturn($categoryVisibility);
    }

    /**
     * @param string $className fully qualified visibility entity class name
     * @param string $entityClassName fully qualified subject entity class name
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected function getCommonVisibilityFormMock($className, $entityClassName)
    {
        $data = [
            [
                'entity' => $this->getEntity($entityClassName, 1),
                'data' => [
                    'visibility' => constant($className . '::PARENT_CATEGORY'),
                ],
            ],
            [
                'entity' => $this->getEntity($entityClassName, 2),
                'data' => [
                    'visibility' => constant($className . '::PARENT_CATEGORY'),
                ],
            ],
            [
                'entity' => $this->getEntity($entityClassName, 3),
                'data' => [
                    'visibility' => constant($className . '::VISIBLE'),
                ],
            ],
        ];

        $changeSet = new ArrayCollection($data);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($changeSet);

        return $form;
    }

    protected function prepareAccountVisibilityRepository()
    {
        $accountCategoryVisibilities = new ArrayCollection(
            [
                $this->getAccountCategoryVisibilityMock(
                    AccountCategoryVisibility::PARENT_CATEGORY,
                    $this->getEntity('Oro\Bundle\AccountBundle\Entity\Account', 1)
                ),
                $this->getAccountCategoryVisibilityMock(
                    AccountCategoryVisibility::PARENT_CATEGORY,
                    $this->getEntity('Oro\Bundle\AccountBundle\Entity\Account', 2)
                ),
                $this->getAccountCategoryVisibilityMock(
                    AccountCategoryVisibility::VISIBLE,
                    $this->getEntity('Oro\Bundle\AccountBundle\Entity\Account', 3)
                ),
            ]
        );

        $this->accountCategoryVisibilityRepository->expects($this->once())
            ->method('findBy')
            ->willReturn($accountCategoryVisibilities);
    }

    protected function prepareAccountGroupVisibilityRepository()
    {
        $accountGroupCategoryVisibilities = new ArrayCollection(
            [
                $this->getAccountGroupCategoryVisibilityMock(
                    AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    $this->getEntity('Oro\Bundle\AccountBundle\Entity\AccountGroup', 1)
                ),
                $this->getAccountGroupCategoryVisibilityMock(
                    AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    $this->getEntity('Oro\Bundle\AccountBundle\Entity\AccountGroup', 2)
                ),
                $this->getAccountGroupCategoryVisibilityMock(
                    AccountGroupCategoryVisibility::VISIBLE,
                    $this->getEntity('Oro\Bundle\AccountBundle\Entity\AccountGroup', 3)
                ),
            ]
        );

        $this->accountGroupCategoryVisibilityRepository->expects($this->once())
            ->method('findBy')
            ->willReturn($accountGroupCategoryVisibilities);
    }

    public function testSaveFormAllDataWithEmptyCategoryVisibility()
    {
        $category = $this->getEntity('Oro\Bundle\CatalogBundle\Entity\Category', self::CATEGORY_ID);
        $visibility = CategoryVisibility::VISIBLE;
        $criteria = $this->addWebsiteCriteria(['category' => $category]);
        $this->categoryVisibilityRepository->expects($this->once())
            ->method('findOneBy')
            ->with($criteria)
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('persist')
            ->with(
                $this->callback(
                    function ($arg) use ($category) {
                        return $arg instanceof CategoryVisibility
                        && $category === $arg->getCategory();
                    }
                )
            );

        $this->form->expects($this->atLeast(1))
            ->method('getData')
            ->willReturn($category);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())->method('getData')->willReturn($visibility);
        $this->form->expects($this->once())->method('get')->willReturn($form);

        $reflectionClass = new \ReflectionClass($this->listener);
        $method = $reflectionClass->getMethod('saveFormAllData');
        $method->setAccessible(true);
        $method->invoke($this->listener, $this->form);
    }

    public function testProcessAccountVisibilityWithEmptyAccountCategoryVisibility()
    {
        $this->markTestSkipped('Should be fixed after BB-4710');
        $category = $this->getEntity('Oro\Bundle\CatalogBundle\Entity\Category', self::CATEGORY_ID);
        $account = $this->getEntity('Oro\Bundle\AccountBundle\Entity\Account', 1);
        $data = [
            [
                'entity' => $account,
                'data' => [
                    'visibility' => AccountCategoryVisibility::VISIBLE,
                ],
            ],
        ];
        $accountChangeSet = new ArrayCollection($data);

        $this->accountCategoryVisibilityRepository->expects($this->atLeastOnce())
            ->method('findBy')
            ->willReturn(new ArrayCollection([]));

        $this->em->expects($this->once())
            ->method('persist')
            ->with(
                $this->callback(
                    function ($arg) use ($category, $account) {
                        return $arg instanceof AccountCategoryVisibility
                        && $category === $arg->getCategory()
                        && $account === $arg->getAccount();
                    }
                )
            );

        $this->form->expects($this->atLeast(1))
            ->method('getData')
            ->willReturn($category);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())->method('getData')->willReturn($accountChangeSet);
        $this->form->expects($this->once())->method('get')->willReturn($form);

        $reflectionClass = new \ReflectionClass($this->listener);
        $method = $reflectionClass->getMethod('saveFormAccountData');
        $method->setAccessible(true);
        $method->invoke($this->listener, $this->form);
    }

    public function testProcessAccountGroupVisibilityWithEmptyAccountGroupCategoryVisibility()
    {
        $this->markTestSkipped('Should be fixed after BB-4710');
        $category = $this->getEntity('Oro\Bundle\CatalogBundle\Entity\Category', self::CATEGORY_ID);
        $accountGroup = $this->getEntity('Oro\Bundle\AccountBundle\Entity\AccountGroup', 1);
        $data = [
            [
                'entity' => $accountGroup,
                'data' => [
                    'visibility' => AccountGroupCategoryVisibility::HIDDEN,
                ],
            ],
        ];
        $accountGroupChangeSet = new ArrayCollection($data);

        $this->accountGroupCategoryVisibilityRepository->expects($this->atLeastOnce())
            ->method('findBy')
            ->willReturn(new ArrayCollection([]));

        $this->em->expects($this->once())
            ->method('persist')
            ->with(
                $this->callback(
                    function ($arg) use ($category, $accountGroup) {
                        return $arg instanceof AccountGroupCategoryVisibility
                        && $category === $arg->getCategory()
                        && $accountGroup === $arg->getAccountGroup();
                    }
                )
            );

        $this->form->expects($this->atLeast(1))
            ->method('getData')
            ->willReturn($category);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())->method('getData')->willReturn($accountGroupChangeSet);
        $this->form->expects($this->once())->method('get')->willReturn($form);

        $reflectionClass = new \ReflectionClass($this->listener);
        $method = $reflectionClass->getMethod('saveFormAccountGroupData');
        $method->setAccessible(true);
        $method->invoke($this->listener, $this->form);
    }

    /**
     * @param string $visibility
     * @param Account|\PHPUnit_Framework_MockObject_MockObject|object $account
     *
     * @return AccountCategoryVisibility|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAccountCategoryVisibilityMock($visibility, $account)
    {
        $visibilityEntity = $this
            ->getMockBuilder('Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility')
            ->setMethods(['getAccount', 'setVisibility', 'getVisibility'])
            ->getMock();

        $visibilityEntity->expects($this->any())
            ->method('setVisibility')
            ->with($visibility);
        $visibilityEntity->expects($this->any())
            ->method('getAccount')
            ->willReturn($account);
        $visibilityEntity->expects($this->any())
            ->method('getVisibility')
            ->willReturn($visibility);

        return $visibilityEntity;
    }

    /**
     * @param string $visibility
     * @param AccountGroup|\PHPUnit_Framework_MockObject_MockObject|object $account
     *
     * @return AccountCategoryVisibility|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAccountGroupCategoryVisibilityMock($visibility, $account)
    {
        $visibilityEntity = $this
            ->getMockBuilder('Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility')
            ->setMethods(['getAccountGroup', 'setVisibility', 'getVisibility'])
            ->getMock();

        $visibilityEntity->expects($this->any())
            ->method('setVisibility')
            ->with($visibility);
        $visibilityEntity->expects($this->any())
            ->method('getAccountGroup')
            ->willReturn($account);
        $visibilityEntity->expects($this->any())
            ->method('getVisibility')
            ->willReturn($visibility);

        return $visibilityEntity;
    }

    /**
     * @param string $className
     * @param int $id
     *
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

    /**
     * @return AfterFormProcessEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFormAwareEventMock()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())->method('has')->with(EntityVisibilityType::VISIBILITY)->willReturn(true);
        $form->expects($this->once())
            ->method('get')
            ->with(EntityVisibilityType::VISIBILITY)
            ->willReturn($this->form);
        /** @var AfterFormProcessEvent |\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())->method('getForm')->willReturn($form);

        return $event;
    }
}
