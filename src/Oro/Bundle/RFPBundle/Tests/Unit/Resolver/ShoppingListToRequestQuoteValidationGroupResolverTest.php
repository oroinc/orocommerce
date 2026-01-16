<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\Resolver;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\RFPBundle\Resolver\ShoppingListToRequestQuoteValidationGroupResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class ShoppingListToRequestQuoteValidationGroupResolverTest extends TestCase
{
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private FeatureChecker&MockObject $featureChecker;
    private ShoppingListToRequestQuoteValidationGroupResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->resolver = new ShoppingListToRequestQuoteValidationGroupResolver($this->authorizationChecker);
        $this->resolver->setFeatureChecker($this->featureChecker);
        $this->resolver->addFeature('rfp_frontend');
    }

    public function testGetType(): void
    {
        self::assertSame(
            ShoppingListToRequestQuoteValidationGroupResolver::TYPE,
            $this->resolver->getType()
        );
    }

    public function testGetValidationGroupName(): void
    {
        self::assertSame('datagrid_line_items_data_for_rfq', $this->resolver->getValidationGroupName());
    }

    public function testIsApplicableReturnsFalseWhenFeatureDisabled(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('rfp_frontend', null)
            ->willReturn(false);
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertFalse($this->resolver->isApplicable());
    }

    public function testIsApplicableReturnsFalseWhenAclDenied(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('rfp_frontend', null)
            ->willReturn(true);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('oro_rfp_frontend_request_create')
            ->willReturn(false);

        self::assertFalse($this->resolver->isApplicable());
    }

    public function testIsApplicableReturnsTrueWhenChecksPassed(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('rfp_frontend', null)
            ->willReturn(true);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('oro_rfp_frontend_request_create')
            ->willReturn(true);

        self::assertTrue($this->resolver->isApplicable());
    }
}
