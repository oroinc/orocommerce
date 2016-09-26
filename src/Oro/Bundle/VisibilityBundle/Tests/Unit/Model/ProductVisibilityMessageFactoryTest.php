<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Entity\Repository\AccountGroupRepository;
use Oro\Bundle\AccountBundle\Entity\Repository\AccountRepository;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\AccountGroupProductVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\AccountProductVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\ProductVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilityMessageFactory;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ProductVisibilityMessageFactoryTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ProductVisibilityMessageFactory
     */
    protected $productVisibilityMessageFactory;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->productVisibilityMessageFactory = new ProductVisibilityMessageFactory($this->registry);
    }

    public function testCreateMessageForProductVisibility()
    {
        $productId = 123;
        $productVisibilityId = 42;
        $websiteId = 1;

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => $websiteId]);

        /** @var ProductVisibility $productVisibility */
        $productVisibility = $this->getEntity(ProductVisibility::class, ['id' => $productVisibilityId]);
        $productVisibility->setProduct($product);
        $productVisibility->setWebsite($website);

        $this->productVisibilityMessageFactory->createMessage($productVisibility);

        $expected = [
            ProductVisibilityMessageFactory::ID => $productVisibilityId,
            ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
            ProductVisibilityMessageFactory::PRODUCT_ID => $productId,
            ProductVisibilityMessageFactory::WEBSITE_ID => $websiteId
        ];

        $this->assertEquals($expected, $this->productVisibilityMessageFactory->createMessage($productVisibility));
    }

    public function testCreateMessageForAccountGroupProductVisibility()
    {
        $accountGroupId = 1;
        $productId = 123;
        $productVisibilityId = 42;
        $websiteId = 1;
        
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getEntity(AccountGroup::class, ['id' => $accountGroupId]);

        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => $websiteId]);

        /** @var AccountGroupProductVisibility $accountGroupProductVisibility */
        $accountGroupProductVisibility = $this->getEntity(
            AccountGroupProductVisibility::class,
            ['id' => $productVisibilityId]
        );
        $accountGroupProductVisibility->setProduct($product);
        $accountGroupProductVisibility->setAccountGroup($accountGroup);
        $accountGroupProductVisibility->setWebsite($website);

        $expected = [
            ProductVisibilityMessageFactory::ID => $productVisibilityId,
            ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountGroupProductVisibility::class,
            ProductVisibilityMessageFactory::PRODUCT_ID => $productId,
            ProductVisibilityMessageFactory::ACCOUNT_GROUP_ID => $accountGroupId,
            ProductVisibilityMessageFactory::WEBSITE_ID => $websiteId
        ];

        $this->assertEquals(
            $expected,
            $this->productVisibilityMessageFactory->createMessage($accountGroupProductVisibility)
        );
    }

    public function testCreateMessageForAccountProductVisibility()
    {
        $accountId = 5;
        $productId = 123;
        $productVisibilityId = 42;
        $websiteId = 1;

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        /** @var Account $account */
        $account = $this->getEntity(Account::class, ['id' => $accountId]);

        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => $websiteId]);

        /** @var AccountProductVisibility $accountProductVisibility */
        $accountProductVisibility = $this->getEntity(
            AccountProductVisibility::class,
            ['id' => $productVisibilityId]
        );
        $accountProductVisibility->setProduct($product);
        $accountProductVisibility->setAccount($account);
        $accountProductVisibility->setWebsite($website);

        $this->productVisibilityMessageFactory->createMessage($accountProductVisibility);

        $expected = [
            ProductVisibilityMessageFactory::ID => $productVisibilityId,
            ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountProductVisibility::class,
            ProductVisibilityMessageFactory::PRODUCT_ID => $productId,
            ProductVisibilityMessageFactory::ACCOUNT_ID => $accountId,
            ProductVisibilityMessageFactory::WEBSITE_ID => $websiteId
        ];

        $this->assertEquals(
            $expected,
            $this->productVisibilityMessageFactory->createMessage($accountProductVisibility)
        );
    }

    /**
     * @expectedException \Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Unsupported entity class
     */
    public function testCreateMessageUnsupportedClass()
    {
        $this->productVisibilityMessageFactory->createMessage(new \stdClass());
    }

    public function testGetEntityFromMessageProductVisibility()
    {
        $productVisibilityId = 123;

        $data =  [
            ProductVisibilityMessageFactory::ID => $productVisibilityId,
            ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
            ProductVisibilityMessageFactory::PRODUCT_ID => 42
        ];

        $productVisibility = $this->getEntity(ProductVisibility::class, ['id' => $productVisibilityId]);

        $repository = $this->getMockBuilder(ProductVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('find')
            ->with($productVisibilityId)
            ->willReturn($productVisibility);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ProductVisibility::class)
            ->willReturn($em);

        $this->assertEquals($productVisibility, $this->productVisibilityMessageFactory->getEntityFromMessage($data));
    }

    public function testGetEntityFromMessageProductVisibilityWithoutVisibility()
    {
        $productVisibilityId = 123;
        $productId = 42;
        $websiteId = 1;

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => $websiteId]);

        $data =  [
            ProductVisibilityMessageFactory::ID => $productVisibilityId,
            ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
            ProductVisibilityMessageFactory::PRODUCT_ID => $productId,
            ProductVisibilityMessageFactory::WEBSITE_ID => $websiteId
        ];

        $websiteRepository = $this->getMockBuilder(WebsiteRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $websiteRepository->expects($this->once())
            ->method('find')
            ->with($websiteId)
            ->willReturn($website);

        $productRepository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productRepository->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $productVisibilityRepository = $this->getMockBuilder(ProductVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productVisibilityRepository->expects($this->once())
            ->method('find')
            ->with($productVisibilityId)
            ->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [ProductVisibility::class, $productVisibilityRepository],
                [Product::class, $productRepository],
                [Website::class, $websiteRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $expectedVisibility = new ProductVisibility();
        $expectedVisibility->setProduct($product);
        $expectedVisibility->setWebsite($website);
        $expectedVisibility->setVisibility(ProductVisibility::CATEGORY);

        $this->assertEquals($expectedVisibility, $this->productVisibilityMessageFactory->getEntityFromMessage($data));
    }

    /**
     * @expectedException \Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Product object was not found.
     */
    public function testGetEntityFromMessageProductVisibilityWithoutProduct()
    {
        $productVisibilityId = 123;
        $productId = 42;
        $websiteId = 1;

        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => $websiteId]);

        $data =  [
            ProductVisibilityMessageFactory::ID => $productVisibilityId,
            ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
            ProductVisibilityMessageFactory::PRODUCT_ID => $productId,
            ProductVisibilityMessageFactory::WEBSITE_ID => $websiteId
        ];

        $websiteRepository = $this->getMockBuilder(WebsiteRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $websiteRepository->expects($this->once())
            ->method('find')
            ->with($websiteId)
            ->willReturn($website);

        $productRepository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productRepository->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn(null);

        $productVisibilityRepository = $this->getMockBuilder(ProductVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productVisibilityRepository->expects($this->once())
            ->method('find')
            ->with($productVisibilityId)
            ->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [ProductVisibility::class, $productVisibilityRepository],
                [Product::class, $productRepository],
                [Website::class, $websiteRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->productVisibilityMessageFactory->getEntityFromMessage($data);
    }

    /**
     * @expectedException \Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Website object was not found.
     */
    public function testGetEntityFromMessageProductVisibilityWithoutWebsite()
    {
        $productVisibilityId = 123;
        $productId = 42;
        $websiteId = 1;

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        $data =  [
            ProductVisibilityMessageFactory::ID => $productVisibilityId,
            ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
            ProductVisibilityMessageFactory::PRODUCT_ID => $productId,
            ProductVisibilityMessageFactory::WEBSITE_ID => $websiteId
        ];

        $websiteRepository = $this->getMockBuilder(WebsiteRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $websiteRepository->expects($this->once())
            ->method('find')
            ->with($websiteId)
            ->willReturn(null);

        $productRepository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productRepository->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $productVisibilityRepository = $this->getMockBuilder(ProductVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productVisibilityRepository->expects($this->once())
            ->method('find')
            ->with($productVisibilityId)
            ->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [ProductVisibility::class, $productVisibilityRepository],
                [Product::class, $productRepository],
                [Website::class, $websiteRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->productVisibilityMessageFactory->getEntityFromMessage($data);
    }

    public function testGetEntityFromMessageAccountProductVisibilityWithoutVisibility()
    {
        $accountProductVisibilityId = 123;
        $productId = 42;
        $accountId = 4;
        $websiteId = 1;

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        /** @var Account $account */
        $account = $this->getEntity(Account::class, ['id' => $accountId]);

        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => $websiteId]);

        $data =  [
            ProductVisibilityMessageFactory::ID => $accountProductVisibilityId,
            ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountProductVisibility::class,
            ProductVisibilityMessageFactory::PRODUCT_ID => $productId,
            ProductVisibilityMessageFactory::ACCOUNT_ID => $accountId,
            ProductVisibilityMessageFactory::WEBSITE_ID => $websiteId
        ];

        $websiteRepository = $this->getMockBuilder(WebsiteRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $websiteRepository->expects($this->once())
            ->method('find')
            ->with($websiteId)
            ->willReturn($website);

        $productRepository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productRepository->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $accountRepository = $this->getMockBuilder(AccountRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountRepository->expects($this->once())
            ->method('find')
            ->with($accountId)
            ->willReturn($account);

        $accountProductVisibilityRepository = $this->getMockBuilder(AccountProductVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountProductVisibilityRepository->expects($this->once())
            ->method('find')
            ->with($accountProductVisibilityId)
            ->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [AccountProductVisibility::class, $accountProductVisibilityRepository],
                [Product::class, $productRepository],
                [Account::class, $accountRepository],
                [Website::class, $websiteRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $expectedVisibility = new AccountProductVisibility();
        $expectedVisibility->setProduct($product);
        $expectedVisibility->setAccount($account);
        $expectedVisibility->setVisibility(AccountProductVisibility::ACCOUNT_GROUP);
        $expectedVisibility->setWebsite($website);

        $this->assertEquals($expectedVisibility, $this->productVisibilityMessageFactory->getEntityFromMessage($data));
    }

    /**
     * @expectedException \Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Product object was not found.
     */
    public function testGetEntityFromMessageAccountProductVisibilityWithoutProduct()
    {
        $accountProductVisibilityId = 123;
        $productId = 42;
        $accountId = 4;
        $websiteId = 1;

        /** @var Account $account */
        $account = $this->getEntity(Account::class, ['id' => $accountId]);

        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => $websiteId]);

        $data =  [
            ProductVisibilityMessageFactory::ID => $accountProductVisibilityId,
            ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountProductVisibility::class,
            ProductVisibilityMessageFactory::PRODUCT_ID => $productId,
            ProductVisibilityMessageFactory::ACCOUNT_ID => $accountId,
            ProductVisibilityMessageFactory::WEBSITE_ID => $websiteId
        ];

        $websiteRepository = $this->getMockBuilder(WebsiteRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $websiteRepository->expects($this->once())
            ->method('find')
            ->with($websiteId)
            ->willReturn($website);

        $productRepository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productRepository->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn(null);

        $accountRepository = $this->getMockBuilder(AccountRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountRepository->expects($this->once())
            ->method('find')
            ->with($accountId)
            ->willReturn($account);

        $accountProductVisibilityRepository = $this->getMockBuilder(AccountProductVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountProductVisibilityRepository->expects($this->once())
            ->method('find')
            ->with($accountProductVisibilityId)
            ->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [AccountProductVisibility::class, $accountProductVisibilityRepository],
                [Product::class, $productRepository],
                [Account::class, $accountRepository],
                [Website::class, $websiteRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->productVisibilityMessageFactory->getEntityFromMessage($data);
    }
    
    /**
     * @expectedException \Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Account object was not found.
     */
    public function testGetEntityFromMessageAccountProductVisibilityWithoutAccountGroup()
    {
        $accountProductVisibilityId = 123;
        $productId = 42;
        $accountId = 4;
        $websiteId = 1;

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => $websiteId]);

        $data =  [
            ProductVisibilityMessageFactory::ID => $accountProductVisibilityId,
            ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountProductVisibility::class,
            ProductVisibilityMessageFactory::PRODUCT_ID => $productId,
            ProductVisibilityMessageFactory::ACCOUNT_ID => $accountId,
            ProductVisibilityMessageFactory::WEBSITE_ID => $websiteId
        ];

        $websiteRepository = $this->getMockBuilder(WebsiteRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $websiteRepository->expects($this->once())
            ->method('find')
            ->with($websiteId)
            ->willReturn($website);

        $productRepository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productRepository->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $accountRepository = $this->getMockBuilder(AccountRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountRepository->expects($this->once())
            ->method('find')
            ->with($accountId)
            ->willReturn(null);

        $accountProductVisibilityRepository = $this->getMockBuilder(AccountProductVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountProductVisibilityRepository->expects($this->once())
            ->method('find')
            ->with($accountProductVisibilityId)
            ->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [AccountProductVisibility::class, $accountProductVisibilityRepository],
                [Product::class, $productRepository],
                [Account::class, $accountRepository],
                [Website::class, $websiteRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->productVisibilityMessageFactory->getEntityFromMessage($data);
    }
    
    /**
     * @expectedException \Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Website object was not found.
     */
    public function testGetEntityFromMessageAccountProductVisibilityWithoutWebsite()
    {
        $accountProductVisibilityId = 123;
        $productId = 42;
        $accountId = 4;
        $websiteId = 1;

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        /** @var Account $account */
        $account = $this->getEntity(Account::class, ['id' => $accountId]);

        $data =  [
            ProductVisibilityMessageFactory::ID => $accountProductVisibilityId,
            ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountProductVisibility::class,
            ProductVisibilityMessageFactory::PRODUCT_ID => $productId,
            ProductVisibilityMessageFactory::ACCOUNT_ID => $accountId,
            ProductVisibilityMessageFactory::WEBSITE_ID => $websiteId
        ];

        $websiteRepository = $this->getMockBuilder(WebsiteRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $websiteRepository->expects($this->once())
            ->method('find')
            ->with($websiteId)
            ->willReturn(null);

        $productRepository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productRepository->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $accountRepository = $this->getMockBuilder(AccountRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountRepository->expects($this->once())
            ->method('find')
            ->with($accountId)
            ->willReturn($account);

        $accountProductVisibilityRepository = $this->getMockBuilder(AccountProductVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountProductVisibilityRepository->expects($this->once())
            ->method('find')
            ->with($accountProductVisibilityId)
            ->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [AccountProductVisibility::class, $accountProductVisibilityRepository],
                [Product::class, $productRepository],
                [Account::class, $accountRepository],
                [Website::class, $websiteRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->productVisibilityMessageFactory->getEntityFromMessage($data);
    }

    public function testGetEntityFromMessageAccountGroupProductVisibilityWithoutVisibility()
    {
        $accountGroupProductVisibilityId = 123;
        $productId = 42;
        $accountGroupId = 4;
        $websiteId = 1;

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getEntity(AccountGroup::class, ['id' => $accountGroupId]);

        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => $websiteId]);

        $data =  [
            ProductVisibilityMessageFactory::ID => $accountGroupProductVisibilityId,
            ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountGroupProductVisibility::class,
            ProductVisibilityMessageFactory::PRODUCT_ID => $productId,
            ProductVisibilityMessageFactory::ACCOUNT_GROUP_ID => $accountGroupId,
            ProductVisibilityMessageFactory::WEBSITE_ID => $websiteId
        ];

        $websiteRepository = $this->getMockBuilder(WebsiteRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $websiteRepository->expects($this->once())
            ->method('find')
            ->with($websiteId)
            ->willReturn($website);

        $productRepository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productRepository->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $accountGroupRepository = $this->getMockBuilder(AccountGroupRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountGroupRepository->expects($this->once())
            ->method('find')
            ->with($accountGroupId)
            ->willReturn($accountGroup);

        $accountProductVisibilityRepository = $this->getMockBuilder(AccountGroupProductVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountProductVisibilityRepository->expects($this->once())
            ->method('find')
            ->with($accountGroupProductVisibilityId)
            ->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [AccountGroupProductVisibility::class, $accountProductVisibilityRepository],
                [Product::class, $productRepository],
                [AccountGroup::class, $accountGroupRepository],
                [Website::class, $websiteRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $expectedVisibility = new AccountGroupProductVisibility();
        $expectedVisibility->setProduct($product);
        $expectedVisibility->setAccountGroup($accountGroup);
        $expectedVisibility->setVisibility(AccountGroupProductVisibility::CURRENT_PRODUCT);
        $expectedVisibility->setWebsite($website);

        $this->assertEquals($expectedVisibility, $this->productVisibilityMessageFactory->getEntityFromMessage($data));
    }

    /**
     * @expectedException \Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage AccountGroup object was not found.
     */
    public function testGetEntityFromMessageAccountGroupProductVisibilityWithoutAccountGroup()
    {
        $accountGroupProductVisibilityId = 123;
        $productId = 42;
        $accountGroupId = 4;
        $websiteId = 1;

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => $websiteId]);

        $data =  [
            ProductVisibilityMessageFactory::ID => $accountGroupProductVisibilityId,
            ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountGroupProductVisibility::class,
            ProductVisibilityMessageFactory::PRODUCT_ID => $productId,
            ProductVisibilityMessageFactory::ACCOUNT_GROUP_ID => $accountGroupId,
            ProductVisibilityMessageFactory::WEBSITE_ID => $websiteId
        ];

        $websiteRepository = $this->getMockBuilder(WebsiteRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $websiteRepository->expects($this->once())
            ->method('find')
            ->with($websiteId)
            ->willReturn($website);

        $productRepository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productRepository->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $accountGroupRepository = $this->getMockBuilder(AccountGroupRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountGroupRepository->expects($this->once())
            ->method('find')
            ->with($accountGroupId)
            ->willReturn(null);

        $accountProductVisibilityRepository = $this->getMockBuilder(AccountGroupProductVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountProductVisibilityRepository->expects($this->once())
            ->method('find')
            ->with($accountGroupProductVisibilityId)
            ->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [AccountGroupProductVisibility::class, $accountProductVisibilityRepository],
                [Product::class, $productRepository],
                [AccountGroup::class, $accountGroupRepository],
                [Website::class, $websiteRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->productVisibilityMessageFactory->getEntityFromMessage($data);
    }

    /**
     * @expectedException \Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Product object was not found.
     */
    public function testGetEntityFromMessageAccountGroupProductVisibilityWithoutProduct()
    {
        $accountGroupProductVisibilityId = 123;
        $productId = 42;
        $accountGroupId = 4;
        $websiteId = 1;

        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => $websiteId]);

        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getEntity(AccountGroup::class, ['id' => $accountGroupId]);

        $data =  [
            ProductVisibilityMessageFactory::ID => $accountGroupProductVisibilityId,
            ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountGroupProductVisibility::class,
            ProductVisibilityMessageFactory::PRODUCT_ID => $productId,
            ProductVisibilityMessageFactory::ACCOUNT_GROUP_ID => $accountGroupId,
            ProductVisibilityMessageFactory::WEBSITE_ID => $websiteId
        ];

        $websiteRepository = $this->getMockBuilder(WebsiteRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $websiteRepository->expects($this->once())
            ->method('find')
            ->with($websiteId)
            ->willReturn($website);

        $productRepository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productRepository->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn(null);

        $accountGroupRepository = $this->getMockBuilder(AccountGroupRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountGroupRepository->expects($this->once())
            ->method('find')
            ->with($accountGroupId)
            ->willReturn($accountGroup);

        $accountProductVisibilityRepository = $this->getMockBuilder(AccountGroupProductVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountProductVisibilityRepository->expects($this->once())
            ->method('find')
            ->with($accountGroupProductVisibilityId)
            ->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [AccountGroupProductVisibility::class, $accountProductVisibilityRepository],
                [Product::class, $productRepository],
                [AccountGroup::class, $accountGroupRepository],
                [Website::class, $websiteRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->productVisibilityMessageFactory->getEntityFromMessage($data);
    }

    /**
     * @expectedException \Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Website object was not found.
     */
    public function testGetEntityFromMessageAccountGroupProductVisibilityWithoutWebsite()
    {
        $accountGroupProductVisibilityId = 123;
        $productId = 42;
        $accountGroupId = 4;
        $websiteId = 1;

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getEntity(AccountGroup::class, ['id' => $accountGroupId]);

        $data =  [
            ProductVisibilityMessageFactory::ID => $accountGroupProductVisibilityId,
            ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountGroupProductVisibility::class,
            ProductVisibilityMessageFactory::PRODUCT_ID => $productId,
            ProductVisibilityMessageFactory::ACCOUNT_GROUP_ID => $accountGroupId,
            ProductVisibilityMessageFactory::WEBSITE_ID => $websiteId
        ];

        $websiteRepository = $this->getMockBuilder(WebsiteRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $websiteRepository->expects($this->once())
            ->method('find')
            ->with($websiteId)
            ->willReturn(null);

        $productRepository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productRepository->expects($this->once())
            ->method('find')
            ->with($productId)
            ->willReturn($product);

        $accountGroupRepository = $this->getMockBuilder(AccountGroupRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountGroupRepository->expects($this->once())
            ->method('find')
            ->with($accountGroupId)
            ->willReturn($accountGroup);

        $accountProductVisibilityRepository = $this->getMockBuilder(AccountGroupProductVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountProductVisibilityRepository->expects($this->once())
            ->method('find')
            ->with($accountGroupProductVisibilityId)
            ->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [AccountGroupProductVisibility::class, $accountProductVisibilityRepository],
                [Product::class, $productRepository],
                [AccountGroup::class, $accountGroupRepository],
                [Website::class, $websiteRepository]
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->productVisibilityMessageFactory->getEntityFromMessage($data);
    }

    /**
     * @expectedException \Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Message should not be empty.
     */
    public function testGetEntityFromMessageEmptyData()
    {
        $this->productVisibilityMessageFactory->getEntityFromMessage([]);
    }

    /**
     * @expectedException \Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Message should contain entity name.
     */
    public function testGetEntityFromMessageEmptyEntityClassName()
    {
        $this->productVisibilityMessageFactory->getEntityFromMessage([ProductVisibilityMessageFactory::ID => 42]);
    }

    /**
     * @expectedException \Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException
     * @expectedExceptionMessage Message should contain entity id.
     */
    public function testGetEntityFromMessageEmptyEntityId()
    {
        $this->productVisibilityMessageFactory->getEntityFromMessage([
            ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class
        ]);
    }
}
