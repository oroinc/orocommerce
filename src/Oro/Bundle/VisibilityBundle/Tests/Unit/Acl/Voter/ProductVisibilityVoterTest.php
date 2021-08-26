<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Acl\Voter\ProductVisibilityVoter;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ProductVisibilityVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $frontendHelper;

    /** @var ResolvedProductVisibilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $resolvedProductVisibilityProvider;

    /** @var ProductVisibilityVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->resolvedProductVisibilityProvider = $this->createMock(ResolvedProductVisibilityProvider::class);

        $container = TestContainerBuilder::create()
            ->add(
                'oro_visibility.provider.resolved_product_visibility_provider',
                $this->resolvedProductVisibilityProvider
            )
            ->getContainer($this);

        $this->voter = new ProductVisibilityVoter($this->doctrineHelper, $this->frontendHelper, $container);
        $this->voter->setClassName(Product::class);
    }

    /**
     * @dataProvider unsupportedAttributesDataProvider
     */
    public function testAbstainOnUnsupportedAttribute(array $attributes): void
    {
        $product = new Product();

        $token = $this->createMock(TokenInterface::class);

        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
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
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, $product, ['VIEW']));
    }

    /**
     * @dataProvider voteDataProvider
     */
    public function testVote(bool $isVisible, int $expected): void
    {
        $productId = 42;
        $product = new Product();
        ReflectionUtil::setId($product, $productId);

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
                'expected' => VoterInterface::ACCESS_GRANTED,
            ],
            'access denied' => [
                'isVisible' => false,
                'expected' => VoterInterface::ACCESS_DENIED,
            ],
        ];
    }
}
