<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Acl\Voter\ProductVisibilityVoter;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProductVisibilityVoterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ProductVisibilityVoter */
    private $voter;

    /** @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $frontendHelper;

    /** @var ResolvedProductVisibilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $resolvedProductVisibilityProvider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->voter = new ProductVisibilityVoter($this->doctrineHelper);
        $this->voter->setClassName(Product::class);

        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->voter->setFrontendHelper($this->frontendHelper);

        $this->resolvedProductVisibilityProvider = $this->createMock(ResolvedProductVisibilityProvider::class);
        $this->voter->setResolvedProductVisibilityProvider($this->resolvedProductVisibilityProvider);
    }

    /**
     * @dataProvider unsupportedAttributesDataProvider
     */
    public function testAbstainOnUnsupportedAttribute($attributes): void
    {
        $product = new Product();

        /** @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject $token * */
        $token = $this->createMock(TokenInterface::class);

        $this->assertEquals(
            ProductVisibilityVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $product, $attributes)
        );
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

    public function testVoteWhenNotFrontend(): void
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->resolvedProductVisibilityProvider
            ->expects($this->never())
            ->method('isVisible');

        $token = $this->createMock(TokenInterface::class);
        $product = $this->createMock(Product::class);
        $this->assertEquals(ProductVisibilityVoter::ACCESS_ABSTAIN, $this->voter->vote($token, $product, ['VIEW']));
    }

    /**
     * @dataProvider voteDataProvider
     */
    public function testVote(bool $isVisible, int $expected): void
    {
        $productId = 42;
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($product, false)
            ->willReturn($productId);

        $this->resolvedProductVisibilityProvider
            ->expects($this->once())
            ->method('isVisible')
            ->with($productId)
            ->willReturn($isVisible);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals($expected, $this->voter->vote($token, $product, ['VIEW']));
    }

    public function voteDataProvider(): array
    {
        return [
            'access granted' => [
                'isVisible' => true,
                'expected' => ProductVisibilityVoter::ACCESS_GRANTED,
            ],
            'access denied' => [
                'isVisible' => false,
                'expected' => ProductVisibilityVoter::ACCESS_DENIED,
            ],
        ];
    }
}
