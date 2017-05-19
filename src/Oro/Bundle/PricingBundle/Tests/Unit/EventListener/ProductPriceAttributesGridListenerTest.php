<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\EventListener\ProductPriceAttributesGridListener;
use Oro\Bundle\PricingBundle\Provider\PriceAttributePricesProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

use Symfony\Component\Translation\TranslatorInterface;

class ProductPriceAttributesGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $helper;

    /**
     * @var PriceAttributePricesProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $provider;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var BuildBefore|\PHPUnit_Framework_MockObject_MockObject $event
     */
    protected $buildBeforeEvent;

    /**
     * @var BuildAfter|\PHPUnit_Framework_MockObject_MockObject $event
     */
    protected $buildAfterEvent;

    /**
     * @var  DatagridInterface|\PHPUnit_Framework_MockObject_MockObject $datagrid
     */
    protected $datagrid;

    /**
     * @var  ParameterBag
     */
    protected $parameterBag;

    /**
     * @var  ArrayDatasource
     */
    protected $arrayDatasource;

    /**
     * @var ProductPriceAttributesGridListener
     */
    protected $productPriceAttributesGridListener;

    protected function setUp()
    {
        $this->prepareEventsMocks();
        $this->helper = $this->createMock(DoctrineHelper::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->provider = $this->createMock(PriceAttributePricesProvider::class);
        $this->arrayDatasource = new ArrayDatasource();

        $this->productPriceAttributesGridListener = new ProductPriceAttributesGridListener(
            $this->helper,
            $this->provider,
            $this->translator
        );
    }

    /**
     * @expectedException \LogicException
     */
    public function testOnBuildBeforeWithNoPriceListId()
    {
        $this->productPriceAttributesGridListener->onBuildBefore($this->buildBeforeEvent);
    }

    /**
     * @expectedException \LogicException
     */
    public function testOnBuildBeforeWithNoPriceList()
    {
        $this->parameterBag->set('price_list_id', 1);
        $this->helper->expects($this->once())->method('getEntity')->willReturn(null);

        $this->productPriceAttributesGridListener->onBuildBefore($this->buildBeforeEvent);
    }

    public function testOnBuildBefore()
    {
        $this->parameterBag->set('price_list_id', 1);

        $priceList = $this->createMock(PriceAttributePriceList::class);
        $priceList->expects($this->once())->method('getCurrencies')->willReturn(['USD']);

        $config = $this->createMock(DatagridConfiguration::class);

        $config->expects($this->at(0))->method('offsetGetByPath')
            ->with('[columns][USD]', [])->willReturn([]);
        $config->expects($this->at(1))->method('offsetSetByPath')
            ->with('[columns][USD]', ['label' => 'USD']);

        $config->expects($this->at(2))->method('offsetGetByPath')
            ->with('[sorters][columns][USD]', [])->willReturn([]);
        $config->expects($this->at(3))->method('offsetSetByPath')
            ->with('[sorters][columns][USD]', ['data_name' => 'USD']);

        $this->buildBeforeEvent->expects($this->once())->method('getConfig')->willReturn($config);
        $this->helper->expects($this->once())->method('getEntity')->willReturn($priceList);

        $this->productPriceAttributesGridListener->onBuildBefore($this->buildBeforeEvent);
    }

    /**
     * @expectedException \LogicException
     */
    public function testOnBuildAfterWithWrongDatasource()
    {
        $this->datagrid->expects($this->never())->method('getParameters')->willReturn($this->parameterBag);
        $this->datagrid->expects($this->once())->method('getDatasource')
            ->willReturn($this->createMock(OrmDatasource::class));

        $this->productPriceAttributesGridListener->onBuildAfter($this->buildAfterEvent);
    }

    /**
     * @dataProvider parameterBagParamsProvider
     * @param null|int $productId
     * @param null|int $priceListId
     * @expectedException \LogicException
     */
    public function testOnBuildAfterWithEmptyRequiredParams($productId, $priceListId)
    {
        $this->parameterBag->set('product_id', $productId);
        $this->parameterBag->set('price_list_id', $priceListId);

        $this->datagrid->expects($this->once())->method('getDatasource')
            ->willReturn($this->createMock(ArrayDatasource::class));

        $this->productPriceAttributesGridListener->onBuildAfter($this->buildAfterEvent);
    }

    /**
     * @dataProvider testEntitiesProvider
     * @param null|Product $product
     * @param null|PriceAttributePriceList $priceList
     * @expectedException \LogicException
     */
    public function testOnBuildAfterWithEmptyRequiredEntities($product, $priceList)
    {
        $this->parameterBag->set('product_id', 1);
        $this->parameterBag->set('price_list_id', 2);

        $this->datagrid->expects($this->once())->method('getDatasource')
            ->willReturn($this->arrayDatasource);

        $this->helper->expects($this->at(0))->method('getEntity')
            ->with(Product::class, 1)->willReturn($product);

        $this->helper->expects($this->at(1))->method('getEntity')
            ->with(PriceAttributePriceList::class, 2)->willReturn($priceList);

        $this->productPriceAttributesGridListener->onBuildAfter($this->buildAfterEvent);
    }

    public function testOnBuildAfter()
    {
        $this->parameterBag->set('product_id', 1);
        $this->parameterBag->set('price_list_id', 2);

        $product = $this->createMock(Product::class);
        $priceList = $this->createMock(PriceAttributePriceList::class);

        $this->helper->expects($this->at(0))->method('getEntity')
            ->with(Product::class, 1)->willReturn($product);

        $this->helper->expects($this->at(1))->method('getEntity')
            ->with(PriceAttributePriceList::class, 2)->willReturn($priceList);

        $this->datagrid->expects($this->once())->method('getDatasource')
            ->willReturn($this->arrayDatasource);

        $currencies = ['USD', 'EURO', 'GBP'];
        $priceList->expects($this->once())->method('getCurrencies')->willReturn($currencies);

        $usdPrice = $this->preparePrice(110);
        $euroPrice = $this->preparePrice(95);
        $gbpPrice = $this->preparePrice(34);

        $this->provider->expects($this->once())->method('getPrices')
            ->willReturn(
                [
                    'set' => ['USD' => $usdPrice, 'EURO' => $euroPrice, 'GBP' => $gbpPrice],
                ]
            );

        $this->translator->expects($this->never())->method('trans');

        $this->productPriceAttributesGridListener->onBuildAfter($this->buildAfterEvent);

        $this->assertEquals(
            [
                ['unit' => 'Set', 'USD' => 110, 'EURO' => 95, 'GBP' => 34,],
            ],
            $this->arrayDatasource->getArraySource()
        );
    }

    /**
     * @return array
     */
    public function parameterBagParamsProvider()
    {
        return [
            [null, null],
            [null, 1],
            [1, null],
        ];
    }

    /**
     * @return array
     */
    public function testEntitiesProvider()
    {
        return [
            [null, null],
            [null, new PriceAttributePriceList()],
            [new Product(), null]
        ];
    }

    protected function prepareEventsMocks()
    {
        $this->parameterBag = new ParameterBag();
        $this->datagrid = $this->createMock(DatagridInterface::class);
        $this->datagrid->expects($this->any())->method('getParameters')->willReturn($this->parameterBag);

        $this->buildBeforeEvent = $this->createMock(BuildBefore::class);
        $this->buildAfterEvent = $this->createMock(BuildAfter::class);

        $this->buildBeforeEvent->expects($this->any())->method('getDatagrid')->willReturn($this->datagrid);
        $this->buildAfterEvent->expects($this->any())->method('getDatagrid')->willReturn($this->datagrid);
    }

    /**
     * @param double $value
     * @return BaseProductPrice
     */
    protected function preparePrice($value)
    {
        return (new BaseProductPrice())->setPrice((new Price())->setValue($value));
    }

    /**
     * @param $unitCode
     * @return ProductUnit
     */
    protected function prepareUnit($unitCode)
    {
        return(new ProductUnit())->setCode($unitCode);
    }
}
