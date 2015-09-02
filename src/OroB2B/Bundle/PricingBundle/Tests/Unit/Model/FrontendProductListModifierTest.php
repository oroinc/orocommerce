<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use OroB2B\Bundle\PricingBundle\Model\FrontendProductListModifier;
use OroB2B\Bundle\PricingBundle\Model\PriceListTreeHandler;

class FrontendProductListModifierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PriceListTreeHandler
     */
    protected $priceListTreeHandler;

    /**
     * @var FrontendProductListModifier
     */
    protected $modifier;

    protected function setUp()
    {
        $this->tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $this->priceListTreeHandler = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\PriceListTreeHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->modifier = new FrontendProductListModifier($this->tokenStorage, $this->priceListTreeHandler);
    }

    public function testApplyPriceListLimitationsNotApplied()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|QueryBuilder $qb */
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceListTreeHandler->expects($this->never())
            ->method($this->anything());

        $this->modifier->applyPriceListLimitations($qb);
    }
}
