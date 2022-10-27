<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\FrontendProductListModifier;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FrontendProductListModifierTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CombinedPriceListTreeHandler
     */
    protected $priceListTreeHandler;

    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $featureChecker;

    /**
     * @var FrontendProductListModifier
     */
    protected $modifier;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->priceListTreeHandler = $this->createMock(CombinedPriceListTreeHandler::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->modifier = new FrontendProductListModifier($this->tokenStorage, $this->priceListTreeHandler);
    }

    public function testApplyPriceListLimitationsFeatureDisabled()
    {
        $this->tokenStorage->expects($this->never())->method('getToken');
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        /** @var \PHPUnit\Framework\MockObject\MockObject|QueryBuilder $qb */
        $qb = $this->createMock(QueryBuilder::class);

        $this->modifier->setFeatureChecker($this->featureChecker);
        $this->modifier->addFeature('feature1');
        $this->modifier->applyPriceListLimitations($qb);
    }

    public function testApplyPriceListLimitationsNotApplied()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        /** @var \PHPUnit\Framework\MockObject\MockObject|QueryBuilder $qb */
        $qb = $this->createMock(QueryBuilder::class);

        $this->priceListTreeHandler->expects($this->never())
            ->method($this->anything());

        $this->modifier->setFeatureChecker($this->featureChecker);
        $this->modifier->addFeature('feature1');
        $this->modifier->applyPriceListLimitations($qb);
    }
}
