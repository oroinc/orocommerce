<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Acl\Voter\ProductVoter;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductKitsByProductProvider;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ProductVoterTest extends TestCase
{
    private DoctrineHelper|MockObject $doctrineHelper;
    private ProductKitsByProductProvider|MockObject $productKitsByProductProvider;
    private ProductVoter $voter;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->productKitsByProductProvider = $this->createMock(ProductKitsByProductProvider::class);

        $this->voter = new ProductVoter($this->doctrineHelper, $this->productKitsByProductProvider);
        $this->voter->setClassName(Product::class);
    }

    /**
     * @dataProvider getPermissionForAttributeDataProvider
     */
    public function testGetPermissionForAttribute(
        Product $product,
        array $skus,
        int $expected
    ): void {
        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($product, false)
            ->willReturn($product->getId());

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityReference')
            ->with(Product::class, $product->getId())
            ->willReturn($product);

        $this->productKitsByProductProvider->expects(self::any())
            ->method('getRelatedProductKitsSku')
            ->with($product)
            ->willReturn($skus);

        $token = $this->createMock(TokenInterface::class);

        self::assertEquals($expected, $this->voter->vote($token, $product, [BasicPermission::DELETE]));
    }

    public function testNoSupportedAttributes(): void
    {
        $product = new Product();
        ReflectionUtil::setId($product, 1);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($product, false)
            ->willReturn($product->getId());

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityReference')
            ->with(Product::class, $product->getId());

        $this->productKitsByProductProvider->expects(self::never())
            ->method('getRelatedProductKitsSku')
            ->with($product);

        $token = $this->createMock(TokenInterface::class);

        $actual = $this->voter->vote(
            $token,
            $product,
            [BasicPermission::CREATE, BasicPermission::EDIT, BasicPermission::VIEW]
        );

        self::assertEquals(VoterInterface::ACCESS_ABSTAIN, $actual);
    }

    public function getPermissionForAttributeDataProvider(): array
    {
        $kitProduct = (new Product())->setType(Product::TYPE_KIT);
        ReflectionUtil::setId($kitProduct, 1);

        $noRelatedProduct = new Product();
        ReflectionUtil::setId($noRelatedProduct, 2);

        $relatedProduct = new Product();
        ReflectionUtil::setId($relatedProduct, 3);

        return [
            'No simple product' => [
                'product' => $kitProduct,
                'skus' => [],
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'No related to kit product' => [
                'product' => $noRelatedProduct,
                'skus' => [],
                'expected' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'Related to kit product' => [
                'product' => $relatedProduct,
                'skus' => ['KIT_SKU'],
                'expected' => VoterInterface::ACCESS_DENIED,
            ],
        ];
    }
}
