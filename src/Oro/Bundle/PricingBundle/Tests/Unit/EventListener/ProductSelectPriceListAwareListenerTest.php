<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\PricingBundle\EventListener\ProductSelectPriceListAwareListener;
use Oro\Bundle\PricingBundle\Model\FrontendProductListModifier;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;

class ProductSelectPriceListAwareListenerTest extends \PHPUnit_Framework_TestCase
{
    const PRICE_LIST_ID = 42;

    /**
     * @var ProductSelectPriceListAwareListener
     */
    protected $listener;

    /**
     * @var FrontendProductListModifier|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $modifier;

    /**
     * @var ProductDBQueryRestrictionEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    /**
     * @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $queryBuilder;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->modifier = $this->getMockBuilder('Oro\Bundle\PricingBundle\Model\FrontendProductListModifier')
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this->getMockBuilder('Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ProductSelectPriceListAwareListener($this->modifier, $this->registry);
    }

    /**
     * @dataProvider onDBQueryDataProvider
     * @param bool $applicable
     * @param array $parameters
     * @param bool|false $withPriceList
     */
    public function testOnDBQuery($applicable, array $parameters = [], $withPriceList = false)
    {
        $this->event->expects($this->any())
            ->method('getDataParameters')
            ->willReturn(new ParameterBag($parameters));

        $this->event->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilder);

        if ($applicable) {
            if ($withPriceList) {
                $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
                    ->disableOriginalConstructor()
                    ->getMock();

                $repository->expects($this->once())
                    ->method('find')
                    ->with(self::PRICE_LIST_ID)
                    ->willReturn(new PriceList());

                $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
                    ->disableOriginalConstructor()
                    ->getMock();

                $em->expects($this->once())
                    ->method('getRepository')
                    ->willReturn($repository);

                $this->registry->expects($this->once())
                    ->method('getManagerForClass')
                    ->with('OroPricingBundle:PriceList')
                    ->willReturn($em);
            } else {
                $this->modifier->expects($this->once())
                    ->method('applyPriceListLimitations')
                    ->with($this->queryBuilder);
            }

        } else {
            $this->modifier->expects($this->never())
                ->method('applyPriceListLimitations');
        }

        $this->listener->onDBQuery($this->event);
    }

    /**
     * @return array
     */
    public function onDBQueryDataProvider()
    {
        return [
            'applicable default account user' => [
                'applicable' => true,
                'parameters' => ['price_list' => ProductSelectPriceListAwareListener::DEFAULT_ACCOUNT_USER],
                'withPriceList' => false
            ],
            'applicable with price list' => [
                'applicable' => true,
                'parameters' => ['price_list' => self::PRICE_LIST_ID],
                'withPriceList' => true
            ],
            'not applicable without parameters' => [
                'applicable' => false
            ],
            'not applicable' => [
                'applicable' => false,
                'parameters' => ['another' => 123]
            ]
        ];
    }
}
