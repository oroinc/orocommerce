<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\EventListener;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvent;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Form\EventListener\CategoryPostSubmitListener;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryPostSubmitListenerTest extends AbstractCategoryListenerTestCase
{
    const CATEGORY_ID = 123;

    /** @var CategoryPostSubmitListener */
    protected $listener;

    /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $form;

    /** @var EnumValueProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $enumValueProvider;

    public function setUp()
    {
        $this->enumValueProvider = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->form = $this->getMock('Symfony\Component\Form\FormInterface');

        parent::setUp();
    }

    /**
     * @return CategoryPostSubmitListener
     */
    public function getListener()
    {
        return new CategoryPostSubmitListener($this->registry, $this->enumValueProvider);
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

        $this->prepareEnumValueProvider(
            $this->getCategoryVisibility($category),
            $this->getAccountVisibility(),
            $this->getAccountGroupVisibility()
        );

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
                'OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility',
                'OroB2B\Bundle\AccountBundle\Entity\Account'
            );

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $visibilityForAccountGroupFormMock */
        $visibilityForAccountGroupFormMock = $this
            ->getCommonVisibilityFormMock(
                'OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility',
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
        /** @var AbstractEnumValue|\PHPUnit_Framework_MockObject_MockObject $visibility */
        $abstractEnum = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue')
            ->disableOriginalConstructor()
            ->getMock();
        $abstractEnum->expects($this->once())
            ->method('getId')
            ->willReturn(CategoryVisibility::VISIBLE);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($abstractEnum);

        return $form;
    }

    /**
     * @param AbstractEnumValue $categoryVisibility
     * @param AbstractEnumValue $accountCategoryVisibility
     * @param AbstractEnumValue $accountCategoryGroupVisibility
     */
    protected function prepareEnumValueProvider(
        AbstractEnumValue $categoryVisibility,
        AbstractEnumValue $accountCategoryVisibility,
        AbstractEnumValue $accountCategoryGroupVisibility
    ) {
        $this->enumValueProvider->expects($this->any())
            ->method('getEnumValueByCode')
            ->withConsecutive(
                [CategoryPostSubmitListener::CATEGORY_VISIBILITY, CategoryVisibility::VISIBLE],
                [CategoryPostSubmitListener::ACCOUNT_CATEGORY_VISIBILITY, AccountCategoryVisibility::PARENT_CATEGORY],
                [CategoryPostSubmitListener::ACCOUNT_CATEGORY_VISIBILITY, AccountCategoryVisibility::PARENT_CATEGORY],
                [CategoryPostSubmitListener::ACCOUNT_CATEGORY_VISIBILITY, AccountCategoryVisibility::VISIBLE],
                [
                    CategoryPostSubmitListener::ACCOUNT_GROUP_CATEGORY_VISIBILITY,
                    AccountGroupCategoryVisibility::PARENT_CATEGORY,
                ],
                [
                    CategoryPostSubmitListener::ACCOUNT_GROUP_CATEGORY_VISIBILITY,
                    AccountGroupCategoryVisibility::PARENT_CATEGORY,
                ],
                [
                    CategoryPostSubmitListener::ACCOUNT_GROUP_CATEGORY_VISIBILITY,
                    AccountGroupCategoryVisibility::VISIBLE,
                ]
            )
            ->will(
                $this->onConsecutiveCalls(
                    $categoryVisibility,
                    $accountCategoryVisibility,
                    $accountCategoryVisibility,
                    $accountCategoryVisibility,
                    $accountCategoryGroupVisibility,
                    $accountCategoryGroupVisibility,
                    $accountCategoryGroupVisibility
                )
            );
    }

    /**
     * @param Category $category
     *
     * @return AbstractEnumValue|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCategoryVisibility(Category $category)
    {
        /** @var AbstractEnumValue|\PHPUnit_Framework_MockObject_MockObject $visibility */
        $visibility = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var CategoryVisibility|\PHPUnit_Framework_MockObject_MockObject $categoryVisibility */
        $categoryVisibility = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\CategoryVisibility')
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

    /**
     * @return AbstractEnumValue|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAccountVisibility()
    {
        /** @var AbstractEnumValue|\PHPUnit_Framework_MockObject_MockObject $visibility */
        $visibility = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue')
            ->disableOriginalConstructor()
            ->getMock();

        $accountCategoryVisibilities = new ArrayCollection(
            [
                $this->getAccountCategoryVisibilityMock(
                    $visibility,
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 1),
                    AccountCategoryVisibility::PARENT_CATEGORY
                ),
                $this->getAccountCategoryVisibilityMock(
                    $visibility,
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 2),
                    AccountCategoryVisibility::PARENT_CATEGORY
                ),
                $this->getAccountCategoryVisibilityMock(
                    $visibility,
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 3),
                    AccountCategoryVisibility::VISIBLE
                ),
            ]
        );

        $this->accountCategoryVisibilityRepository->expects($this->once())
            ->method('findForAccounts')
            ->willReturn($accountCategoryVisibilities);

        return $visibility;
    }

    /**
     * @return AbstractEnumValue|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAccountGroupVisibility()
    {
        /** @var AbstractEnumValue|\PHPUnit_Framework_MockObject_MockObject $visibility */
        $visibility = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue')
            ->disableOriginalConstructor()
            ->getMock();

        $accountGroupCategoryVisibilities = new ArrayCollection(
            [
                $this->getAccountGroupCategoryVisibilityMock(
                    $visibility,
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 1),
                    null
                ),
                $this->getAccountGroupCategoryVisibilityMock(
                    $visibility,
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 2),
                    AccountCategoryVisibility::VISIBLE
                ),
                $this->getAccountGroupCategoryVisibilityMock(
                    $visibility,
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 3),
                    AccountCategoryVisibility::HIDDEN
                ),
            ]
        );

        $this->accountGroupCategoryVisibilityRepository->expects($this->once())
            ->method('findForAccountGroups')
            ->willReturn($accountGroupCategoryVisibilities);

        return $visibility;
    }

    public function testProcessCategoryVisibilityWithEmptyCategoryVisibility()
    {
        $category = $this->getEntity('OroB2B\Bundle\CatalogBundle\Entity\Category', self::CATEGORY_ID);
        /** @var AbstractEnumValue|\PHPUnit_Framework_MockObject_MockObject $visibility */
        $visibility = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue')
            ->disableOriginalConstructor()
            ->getMock();

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
     * @param AbstractEnumValue|\PHPUnit_Framework_MockObject_MockObject $visibility $visibility
     * @param Account|\PHPUnit_Framework_MockObject_MockObject|object $account $account
     * @param string $visibilityValue
     *
     * @return AccountCategoryVisibility|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAccountCategoryVisibilityMock($visibility, $account, $visibilityValue)
    {
        $visibilityEntity = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility')
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
            ->willReturn($visibilityValue);

        return $visibilityEntity;
    }

    /**
     * @param AbstractEnumValue|\PHPUnit_Framework_MockObject_MockObject $visibility $visibility
     * @param AccountGroup|\PHPUnit_Framework_MockObject_MockObject|object $account $account
     * @param string $visibilityValue
     *
     * @return AccountCategoryVisibility|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAccountGroupCategoryVisibilityMock($visibility, $account, $visibilityValue)
    {
        $visibilityEntity = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility')
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
            ->willReturn($visibilityValue);

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
