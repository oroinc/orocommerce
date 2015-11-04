<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\HttpFoundation\ParameterBag;

use OroB2B\Bundle\PricingBundle\EventListener\ProductSelectPriceListAwareListener;
use OroB2B\Bundle\PricingBundle\Model\FrontendProductListModifier;
use OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;

class ProductSelectPriceListAwareListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductSelectPriceListAwareListener
     */
    protected $listener;

    /**
     * @var FrontendProductListModifier|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $modifier;

    /**
     * @var ProductSelectDBQueryEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    /**
     * @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $queryBuilder;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->modifier = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\FrontendProductListModifier')
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent')
            ->disableOriginalConstructor()->getMock();

        $this->queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ProductSelectPriceListAwareListener($this->modifier);
    }

    /**
     * @dataProvider onDBQueryDataProvider
     * @param bool $applicable
     * @param string $priceListParameter
     */
    public function testOnDBQuery($applicable, $priceListParameter)
    {
        $this->event->expects($this->once())
            ->method('getDataParameters')
            ->willReturn(new ParameterBag(['price_list' => $priceListParameter]));

        $this->event->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilder);

        if ($applicable) {
            $this->modifier->expects($this->once())
                ->method('applyPriceListLimitations')
                ->with($this->queryBuilder);
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
            'applicable' => [
                'applicable' => true,
                'priceListParameter' => ProductSelectPriceListAwareListener::DEFAULT_ACCOUNT_USER
            ],
            'not applicable' => [
                'applicable' => false,
                'priceListParameter' => 'another'
            ]
        ];
    }
}
