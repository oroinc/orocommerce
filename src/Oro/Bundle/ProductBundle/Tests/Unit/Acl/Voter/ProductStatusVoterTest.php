<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Acl\Voter\ProductStatusVoter;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProductStatusVoterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $frontendHelper;

    /**
     * @var ProductStatusVoter
     */
    private $voter;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);

        $this->voter = new ProductStatusVoter($this->doctrineHelper);
        $this->voter->setClassName(Product::class);
    }

    /**
     * @dataProvider unsupportedAttributesDataProvider
     * @param array $attributes
     */
    public function testAbstainOnUnsupportedAttribute($attributes)
    {
        $product = new Product();

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($product, false)
            ->willReturn(1);

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->voter->setFrontendHelper($this->frontendHelper);

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            ProductStatusVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $product, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     * @param array $attributes
     */
    public function testAbstainOnUnsupportedClass($attributes)
    {
        $object = new \stdClass;

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn(1);

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->voter->setFrontendHelper($this->frontendHelper);

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            ProductStatusVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $object, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     * @param array $attributes
     */
    public function testAbstainWithNonFrontendRequest($attributes)
    {
        $object = new Product();

        $this->doctrineHelper->expects($this->never())
            ->method('getSingleEntityIdentifier');

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepository');

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->voter->setFrontendHelper($this->frontendHelper);

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            ProductStatusVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $object, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     * @param array $attributes
     */
    public function testAbstainWithoutFrontendHelper($attributes)
    {
        $object = new Product();

        $this->doctrineHelper->expects($this->never())
            ->method('getSingleEntityIdentifier');

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepository');

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            ProductStatusVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $object, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     * @param array $attributes
     */
    public function testGrantedOnExistingEnabledProduct($attributes)
    {
        $product = new Product();
        $product->setStatus(Product::STATUS_ENABLED);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($product, false)
            ->willReturn(1);

        $repository = $this->createMock(ProductRepository::class);

        $repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->voter->setFrontendHelper($this->frontendHelper);

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            ProductStatusVoter::ACCESS_GRANTED,
            $this->voter->vote($token, $product, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     * @param array $attributes
     */
    public function testDeniedOnExistingDisabledProduct($attributes)
    {
        $product = new Product();
        $product->setStatus(Product::STATUS_DISABLED);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($product, false)
            ->willReturn(2);

        $repository = $this->createMock(ProductRepository::class);

        $repository->expects($this->once())
            ->method('find')
            ->with(2)
            ->willReturn($product);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->voter->setFrontendHelper($this->frontendHelper);

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            ProductStatusVoter::ACCESS_DENIED,
            $this->voter->vote($token, $product, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     * @param array $attributes
     */
    public function testAbstainOnNotFoundProduct($attributes)
    {
        $product = $this->getEntity(Product::class, ['id' => 9999]);
        $product->setStatus(Product::STATUS_ENABLED);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($product, false)
            ->willReturn(9999);

        $repository = $this->createMock(ProductRepository::class);

        $repository->expects($this->once())
            ->method('find')
            ->with(9999)
            ->willReturn(null);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->voter->setFrontendHelper($this->frontendHelper);

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            ProductStatusVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $product, $attributes)
        );
    }

    /**
     * @return array
     */
    public function supportedAttributesDataProvider()
    {
        return [
            [['VIEW']],
        ];
    }

    /**
     * @return array
     */
    public function unsupportedAttributesDataProvider()
    {
        return [
            [['EDIT']],
            [['DELETE']],
            [['CREATE']],
            [['ASSIGN']],
        ];
    }
}
