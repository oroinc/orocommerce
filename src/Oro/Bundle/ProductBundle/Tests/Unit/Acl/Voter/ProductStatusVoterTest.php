<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Acl\Voter;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Acl\Voter\ProductStatusVoter;
use Oro\Bundle\ProductBundle\Entity\Product;
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

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);

        $this->voter = new ProductStatusVoter($this->doctrineHelper, $this->frontendHelper);
        $this->voter->setClassName(Product::class);
    }

    /**
     * @dataProvider unsupportedAttributeDataProvider
     */
    public function testAbstainOnUnsupportedAttribute(string $attribute): void
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
            $this->voter->vote($token, $product, [$attribute])
        );
    }

    /**
     * @dataProvider supportedAttributeDataProvider
     */
    public function testAbstainOnUnsupportedClass(string $attribute): void
    {
        $object = new \stdClass();

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
            $this->voter->vote($token, $object, [$attribute])
        );
    }

    /**
     * @dataProvider supportedAttributeDataProvider
     */
    public function testAbstainForNotFrontendRequest(string $attribute): void
    {
        $object = new Product();

        $this->doctrineHelper->expects($this->never())
            ->method('getSingleEntityIdentifier');

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManagerForClass');

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $object, [$attribute])
        );
    }

    /**
     * @dataProvider supportedAttributeDataProvider
     */
    public function testGrantedOnExistingEnabledProduct(string $attribute): void
    {
        $product = new Product();
        $product->setStatus(Product::STATUS_ENABLED);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($product, false)
            ->willReturn(1);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(Product::class, 1)
            ->willReturn($product);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(Product::class)
            ->willReturn($em);

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $product, [$attribute])
        );
    }

    /**
     * @dataProvider supportedAttributeDataProvider
     */
    public function testDeniedOnExistingDisabledProduct(string $attribute): void
    {
        $product = new Product();
        $product->setStatus(Product::STATUS_DISABLED);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($product, false)
            ->willReturn(2);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(Product::class, 2)
            ->willReturn($product);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(Product::class)
            ->willReturn($em);

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $product, [$attribute])
        );
    }

    /**
     * @dataProvider supportedAttributeDataProvider
     */
    public function testAbstainOnNotFoundProduct(string $attribute): void
    {
        $product = new Product();
        $product->setStatus(Product::STATUS_ENABLED);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($product, false)
            ->willReturn(1);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(Product::class, 1)
            ->willReturn(null);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(Product::class)
            ->willReturn($em);

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $product, [$attribute])
        );
    }

    public function supportedAttributeDataProvider(): array
    {
        return [
            ['VIEW']
        ];
    }

    public function unsupportedAttributeDataProvider(): array
    {
        return [
            ['EDIT'],
            ['DELETE'],
            ['CREATE'],
            ['ASSIGN']
        ];
    }
}
