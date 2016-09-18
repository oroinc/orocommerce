<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Entity\Repository\AccountGroupRepository;
use Oro\Bundle\AccountBundle\Entity\Repository\AccountRepository;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\Repository\AccountGroupCategoryVisibilityRepository;
use Oro\Bundle\AccountBundle\Entity\Visibility\Repository\CategoryVisibilityRepository;
use Oro\Bundle\AccountBundle\Model\CategoryVisibilityMessageFactory;
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

        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => $categoryId]);

        /** @var CategoryVisibility $categoryVisibility */
        $categoryVisibility = $this->getEntity(CategoryVisibility::class, ['id' => $categoryVisibilityId]);
        $categoryVisibility->setCategory($category);

        $this->categoryVisibilityMessageFactory->createMessage($categoryVisibility);

        $expected = [
            CategoryVisibilityMessageFactory::ID => $categoryVisibilityId,
            CategoryVisibilityMessageFactory::ENTITY_CLASS_NAME => CategoryVisibility::class,
            CategoryVisibilityMessageFactory::CATEGORY_ID => $categoryId
        ];

        $this->assertEquals($expected, $this->categoryVisibilityMessageFactory->createMessage($categoryVisibility));
    }

    public function testCreateMessageForAccountGroupCategoryVisibility()
    {
        $accountGroupId = 1;
        $categoryId = 123;
        $categoryVisibilityId = 42;

        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => $categoryId]);

        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getEntity(AccountGroup::class, ['id' => $accountGroupId]);

        /** @var AccountGroupCategoryVisibility $accountGroupCategoryVisibility */
        $accountGroupCategoryVisibility = $this->getEntity(
            AccountGroupCategoryVisibility::class,
            ['id' => $categoryVisibilityId]
        );
        $accountGroupCategoryVisibility->setCategory($category);
        $accountGroupCategoryVisibility->setAccountGroup($accountGroup);

        $expected = [
            CategoryVisibilityMessageFactory::ID => $categoryVisibilityId,
            CategoryVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountGroupCategoryVisibility::class,
            CategoryVisibilityMessageFactory::CATEGORY_ID => $categoryId,
            CategoryVisibilityMessageFactory::ACCOUNT_GROUP_ID => $accountGroupId
        ];

        $this->assertEquals(
            $expected,
            $this->categoryVisibilityMessageFactory->createMessage($accountGroupCategoryVisibility)
        );
    }

    public function testCreateMessageForAccountCategoryVisibility()
    {
        $accountId = 5;
        $categoryId = 123;
        $categoryVisibilityId = 42;

        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => $categoryId]);

        /** @var Account $account */
        $account = $this->getEntity(Account::class, ['id' => $accountId]);

        /** @var AccountCategoryVisibility $accountCategoryVisibility */
        $accountCategoryVisibility = $this->getEntity(
            AccountCategoryVisibility::class,
            ['id' => $categoryVisibilityId]
        );
        $accountCategoryVisibility->setCategory($category);
        $accountCategoryVisibility->setAccount($account);

        $this->categoryVisibilityMessageFactory->createMessage($accountCategoryVisibility);

        $expected = [
            CategoryVisibilityMessageFactory::ID => $categoryVisibilityId,
            CategoryVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountCategoryVisibility::class,
            CategoryVisibilityMessageFactory::CATEGORY_ID => $categoryId,
            CategoryVisibilityMessageFactory::ACCOUNT_ID => $accountId
        ];

        $this->assertEquals(
            $expected,
            $this->categoryVisibilityMessageFactory->createMessage($accountCategoryVisibility)
        );
    }

    /**
     * @expectedException \Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException
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

    public function testGetEntityFromMessageCategoryVisibilityWithoutVisibility()
    {
        $categoryVisibilityId = 123;
        $categoryId = 42;

        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => $categoryId]);

        $data =  [
            CategoryVisibilityMessageFactory::ID => $categoryVisibilityId,
            CategoryVisibilityMessageFactory::ENTITY_CLASS_NAME => CategoryVisibility::class,
            CategoryVisibilityMessageFactory::CATEGORY_ID => $categoryId
        ];

        $categoryRepository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $categoryRepository->expects($this->once())
            ->method('find')
            ->with($categoryId)
            ->willReturn($category);

        $categoryVisibilityRepository = $this->getMockBuilder(CategoryVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityRepository->expects($this->once())
            ->method('find')
            ->with($categoryVisibilityId)
            ->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [CategoryVisibility::class, $categoryVisibilityRepository],
                [Category::class, $categoryRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $expectedVisibility = new CategoryVisibility();
        $expectedVisibility->setCategory($category);
        $expectedVisibility->setVisibility(CategoryVisibility::CONFIG);

        $this->assertEquals($expectedVisibility, $this->categoryVisibilityMessageFactory->getEntityFromMessage($data));
    }

    /**
     * @expectedException \Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Category object was not found.
     */
    public function testGetEntityFromMessageCategoryVisibilityWithoutCategory()
    {
        $categoryVisibilityId = 123;
        $categoryId = 42;

        $data =  [
            CategoryVisibilityMessageFactory::ID => $categoryVisibilityId,
            CategoryVisibilityMessageFactory::ENTITY_CLASS_NAME => CategoryVisibility::class,
            CategoryVisibilityMessageFactory::CATEGORY_ID => $categoryId
        ];

        $categoryRepository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $categoryRepository->expects($this->once())
            ->method('find')
            ->with($categoryId)
            ->willReturn(null);

        $categoryVisibilityRepository = $this->getMockBuilder(CategoryVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityRepository->expects($this->once())
            ->method('find')
            ->with($categoryVisibilityId)
            ->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [CategoryVisibility::class, $categoryVisibilityRepository],
                [Category::class, $categoryRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->categoryVisibilityMessageFactory->getEntityFromMessage($data);
    }

    public function testGetEntityFromMessageAccountCategoryVisibilityWithoutVisibility()
    {
        $accountCategoryVisibilityId = 123;
        $categoryId = 42;
        $accountId = 4;

        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => $categoryId]);

        /** @var Account $account */
        $account = $this->getEntity(Account::class, ['id' => $accountId]);

        $data =  [
            CategoryVisibilityMessageFactory::ID => $accountCategoryVisibilityId,
            CategoryVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountCategoryVisibility::class,
            CategoryVisibilityMessageFactory::CATEGORY_ID => $categoryId,
            CategoryVisibilityMessageFactory::ACCOUNT_ID => $accountId
        ];

        $categoryRepository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $categoryRepository->expects($this->once())
            ->method('find')
            ->with($categoryId)
            ->willReturn($category);

        $accountRepository = $this->getMockBuilder(AccountRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountRepository->expects($this->once())
            ->method('find')
            ->with($accountId)
            ->willReturn($account);

        $accountCategoryVisibilityRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountCategoryVisibilityRepository->expects($this->once())
            ->method('find')
            ->with($accountCategoryVisibilityId)
            ->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [AccountCategoryVisibility::class, $accountCategoryVisibilityRepository],
                [Category::class, $categoryRepository],
                [Account::class, $accountRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $expectedVisibility = new AccountCategoryVisibility();
        $expectedVisibility->setCategory($category);
        $expectedVisibility->setAccount($account);
        $expectedVisibility->setVisibility(AccountCategoryVisibility::ACCOUNT_GROUP);

        $this->assertEquals($expectedVisibility, $this->categoryVisibilityMessageFactory->getEntityFromMessage($data));
    }

    /**
     * @expectedException \Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Category object was not found.
     */
    public function testGetEntityFromMessageAccountCategoryVisibilityWithoutCategory()
    {
        $accountCategoryVisibilityId = 123;
        $categoryId = 42;
        $accountId = 4;

        /** @var Account $account */
        $account = $this->getEntity(Account::class, ['id' => $accountId]);

        $data =  [
            CategoryVisibilityMessageFactory::ID => $accountCategoryVisibilityId,
            CategoryVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountCategoryVisibility::class,
            CategoryVisibilityMessageFactory::CATEGORY_ID => $categoryId,
            CategoryVisibilityMessageFactory::ACCOUNT_ID => $accountId
        ];

        $categoryRepository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $categoryRepository->expects($this->once())
            ->method('find')
            ->with($categoryId)
            ->willReturn(null);

        $accountRepository = $this->getMockBuilder(AccountRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountRepository->expects($this->once())
            ->method('find')
            ->with($accountId)
            ->willReturn($account);

        $accountCategoryVisibilityRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountCategoryVisibilityRepository->expects($this->once())
            ->method('find')
            ->with($accountCategoryVisibilityId)
            ->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [AccountCategoryVisibility::class, $accountCategoryVisibilityRepository],
                [Category::class, $categoryRepository],
                [Account::class, $accountRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->categoryVisibilityMessageFactory->getEntityFromMessage($data);
    }

    /**
     * @expectedException \Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Account object was not found.
     */
    public function testGetEntityFromMessageAccountCategoryVisibilityWithoutAccount()
    {
        $accountCategoryVisibilityId = 123;
        $categoryId = 42;
        $accountId = 4;

        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => $categoryId]);

        $data =  [
            CategoryVisibilityMessageFactory::ID => $accountCategoryVisibilityId,
            CategoryVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountCategoryVisibility::class,
            CategoryVisibilityMessageFactory::CATEGORY_ID => $categoryId,
            CategoryVisibilityMessageFactory::ACCOUNT_ID => $accountId
        ];

        $categoryRepository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $categoryRepository->expects($this->once())
            ->method('find')
            ->with($categoryId)
            ->willReturn($category);

        $accountRepository = $this->getMockBuilder(AccountRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountRepository->expects($this->once())
            ->method('find')
            ->with($accountId)
            ->willReturn(null);

        $accountCategoryVisibilityRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountCategoryVisibilityRepository->expects($this->once())
            ->method('find')
            ->with($accountCategoryVisibilityId)
            ->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [AccountCategoryVisibility::class, $accountCategoryVisibilityRepository],
                [Category::class, $categoryRepository],
                [Account::class, $accountRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->categoryVisibilityMessageFactory->getEntityFromMessage($data);
    }

    public function testGetEntityFromMessageAccountGroupCategoryVisibilityWithoutVisibility()
    {
        $accountGroupCategoryVisibilityId = 123;
        $categoryId = 42;
        $accountGroupId = 4;

        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => $categoryId]);

        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getEntity(AccountGroup::class, ['id' => $accountGroupId]);

        $data =  [
            CategoryVisibilityMessageFactory::ID => $accountGroupCategoryVisibilityId,
            CategoryVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountGroupCategoryVisibility::class,
            CategoryVisibilityMessageFactory::CATEGORY_ID => $categoryId,
            CategoryVisibilityMessageFactory::ACCOUNT_GROUP_ID => $accountGroupId
        ];

        $categoryRepository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $categoryRepository->expects($this->once())
            ->method('find')
            ->with($categoryId)
            ->willReturn($category);

        $accountGroupRepository = $this->getMockBuilder(AccountGroupRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountGroupRepository->expects($this->once())
            ->method('find')
            ->with($accountGroupId)
            ->willReturn($accountGroup);

        $accountCategoryVisibilityRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountCategoryVisibilityRepository->expects($this->once())
            ->method('find')
            ->with($accountGroupCategoryVisibilityId)
            ->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [AccountGroupCategoryVisibility::class, $accountCategoryVisibilityRepository],
                [Category::class, $categoryRepository],
                [AccountGroup::class, $accountGroupRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $expectedVisibility = new AccountGroupCategoryVisibility();
        $expectedVisibility->setCategory($category);
        $expectedVisibility->setAccountGroup($accountGroup);
        $expectedVisibility->setVisibility(AccountGroupCategoryVisibility::CATEGORY);

        $this->assertEquals($expectedVisibility, $this->categoryVisibilityMessageFactory->getEntityFromMessage($data));
    }

    /**
     * @expectedException \Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage AccountGroup object was not found.
     */
    public function testGetEntityFromMessageAccountGroupCategoryVisibilityWithoutAccountGroup()
    {
        $accountGroupCategoryVisibilityId = 123;
        $categoryId = 42;
        $accountGroupId = 4;

        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => $categoryId]);

        $data =  [
            CategoryVisibilityMessageFactory::ID => $accountGroupCategoryVisibilityId,
            CategoryVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountGroupCategoryVisibility::class,
            CategoryVisibilityMessageFactory::CATEGORY_ID => $categoryId,
            CategoryVisibilityMessageFactory::ACCOUNT_GROUP_ID => $accountGroupId
        ];

        $categoryRepository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $categoryRepository->expects($this->once())
            ->method('find')
            ->with($categoryId)
            ->willReturn($category);

        $accountGroupRepository = $this->getMockBuilder(AccountGroupRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountGroupRepository->expects($this->once())
            ->method('find')
            ->with($accountGroupId)
            ->willReturn(null);

        $accountGroupCategoryVisibilityRepository = $this
            ->getMockBuilder(AccountGroupCategoryVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountGroupCategoryVisibilityRepository->expects($this->once())
            ->method('find')
            ->with($accountGroupCategoryVisibilityId)
            ->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [AccountGroupCategoryVisibility::class, $accountGroupCategoryVisibilityRepository],
                [Category::class, $categoryRepository],
                [AccountGroup::class, $accountGroupRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->categoryVisibilityMessageFactory->getEntityFromMessage($data);
    }

    /**
     * @expectedException \Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Category object was not found.
     */
    public function testGetEntityFromMessageAccountGroupCategoryVisibilityWithoutCategory()
    {
        $accountGroupCategoryVisibilityId = 123;
        $categoryId = 42;
        $accountGroupId = 4;

        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getEntity(AccountGroup::class, ['id' => $accountGroupId]);

        $data =  [
            CategoryVisibilityMessageFactory::ID => $accountGroupCategoryVisibilityId,
            CategoryVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountGroupCategoryVisibility::class,
            CategoryVisibilityMessageFactory::CATEGORY_ID => $categoryId,
            CategoryVisibilityMessageFactory::ACCOUNT_GROUP_ID => $accountGroupId
        ];

        $categoryRepository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $categoryRepository->expects($this->once())
            ->method('find')
            ->with($categoryId)
            ->willReturn(null);

        $accountGroupRepository = $this->getMockBuilder(AccountGroupRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountGroupRepository->expects($this->once())
            ->method('find')
            ->with($accountGroupId)
            ->willReturn($accountGroup);

        $accountGroupCategoryVisibilityRepository = $this
            ->getMockBuilder(AccountGroupCategoryVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountGroupCategoryVisibilityRepository->expects($this->once())
            ->method('find')
            ->with($accountGroupCategoryVisibilityId)
            ->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [AccountGroupCategoryVisibility::class, $accountGroupCategoryVisibilityRepository],
                [Category::class, $categoryRepository],
                [AccountGroup::class, $accountGroupRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->categoryVisibilityMessageFactory->getEntityFromMessage($data);
    }

    /**
     * @expectedException \Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Message should not be empty.
     */
    public function testGetEntityFromMessageEmptyData()
    {
        $this->categoryVisibilityMessageFactory->getEntityFromMessage([]);
    }

    /**
     * @expectedException \Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Message should contain entity name.
     */
    public function testGetEntityFromMessageEmptyEntityClassName()
    {
        $this->categoryVisibilityMessageFactory->getEntityFromMessage([CategoryVisibilityMessageFactory::ID => 42]);
    }

    /**
     * @expectedException \Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Message should contain entity id.
     */
    public function testGetEntityFromMessageEmptyEntityId()
    {
        $this->categoryVisibilityMessageFactory->getEntityFromMessage([
            CategoryVisibilityMessageFactory::ENTITY_CLASS_NAME => CategoryVisibility::class
        ]);
    }
}
