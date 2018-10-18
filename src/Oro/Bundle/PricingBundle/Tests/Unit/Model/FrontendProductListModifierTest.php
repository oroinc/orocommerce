<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PricingBundle\Model\FrontendProductListModifier;
use Oro\Bundle\PricingBundle\Model\PriceListTreeHandler;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FrontendProductListModifierTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PriceListTreeHandler
     */
    protected $priceListTreeHandler;

    /**
     * @var FrontendProductListModifier
     */
    protected $modifier;

    protected function setUp()
    {
        $this->tokenStorage = $this
            ->createMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $this->priceListTreeHandler = $this->getMockBuilder('Oro\Bundle\PricingBundle\Model\PriceListTreeHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->modifier = new FrontendProductListModifier($this->tokenStorage, $this->priceListTreeHandler);
    }

    public function testApplyPriceListLimitationsNotApplied()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|QueryBuilder $qb */
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceListTreeHandler->expects($this->never())
            ->method($this->anything());

        $this->modifier->applyPriceListLimitations($qb);
    }
}
