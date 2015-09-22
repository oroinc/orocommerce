<?php

namespace OroB2B\src\OroB2B\Bundle\AccountBundle\Tests\Unit\Form\EventListener;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvent;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Util\ClassUtils;
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

class CategoryPostSubmitListenerTest extends \PHPUnit_Framework_TestCase
{
    const CATEGORY_ID = 123;

    /** @var  CategoryPostSubmitListener */
    protected $listener;

    /** @var \Doctrine\Common\Persistence\ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject doctrineHelper */
    protected $registry;

    /** @var \Doctrine\ORM\EntityManager|\PHPUnit_Framework_MockObject_MockObject doctrineHelper */
    protected $em;

    /** @var EnumValueProvider|\PHPUnit_Framework_MockObject_MockObject enumValueProvider */
    protected $enumValueProvider;

    public function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);
        $this->enumValueProvider = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->listener = new CategoryPostSubmitListener($this->registry, $this->enumValueProvider);

        $this->listener
            ->setCategoryVisibilityClass('OroB2B\Bundle\AccountBundle\Entity\CategoryVisibility')
            ->setAccountCategoryVisibilityClass('OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility')
            ->setAccountGroupCategoryVisibilityClass(
                'OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility'
            );
    }

    public function testOnPostSubmit()
    {
        $event = $this->getEventMock();

        $category = $this->getEntity('OroB2B\Bundle\CatalogBundle\Entity\Category', self::CATEGORY_ID);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($category);

        $this->prepareProcessCategoryVisibility($category);
        $this->prepareProcessAccountVisibility();
        $this->prepareProcessAccountGroupVisibility();

        $this->em->expects($this->once())
            ->method('flush');

        $this->listener->onPostSubmit($event);
    }

    public function testOnPostSubmitWithEmptyData()
    {
        $event = $this->getMockBuilder('\Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getData')
            ->willReturn(null);
        $this->listener->onPostSubmit($event);
        $event->expects($this->never())
            ->method('getForm');
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
        $event->expects($this->once())
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
     * @param Category $category
     */
    protected function prepareProcessCategoryVisibility(Category $category)
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

        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this
            ->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['category' => $category])
            ->willReturn($categoryVisibility);

        $this->registry->expects($this->at(0))
            ->method('getRepository')
            ->with('OroB2BAccountBundle:CategoryVisibility')
            ->willReturn($repo);

        $em = $this->getEntityManagerMock($categoryVisibility, false);

        $this->registry->expects($this->at(1))
            ->method('getManagerForClass')
            ->with('OroB2B\Bundle\CatalogBundle\Entity\Category')
            ->willReturn($em);

        $this->enumValueProvider->expects($this->at(0))
            ->method('getEnumValueByCode')
            ->with(CategoryPostSubmitListener::CATEGORY_VISIBILITY, CategoryVisibility::VISIBLE)
            ->willReturn($visibility);
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
                    'visibility' => constant($className.'::PARENT_CATEGORY')
                ]
            ],
            [
                'entity' => $this->getEntity($entityClassName, 2),
                'data' => [
                    'visibility' => constant($className.'::PARENT_CATEGORY')
                ]
            ],
            [
                'entity' => $this->getEntity($entityClassName, 3),
                'data' => [
                    'visibility' => constant($className.'::VISIBLE')
                ]
            ]
        ];

        $changeSet = new ArrayCollection($data);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($changeSet);

        return $form;
    }

    /**
     * Method prepareProcessAccountVisibility
     */
    protected function prepareProcessAccountVisibility()
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
                    null
                ),
                $this->getAccountCategoryVisibilityMock(
                    $visibility,
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 2),
                    AccountCategoryVisibility::VISIBLE
                ),
                $this->getAccountCategoryVisibilityMock(
                    $visibility,
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 3),
                    AccountCategoryVisibility::HIDDEN
                )
            ]
        );

        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\Repository\AccountCategoryVisibilityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('findForAccounts')
            ->willReturn($accountCategoryVisibilities);

        $this->registry->expects($this->at(2))
            ->method('getRepository')
            ->with('OroB2BAccountBundle:AccountCategoryVisibility')
            ->willReturn($repo);

        $this->enumValueProvider->expects($this->at(1))
            ->method('getEnumValueByCode')
            ->with(CategoryPostSubmitListener::ACCOUNT_CATEGORY_VISIBILITY, AccountCategoryVisibility::PARENT_CATEGORY)
            ->willReturn($visibility);
    }

    /**
     * Method prepareProcessAccountGroupVisibility
     */
    protected function prepareProcessAccountGroupVisibility()
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
                )
            ]
        );

        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupCategoryVisibilityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('findForAccountGroups')
            ->willReturn($accountGroupCategoryVisibilities);

        $this->registry->expects($this->at(6))
            ->method('getRepository')
            ->with('OroB2BAccountBundle:AccountGroupCategoryVisibility')
            ->willReturn($repo);

        $this->enumValueProvider->expects($this->at(2))
            ->method('getEnumValueByCode')
            ->with(
                CategoryPostSubmitListener::ACCOUNT_GROUP_CATEGORY_VISIBILITY,
                AccountGroupCategoryVisibility::VISIBLE
            )
            ->willReturn($visibility);
    }

    public function testProcessCategoryVisibilityWithEmptyCategoryVisibility()
    {
        $category = $this->getEntity('OroB2B\Bundle\CatalogBundle\Entity\Category', self::CATEGORY_ID);
        /** @var AbstractEnumValue|\PHPUnit_Framework_MockObject_MockObject $visibility */
        $visibility = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this
            ->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['category' => $category])
            ->willReturn(null);
        $this->registry->expects($this->at(0))
            ->method('getRepository')
            ->with('OroB2BAccountBundle:CategoryVisibility')
            ->willReturn($repo);

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
                    'visibility' => AccountCategoryVisibility::VISIBLE
                ]
            ]
        ];
        $accountChangeSet = new ArrayCollection($data);

        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this
            ->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->setMethods(['findForAccounts'])
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->at(0))
            ->method('findForAccounts')
            ->willReturn(new ArrayCollection([]));
        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BAccountBundle:AccountCategoryVisibility')
            ->willReturn($repo);

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
                    'visibility' => AccountGroupCategoryVisibility::HIDDEN
                ]
            ]
        ];
        $accountGroupChangeSet = new ArrayCollection($data);

        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this
            ->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->setMethods(['findForAccountGroups'])
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->at(0))
            ->method('findForAccountGroups')
            ->willReturn(new ArrayCollection([]));
        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BAccountBundle:AccountGroupCategoryVisibility')
            ->willReturn($repo);

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
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    protected function getEntityManagerMock()
    {
        return $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
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
