<?php

namespace OroB2B\src\OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\EventListener\CategoryEditSubscriber;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Event\CategoryEditEvent;

class CategoryEditSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var  CategoryEditSubscriber */
    protected $categoryEditSubscriber;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject doctrineHelper */
    protected $doctrineHelper;

    /** @var EnumValueProvider|\PHPUnit_Framework_MockObject_MockObject enumValueProvider */
    protected $enumValueProvider;

    public function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->enumValueProvider = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider')
            ->setConstructorArgs([$this->doctrineHelper])
            ->getMock();

        $this->categoryEditSubscriber = new CategoryEditSubscriber($this->doctrineHelper, $this->enumValueProvider);
    }

    public function testGetSubscribedEvents()
    {
        $subscribedEvents = CategoryEditSubscriber::getSubscribedEvents();
        $expectedEvents = [CategoryEditEvent::NAME];

        $this->assertEquals(array_keys($subscribedEvents), $expectedEvents);
    }

    public function testOnCategoryEdit()
    {
        $event = $this->getEventMock();

        $category = new Category();
        $event->expects($this->once())
            ->method('getCategory')
            ->willReturn($category);

        $this->prepareProcessCategoryVisibility($category);
        $this->prepareProcessAccountVisibility();
        $this->prepareProcessAccountGroupVisibility();

        $this->categoryEditSubscriber->onCategoryEdit($event);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CategoryEditEvent
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
            ->willReturnMap([
                ['categoryVisibility', $categoryFormMock],
                ['visibilityForAccount', $visibilityForAccountFormMock],
                ['visibilityForAccountGroup', $visibilityForAccountGroupFormMock],
            ]);

        $event = $this->getMock('OroB2B\Bundle\CatalogBundle\Event\CategoryEditEvent');
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
        $categoryVisibility  = $this
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

        $em = $this->getEntityManagerMock($categoryVisibility, false);

        $this->doctrineHelper->expects($this->at(1))
            ->method('getEntityManager')
            ->with($categoryVisibility)
            ->willReturn($em);

        $this->doctrineHelper->expects($this->at(0))
            ->method('getEntityRepository')
            ->with('OroB2BAccountBundle:CategoryVisibility')
            ->willReturn($repo);

        $this->enumValueProvider->expects($this->at(0))
            ->method('getEnumValueByCode')
            ->with(CategoryEditSubscriber::CATEGORY_VISIBILITY, CategoryVisibility::VISIBLE)
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
                    'visibility' => constant($className . '::PARENT_CATEGORY')
                ]
            ],
            [
                'entity' => $this->getEntity($entityClassName, 2),
                'data' => [
                    'visibility' => constant($className . '::PARENT_CATEGORY')
                ]
            ],
            [
                'entity' => $this->getEntity($entityClassName, 3),
                'data' => [
                    'visibility' => constant($className . '::VISIBLE')
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

        $accountCategoryVisibilities = new ArrayCollection([
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
        ]);

        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\Repository\AccountCategoryVisibilityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('findForAccounts')
            ->willReturn($accountCategoryVisibilities);

        $this->doctrineHelper->expects($this->at(2))
            ->method('getEntityRepository')
            ->with('OroB2BAccountBundle:AccountCategoryVisibility')
            ->willReturn($repo);

        $em = $this->getEntityManagerMock($accountCategoryVisibilities->offsetGet(0));
        $this->doctrineHelper->expects($this->at(3))
            ->method('getEntityManager')
            ->with($accountCategoryVisibilities->offsetGet(0))
            ->willReturn($em);

        $this->enumValueProvider->expects($this->at(1))
            ->method('getEnumValueByCode')
            ->with(CategoryEditSubscriber::ACCOUNT_CATEGORY_VISIBILITY, AccountCategoryVisibility::VISIBLE)
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

        $accountGroupCategoryVisibilities = new ArrayCollection([
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
        ]);

        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupCategoryVisibilityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('findForAccountGroups')
            ->willReturn($accountGroupCategoryVisibilities);

        $this->doctrineHelper->expects($this->at(4))
            ->method('getEntityRepository')
            ->with('OroB2BAccountBundle:AccountGroupCategoryVisibility')
            ->willReturn($repo);

        $em = $this->getEntityManagerMock($accountGroupCategoryVisibilities->offsetGet(0));
        $this->doctrineHelper->expects($this->at(5))
            ->method('getEntityManager')
            ->with($accountGroupCategoryVisibilities->offsetGet(0))
            ->willReturn($em);

        $this->enumValueProvider->expects($this->at(2))
            ->method('getEnumValueByCode')
            ->with(CategoryEditSubscriber::ACCOUNT_GROUP_CATEGORY_VISIBILITY, AccountGroupCategoryVisibility::VISIBLE)
            ->willReturn($visibility);
    }

    /**
     * @param AbstractEnumValue|\PHPUnit_Framework_MockObject_MockObject $visibility $visibility
     * @param Account|\PHPUnit_Framework_MockObject_MockObject|object $account$account
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
     * @param AccountGroup|\PHPUnit_Framework_MockObject_MockObject|object $account$account
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
     * @param object $visibilityEntity
     * @param bool $expectRemoval
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    protected function getEntityManagerMock($visibilityEntity, $expectRemoval = true)
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())
            ->method('persist')
            ->with($visibilityEntity);

        $em->expects($this->once())
            ->method('flush');

        if ($expectRemoval) {
            $em->expects($this->once())
                ->method('remove')
                ->with($visibilityEntity);
        }

        return $em;
    }

    /**
     * @param string $className
     * @param int    $id
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
