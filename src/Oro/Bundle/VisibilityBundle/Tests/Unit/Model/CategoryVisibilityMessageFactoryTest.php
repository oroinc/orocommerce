<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ScopeBundle\Entity\Repository\ScopeRepository;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\AccountGroupCategoryVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\CategoryVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Model\CategoryVisibilityMessageFactory;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CategoryVisibilityMessageFactoryTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var CategoryVisibilityMessageFactory
     */
    protected $categoryVisibilityMessageFactory;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->categoryVisibilityMessageFactory = new CategoryVisibilityMessageFactory($this->registry);
    }

    public function testCreateMessageForCategoryVisibility()
    {
        $categoryId = 123;
        $categoryVisibilityId = 42;
        $scopeId = 1;

        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => $categoryId]);

        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => $scopeId]);

        /** @var CategoryVisibility $categoryVisibility */
        $categoryVisibility = $this->getEntity(CategoryVisibility::class, ['id' => $categoryVisibilityId]);
        $categoryVisibility->setCategory($category);
        $categoryVisibility->setScope($scope);

        $this->categoryVisibilityMessageFactory->createMessage($categoryVisibility);

        $expected = [
            CategoryVisibilityMessageFactory::ID => $categoryVisibilityId,
            CategoryVisibilityMessageFactory::ENTITY_CLASS_NAME => CategoryVisibility::class,
            CategoryVisibilityMessageFactory::CATEGORY_ID => $categoryId,
            CategoryVisibilityMessageFactory::SCOPE_ID => $scopeId
        ];

        $this->assertEquals($expected, $this->categoryVisibilityMessageFactory->createMessage($categoryVisibility));
    }

    public function testCreateMessageForAccountGroupCategoryVisibility()
    {
        $scopeId = 1;
        $categoryId = 123;
        $categoryVisibilityId = 42;

        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => $categoryId]);

        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => $scopeId]);

        /** @var AccountGroupCategoryVisibility $accountGroupCategoryVisibility */
        $accountGroupCategoryVisibility = $this->getEntity(
            AccountGroupCategoryVisibility::class,
            ['id' => $categoryVisibilityId]
        );
        $accountGroupCategoryVisibility->setCategory($category);
        $accountGroupCategoryVisibility->setScope($scope);

        $expected = [
            CategoryVisibilityMessageFactory::ID => $categoryVisibilityId,
            CategoryVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountGroupCategoryVisibility::class,
            CategoryVisibilityMessageFactory::CATEGORY_ID => $categoryId,
            CategoryVisibilityMessageFactory::SCOPE_ID => $scopeId
        ];

        $this->assertEquals(
            $expected,
            $this->categoryVisibilityMessageFactory->createMessage($accountGroupCategoryVisibility)
        );
    }

    public function testCreateMessageForAccountCategoryVisibility()
    {
        $scopeId = 5;
        $categoryId = 123;
        $categoryVisibilityId = 42;

        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => $categoryId]);

        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => $scopeId]);

        /** @var AccountCategoryVisibility $accountCategoryVisibility */
        $accountCategoryVisibility = $this->getEntity(
            AccountCategoryVisibility::class,
            ['id' => $categoryVisibilityId]
        );
        $accountCategoryVisibility->setCategory($category);
        $accountCategoryVisibility->setScope($scope);

        $this->categoryVisibilityMessageFactory->createMessage($accountCategoryVisibility);

        $expected = [
            CategoryVisibilityMessageFactory::ID => $categoryVisibilityId,
            CategoryVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountCategoryVisibility::class,
            CategoryVisibilityMessageFactory::CATEGORY_ID => $categoryId,
            CategoryVisibilityMessageFactory::SCOPE_ID => $scopeId
        ];

        $this->assertEquals(
            $expected,
            $this->categoryVisibilityMessageFactory->createMessage($accountCategoryVisibility)
        );
    }

    /**
     * @expectedException \Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Unsupported entity class
     */
    public function testCreateMessageUnsupportedClass()
    {
        $this->categoryVisibilityMessageFactory->createMessage(new \stdClass());
    }

    public function testGetEntityFromMessageCategoryVisibility()
    {
        $categoryVisibilityId = 123;

        $data =  [
            CategoryVisibilityMessageFactory::ID => $categoryVisibilityId,
            CategoryVisibilityMessageFactory::ENTITY_CLASS_NAME => CategoryVisibility::class,
            CategoryVisibilityMessageFactory::CATEGORY_ID => 42
        ];

        $categoryVisibility = $this->getEntity(CategoryVisibility::class, ['id' => $categoryVisibilityId]);

        $repository = $this->getMockBuilder(CategoryVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('find')
            ->with($categoryVisibilityId)
            ->willReturn($categoryVisibility);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CategoryVisibility::class)
            ->willReturn($em);

        $this->assertEquals($categoryVisibility, $this->categoryVisibilityMessageFactory->getEntityFromMessage($data));
    }

    /**
     * @expectedException \Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Message should not be empty.
     */
    public function testGetEntityFromMessageEmptyData()
    {
        $this->categoryVisibilityMessageFactory->getEntityFromMessage([]);
    }

    /**
     * @expectedException \Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Message should contain entity name.
     */
    public function testGetEntityFromMessageEmptyEntityClassName()
    {
        $this->categoryVisibilityMessageFactory->getEntityFromMessage([CategoryVisibilityMessageFactory::ID => 42]);
    }

    /**
     * @expectedException \Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Message should contain entity id.
     */
    public function testGetEntityFromMessageEmptyEntityId()
    {
        $this->categoryVisibilityMessageFactory->getEntityFromMessage([
            CategoryVisibilityMessageFactory::ENTITY_CLASS_NAME => CategoryVisibility::class
        ]);
    }
}
