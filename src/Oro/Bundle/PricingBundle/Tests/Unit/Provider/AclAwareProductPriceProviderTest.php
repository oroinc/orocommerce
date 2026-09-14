<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\AclAwareProductPriceProvider;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProvider;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AclAwareProductPriceProviderTest extends TestCase
{
    private ProductPriceProvider&MockObject $inner;
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private TokenStorageInterface&MockObject $tokenStorage;
    private TokenInterface&MockObject $token;
    private ProductPriceScopeCriteriaInterface&MockObject $scopeCriteria;
    private AclAwareProductPriceProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->inner = $this->createMock(ProductPriceProvider::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $this->provider = new AclAwareProductPriceProvider(
            $this->inner,
            $this->authorizationChecker,
            $this->tokenStorage
        );
    }

    public function testReturnsEmptyResultsWhenAccessIsDenied(): void
    {
        $this->setUser(new User());

        $this->authorizationChecker
            ->expects(self::exactly(4))
            ->method('isGranted')
            ->with(
                BasicPermission::VIEW,
                'entity:' . ProductPrice::class
            )
            ->willReturn(false);

        $this->inner
            ->expects(self::never())
            ->method('getSupportedCurrencies');

        $this->inner
            ->expects(self::never())
            ->method('getPricesByScopeCriteriaAndProducts');

        $this->inner
            ->expects(self::never())
            ->method('getMatchedPrices');

        $this->inner
            ->expects(self::never())
            ->method('getMatchedProductPrices');

        self::assertSame(
            [],
            $this->provider->getSupportedCurrencies($this->scopeCriteria)
        );

        self::assertSame(
            [],
            $this->provider->getPricesByScopeCriteriaAndProducts(
                $this->scopeCriteria,
                [],
                []
            )
        );

        self::assertSame(
            [],
            $this->provider->getMatchedPrices(
                [],
                $this->scopeCriteria
            )
        );

        self::assertSame(
            [],
            $this->provider->getMatchedProductPrices(
                [],
                $this->scopeCriteria
            )
        );
    }

    public function testDelegatesCallsWhenAccessIsGranted(): void
    {
        $this->setUser(new User());

        $this->authorizationChecker
            ->expects(self::exactly(4))
            ->method('isGranted')
            ->with(
                BasicPermission::VIEW,
                'entity:' . ProductPrice::class
            )
            ->willReturn(true);

        $supportedCurrencies = ['USD'];
        $prices = ['prices'];
        $matchedPrices = ['matchedPrices'];
        $matchedProductPrices = ['matchedProductPrices'];

        $this->inner
            ->expects(self::once())
            ->method('getSupportedCurrencies')
            ->with($this->scopeCriteria)
            ->willReturn($supportedCurrencies);

        $this->inner
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with(
                $this->scopeCriteria,
                [],
                [],
                null
            )
            ->willReturn($prices);

        $this->inner
            ->expects(self::once())
            ->method('getMatchedPrices')
            ->with([], $this->scopeCriteria)
            ->willReturn($matchedPrices);

        $this->inner
            ->expects(self::once())
            ->method('getMatchedProductPrices')
            ->with([], $this->scopeCriteria)
            ->willReturn($matchedProductPrices);

        self::assertSame(
            $supportedCurrencies,
            $this->provider->getSupportedCurrencies($this->scopeCriteria)
        );

        self::assertSame(
            $prices,
            $this->provider->getPricesByScopeCriteriaAndProducts(
                $this->scopeCriteria,
                [],
                []
            )
        );

        self::assertSame(
            $matchedPrices,
            $this->provider->getMatchedPrices(
                [],
                $this->scopeCriteria
            )
        );

        self::assertSame(
            $matchedProductPrices,
            $this->provider->getMatchedProductPrices(
                [],
                $this->scopeCriteria
            )
        );
    }

    public function testChecksAccessForCustomerUser(): void
    {
        $this->setUser(new CustomerUser());

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(
                BasicPermission::VIEW,
                'entity:' . ProductPrice::class
            )
            ->willReturn(false);

        $this->inner
            ->expects(self::never())
            ->method('getSupportedCurrencies');

        self::assertSame(
            [],
            $this->provider->getSupportedCurrencies($this->scopeCriteria)
        );
    }

    public function testDelegatesForCustomerUserWhenAccessIsGranted(): void
    {
        $this->setUser(new CustomerUser());

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(
                BasicPermission::VIEW,
                'entity:' . ProductPrice::class
            )
            ->willReturn(true);

        $this->inner
            ->expects(self::once())
            ->method('getSupportedCurrencies')
            ->with($this->scopeCriteria)
            ->willReturn(['USD']);

        self::assertSame(
            ['USD'],
            $this->provider->getSupportedCurrencies($this->scopeCriteria)
        );
    }

    public function testDelegatesWithoutAccessCheckWhenTokenIsMissing(): void
    {
        $this->tokenStorage
            ->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        $this->authorizationChecker
            ->expects(self::never())
            ->method('isGranted');

        $this->inner
            ->expects(self::once())
            ->method('getSupportedCurrencies')
            ->with($this->scopeCriteria)
            ->willReturn(['USD']);

        self::assertSame(
            ['USD'],
            $this->provider->getSupportedCurrencies($this->scopeCriteria)
        );
    }

    private function setUser(AbstractUser $user): void
    {
        $this->token
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($this->token);
    }
}
