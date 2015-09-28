<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\EventListener;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvent;

use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Form\EventListener\CategoryPostSubmitListener;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryPostSubmitListenerTest extends AbstractCategoryListenerTestCase
{
    const CATEGORY_ID = 123;

    /** @var CategoryPostSubmitListener */
    protected $listener;

    /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $form;

    public function setUp()
    {
        $this->form = $this->getMock('Symfony\Component\Form\FormInterface');

        parent::setUp();
    }

    /**
     * @return CategoryPostSubmitListener
     */
    public function getListener()
    {
        return new CategoryPostSubmitListener($this->registry);
    }

    public function testInvalidForm()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())->method('isValid')->willReturn(false);
        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('\Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        $event->expects($this->never())->method('getData');
        $this->listener->onPostSubmit($event);
    }

    public function testOnPostSubmit()
    {
        $event = $this->getEventMock();

        /** @var Category $category */
        $category = $this->getEntity('OroB2B\Bundle\CatalogBundle\Entity\Category', self::CATEGORY_ID);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($category);

        $this->getCategoryVisibility($category);
        $this->getAccountVisibility();
        $this->getAccountGroupVisibility();

        $this->em->expects($this->once())
            ->method('flush');

        $this->listener->onPostSubmit($event);
    }

    public function testOnPostSubmitWithEmptyData()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormEvent $event */
        $event = $this->getMockBuilder('\Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getData')
            ->willReturn(null);
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $event->expects($this->once())
            ->method('getForm')->willReturn($form);
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $this->listener->onPostSubmit($event);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FormEvent
     */
    protected function getEventMock()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $categoryFormMock */
        $categoryFormMock = $this->getCategoryVisibilityFormMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $visibilityForAccountFormMock */
        $visibilityForAccountFormMock = $this
            ->getCommonVisibilityFormMock(
                'OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility',
                'OroB2B\Bundle\AccountBundle\Entity\Account'
            );

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $visibilityForAccountGroupFormMock */
        $visibilityForAccountGroupFormMock = $this
            ->getCommonVisibilityFormMock(
                'OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility',
                'OroB2B\Bundle\AccountBundle\Entity\AccountGroup'
            );

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $form->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap(
                [
                    ['categoryVisibility', $categoryFormMock],
                    ['visibilityForAccount', $visibilityForAccountFormMock],
                    ['visibilityForAccountGroup', $visibilityForAccountGroupFormMock],
                ]
            );
        $event = $this->getMockBuilder('\Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->exactly(2))
            ->method('getForm')
            ->willReturn($form);

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
     * @return string
     */
    protected function getCategoryVisibility(Category $category)
    {
        $visibility = CategoryVisibility::VISIBLE;
        /** @var CategoryVisibility|\PHPUnit_Framework_MockObject_MockObject $categoryVisibility */
        $categoryVisibility = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility')
            ->setMethods(['setVisibility'])
            ->getMock();
        $categoryVisibility->expects($this->once())
            ->method('setVisibility')
            ->with($visibility);

        $this->categoryVisibilityRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['category' => $category])
            ->willReturn($categoryVisibility);

        return $visibility;
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
                    'visibility' => constant($className.'::PARENT_CATEGORY'),
                ],
            ],
            [
                'entity' => $this->getEntity($entityClassName, 2),
                'data' => [
                    'visibility' => constant($className.'::PARENT_CATEGORY'),
                ],
            ],
            [
                'entity' => $this->getEntity($entityClassName, 3),
                'data' => [
                    'visibility' => constant($className.'::VISIBLE'),
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

    /**
     * @return string
     */
    protected function getAccountVisibility()
    {
        $visibility = AccountCategoryVisibility::VISIBLE;

        $accountCategoryVisibilities = new ArrayCollection(
            [
                $this->getAccountCategoryVisibilityMock(
                    AccountCategoryVisibility::PARENT_CATEGORY,
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 1)
                ),
                $this->getAccountCategoryVisibilityMock(
                    AccountCategoryVisibility::PARENT_CATEGORY,
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 2)
                ),
                $this->getAccountCategoryVisibilityMock(
                    AccountCategoryVisibility::VISIBLE,
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 3)
                ),
            ]
        );

        $this->accountCategoryVisibilityRepository->expects($this->once())
            ->method('findForAccounts')
            ->willReturn($accountCategoryVisibilities);

        return $visibility;
    }

    /**
     * @return string
     */
    protected function getAccountGroupVisibility()
    {
        $accountGroupCategoryVisibilities = new ArrayCollection(
            [
                $this->getAccountGroupCategoryVisibilityMock(
                    AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 1)
                ),
                $this->getAccountGroupCategoryVisibilityMock(
                    AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 2)
                ),
                $this->getAccountGroupCategoryVisibilityMock(
                    AccountGroupCategoryVisibility::VISIBLE,
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 3)
                ),
            ]
        );

        $this->accountGroupCategoryVisibilityRepository->expects($this->once())
            ->method('findForAccountGroups')
            ->willReturn($accountGroupCategoryVisibilities);
    }

    public function testProcessCategoryVisibilityWithEmptyCategoryVisibility()
    {
        $category = $this->getEntity('OroB2B\Bundle\CatalogBundle\Entity\Category', self::CATEGORY_ID);
        $visibility = CategoryVisibility::VISIBLE;

        $this->categoryVisibilityRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['category' => $category])
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

        $reflectionClass = new \ReflectionClass($this->listener);
        $method = $reflectionClass->getMethod('processCategoryVisibility');
        $method->setAccessible(true);
        $method->invoke($this->listener, $category, $visibility);
    }

    public function testProcessAccountVisibilityWithEmptyAccountCategoryVisibility()
    {
        $category = $this->getEntity('OroB2B\Bundle\CatalogBundle\Entity\Category', self::CATEGORY_ID);
        $account = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 1);
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
            ->method('findForAccounts')
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

        $reflectionClass = new \ReflectionClass($this->listener);
        $method = $reflectionClass->getMethod('processAccountVisibility');
        $method->setAccessible(true);
        $method->invoke($this->listener, $category, $accountChangeSet);
    }

    public function testProcessAccountGroupVisibilityWithEmptyAccountGroupCategoryVisibility()
    {
        $category = $this->getEntity('OroB2B\Bundle\CatalogBundle\Entity\Category', self::CATEGORY_ID);
        $accountGroup = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 1);
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
            ->method('findForAccountGroups')
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

        $reflectionClass = new \ReflectionClass($this->listener);
        $method = $reflectionClass->getMethod('processAccountGroupVisibility');
        $method->setAccessible(true);
        $method->invoke($this->listener, $category, $accountGroupChangeSet);
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
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility')
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
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility')
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
}
