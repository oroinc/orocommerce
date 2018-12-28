<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\EventListener\ProductPriceAttributesGridListener;
use Oro\Bundle\PricingBundle\Provider\PriceAttributePricesProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

class ProductPriceAttributesGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var PriceAttributePricesProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $priceAttributePricesProvider;

    /**
     * @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $datagrid;

    /**
     * @var ParameterBag
     */
    protected $parameterBag;

    /**
     * @var ArrayDatasource
     */
    protected $arrayDatasource;

    /**
     * @var ProductPriceAttributesGridListener
     */
    protected $productPriceAttributesGridListener;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->priceAttributePricesProvider = $this->createMock(PriceAttributePricesProvider::class);

        $this->parameterBag = new ParameterBag();
        $this->datagrid = $this->createMock(DatagridInterface::class);
        $this->datagrid->expects($this->any())->method('getParameters')->willReturn($this->parameterBag);
        $this->arrayDatasource = new ArrayDatasource();

        $this->productPriceAttributesGridListener = new ProductPriceAttributesGridListener(
            $this->doctrineHelper,
            $this->priceAttributePricesProvider
        );
    }

    /**
     * @expectedException \LogicException
     */
    public function testOnBuildBeforeWithNoPriceListId()
    {
        $datagridConfiguration = $this->createMock(DatagridConfiguration::class);
        $buildBeforeEvent = new BuildBefore($this->datagrid, $datagridConfiguration);
        $this->productPriceAttributesGridListener->onBuildBefore($buildBeforeEvent);
    }

    /**
     * @expectedException \LogicException
     */
    public function testOnBuildBeforeWithNoPriceList()
    {
        $this->parameterBag->set('price_list_id', 1);
        $this->doctrineHelper->expects($this->once())->method('getEntity')->willReturn(null);

        $datagridConfiguration = $this->createMock(DatagridConfiguration::class);
        $buildBeforeEvent = new BuildBefore($this->datagrid, $datagridConfiguration);
        $this->productPriceAttributesGridListener->onBuildBefore($buildBeforeEvent);
    }

    public function testOnBuildBefore()
    {
        $this->parameterBag->set('price_list_id', 1);

        $priceList = $this->createMock(PriceAttributePriceList::class);
        $priceList->expects($this->once())->method('getCurrencies')->willReturn(['USD']);

        $datagridConfig = $this->createMock(DatagridConfiguration::class);
        $datagridConfig->expects($this->at(0))->method('offsetGetByPath')
            ->with('[columns][USD]', [])->willReturn([]);

        $expectedColumn = [
            'label' => 'USD',
            'type' => 'twig',
            'template' => 'OroPricingBundle:Datagrid:Column/priceValue.html.twig',
            'frontend_type' => 'html',
        ];

        $datagridConfig->expects($this->at(1))->method('offsetSetByPath')
            ->with('[columns][USD]', $expectedColumn);

        $datagridConfig->expects($this->at(2))->method('offsetGetByPath')
            ->with('[sorters][columns][USD]', [])->willReturn([]);
        $datagridConfig->expects($this->at(3))->method('offsetSetByPath')
            ->with('[sorters][columns][USD]', ['data_name' => 'USD']);

        $this->doctrineHelper->expects($this->once())->method('getEntity')->willReturn($priceList);

        $buildBeforeEvent = new BuildBefore($this->datagrid, $datagridConfig);
        $this->productPriceAttributesGridListener->onBuildBefore($buildBeforeEvent);
    }

    /**
     * @expectedException \LogicException
     */
    public function testOnBuildAfterWithWrongDatasource()
    {
        $this->datagrid->expects($this->never())->method('getParameters')->willReturn($this->parameterBag);
        $this->datagrid->expects($this->once())->method('getDatasource')
            ->willReturn($this->createMock(OrmDatasource::class));

        $buildAfterEvent = new BuildAfter($this->datagrid);
        $this->productPriceAttributesGridListener->onBuildAfter($buildAfterEvent);
    }

    /**
     * @dataProvider onBuildAfterWithEmptyRequiredParamsProvider
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

        $buildAfterEvent = new BuildAfter($this->datagrid);
        $this->productPriceAttributesGridListener->onBuildAfter($buildAfterEvent);
    }

    /**
     * @dataProvider onBuildAfterWithEmptyRequiredEntitiesProvider
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

        $this->doctrineHelper->expects($this->any())
            ->method('getEntity')
            ->willReturnMap([
                [Product::class, 1, $product],
                [PriceAttributePriceList::class, 2, $priceList],
            ]);

        $buildAfterEvent = new BuildAfter($this->datagrid);
        $this->productPriceAttributesGridListener->onBuildAfter($buildAfterEvent);
    }

    public function testOnBuildAfter()
    {
        $this->parameterBag->set('product_id', 1);
        $this->parameterBag->set('price_list_id', 2);

        $product = $this->createMock(Product::class);
        $priceList = $this->createMock(PriceAttributePriceList::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntity')
            ->willReturnMap([
                [Product::class, 1, $product],
                [PriceAttributePriceList::class, 2, $priceList],
            ]);

        $this->datagrid->expects($this->once())->method('getDatasource')
            ->willReturn($this->arrayDatasource);

        $currencies = ['USD', 'EURO', 'GBP'];
        $priceList->expects($this->once())->method('getCurrencies')->willReturn($currencies);

        $usdPrice = $this->createPrice(110);
        $euroPrice = $this->createPrice(95);
        $gbpPrice = $this->createPrice(34);

        $this->priceAttributePricesProvider->expects($this->once())->method('getPricesWithUnitAndCurrencies')
            ->willReturn(
                [
                    'set' => ['USD' => $usdPrice, 'EURO' => $euroPrice, 'GBP' => $gbpPrice],
                ]
            );

        $buildAfterEvent = new BuildAfter($this->datagrid);
        $this->productPriceAttributesGridListener->onBuildAfter($buildAfterEvent);

        $this->assertEquals(
            [
                ['unit' => 'set', 'USD' => 110, 'EURO' => 95, 'GBP' => 34,],
            ],
            $this->arrayDatasource->getArraySource()
        );
    }

    /**
     * @return array
     */
    public function onBuildAfterWithEmptyRequiredParamsProvider()
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
    public function onBuildAfterWithEmptyRequiredEntitiesProvider()
    {
        return [
            [null, null],
            [null, new PriceAttributePriceList()],
            [new Product(), null],
        ];
    }

    /**
     * @param double $value
     * @return BaseProductPrice
     */
    protected function createPrice($value)
    {
        return (new BaseProductPrice())->setPrice((new Price())->setValue($value));
    }

    /**
     * @param $unitCode
     * @return ProductUnit
     */
    protected function createUnit($unitCode)
    {
        return (new ProductUnit())->setCode($unitCode);
    }
}
