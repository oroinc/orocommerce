<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\ProductVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilityMessageFactory;
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
        $scopeId = 1;

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => $scopeId]);

        /** @var ProductVisibility $productVisibility */
        $productVisibility = $this->getEntity(ProductVisibility::class, ['id' => $productVisibilityId]);
        $productVisibility->setProduct($product);
        $productVisibility->setScope($scope);

        $this->productVisibilityMessageFactory->createMessage($productVisibility);

        $expected = [
            ProductVisibilityMessageFactory::ID => $productVisibilityId,
            ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
            ProductVisibilityMessageFactory::PRODUCT_ID => $productId,
            ProductVisibilityMessageFactory::SCOPE_ID => $scopeId
        ];

        $this->assertEquals($expected, $this->productVisibilityMessageFactory->createMessage($productVisibility));
    }

    public function testCreateMessageForAccountGroupProductVisibility()
    {
        $productId = 123;
        $productVisibilityId = 42;
        $scopeId = 1;
        
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => $scopeId]);

        /** @var AccountGroupProductVisibility $accountGroupProductVisibility */
        $accountGroupProductVisibility = $this->getEntity(
            AccountGroupProductVisibility::class,
            ['id' => $productVisibilityId]
        );
        $accountGroupProductVisibility->setProduct($product);
        $accountGroupProductVisibility->setScope($scope);

        $expected = [
            ProductVisibilityMessageFactory::ID => $productVisibilityId,
            ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountGroupProductVisibility::class,
            ProductVisibilityMessageFactory::PRODUCT_ID => $productId,
            ProductVisibilityMessageFactory::SCOPE_ID => $scopeId
        ];

        $this->assertEquals(
            $expected,
            $this->productVisibilityMessageFactory->createMessage($accountGroupProductVisibility)
        );
    }

    public function testCreateMessageForAccountProductVisibility()
    {
        $productId = 123;
        $productVisibilityId = 42;
        $scopeId = 1;

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => $scopeId]);

        /** @var AccountProductVisibility $accountProductVisibility */
        $accountProductVisibility = $this->getEntity(
            AccountProductVisibility::class,
            ['id' => $productVisibilityId]
        );
        $accountProductVisibility->setProduct($product);
        $accountProductVisibility->setScope($scope);

        $this->productVisibilityMessageFactory->createMessage($accountProductVisibility);

        $expected = [
            ProductVisibilityMessageFactory::ID => $productVisibilityId,
            ProductVisibilityMessageFactory::ENTITY_CLASS_NAME => AccountProductVisibility::class,
            ProductVisibilityMessageFactory::PRODUCT_ID => $productId,
            ProductVisibilityMessageFactory::SCOPE_ID => $scopeId
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
