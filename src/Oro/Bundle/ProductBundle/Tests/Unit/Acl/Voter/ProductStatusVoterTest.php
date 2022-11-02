<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Acl\Voter\ProductStatusVoter;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ProductStatusVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $frontendHelper;

    /** @var ProductStatusVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);

        $this->voter = new ProductStatusVoter($this->doctrineHelper, $this->frontendHelper);
        $this->voter->setClassName(Product::class);
    }

    /**
     * @dataProvider unsupportedAttributesDataProvider
     */
    public function testAbstainOnUnsupportedAttribute(array $attributes)
    {
        $product = new Product();

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($product, false)
            ->willReturn(1);

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $product, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     */
    public function testAbstainOnUnsupportedClass(array $attributes)
    {
        $object = new \stdClass;

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn(1);

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $object, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     */
    public function testAbstainWithNonFrontendRequest(array $attributes)
    {
        $object = new Product();

        $this->doctrineHelper->expects($this->never())
            ->method('getSingleEntityIdentifier');

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepository');

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $object, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     */
    public function testAbstainWithoutFrontendHelper(array $attributes)
    {
        $object = new Product();

        $this->doctrineHelper->expects($this->never())
            ->method('getSingleEntityIdentifier');

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepository');

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $object, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     */
    public function testGrantedOnExistingEnabledProduct(array $attributes)
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

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $product, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     */
    public function testDeniedOnExistingDisabledProduct(array $attributes)
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

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $product, $attributes)
        );
    }

    /**
     * @dataProvider supportedAttributesDataProvider
     */
    public function testAbstainOnNotFoundProduct(array $attributes)
    {
        $product = new Product();
        ReflectionUtil::setId($product, 9999);
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

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $product, $attributes)
        );
    }

    public function supportedAttributesDataProvider(): array
    {
        return [
            [['VIEW']],
        ];
    }

    public function unsupportedAttributesDataProvider(): array
    {
        return [
            [['EDIT']],
            [['DELETE']],
            [['CREATE']],
            [['ASSIGN']],
        ];
    }
}
