<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\PromotionRepository;
use Oro\Bundle\PromotionBundle\Provider\AvailablePromotionProvider;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class AvailablePromotionProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var AvailablePromotionProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->provider = new AvailablePromotionProvider(
            $this->doctrine,
            $this->tokenAccessor
        );
    }

    public function testGetAvailablePromotionsWhenNoOrganizationInSecurityContext(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn(null);

        $this->doctrine->expects(self::never())
            ->method(self::anything());

        self::assertSame([], $this->provider->getAvailablePromotions(['key' => 'val']));
    }

    public function testGetAvailablePromotions(): void
    {
        $organizationId = 123;
        $organization = $this->createMock(Organization::class);
        $organization->expects(self::once())
            ->method('getId')
            ->willReturn($organizationId);

        $criteria = $this->createMock(ScopeCriteria::class);
        $currency = 'USD';
        $contextData = [
            ContextDataConverterInterface::CRITERIA => $criteria,
            ContextDataConverterInterface::CURRENCY => $currency
        ];
        $promotions = [$this->createMock(Promotion::class)];

        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $repository = $this->createMock(PromotionRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(Promotion::class)
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('getAvailablePromotions')
            ->with(self::identicalTo($criteria), $currency, $organizationId)
            ->willReturn($promotions);

        self::assertSame($promotions, $this->provider->getAvailablePromotions($contextData));
    }
}
