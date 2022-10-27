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

class ProductPriceAttributesGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var PriceAttributePricesProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $priceAttributePricesProvider;

    /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $datagrid;

    /** @var ParameterBag */
    private $parameterBag;

    /** @var ArrayDatasource */
    private $arrayDatasource;

    /** @var ProductPriceAttributesGridListener */
    private $productPriceAttributesGridListener;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->priceAttributePricesProvider = $this->createMock(PriceAttributePricesProvider::class);

        $this->parameterBag = new ParameterBag();
        $this->datagrid = $this->createMock(DatagridInterface::class);
        $this->datagrid->expects($this->any())
            ->method('getParameters')
            ->willReturn($this->parameterBag);
        $this->arrayDatasource = new ArrayDatasource();

        $this->productPriceAttributesGridListener = new ProductPriceAttributesGridListener(
            $this->doctrineHelper,
            $this->priceAttributePricesProvider
        );
    }

    public function testOnBuildBeforeWithNoPriceListId()
    {
        $this->expectException(\LogicException::class);
        $datagridConfiguration = $this->createMock(DatagridConfiguration::class);
        $buildBeforeEvent = new BuildBefore($this->datagrid, $datagridConfiguration);
        $this->productPriceAttributesGridListener->onBuildBefore($buildBeforeEvent);
    }

    public function testOnBuildBeforeWithNoPriceList()
    {
        $this->expectException(\LogicException::class);
        $this->parameterBag->set('price_list_id', 1);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->willReturn(null);

        $datagridConfiguration = $this->createMock(DatagridConfiguration::class);
        $buildBeforeEvent = new BuildBefore($this->datagrid, $datagridConfiguration);
        $this->productPriceAttributesGridListener->onBuildBefore($buildBeforeEvent);
    }

    public function testOnBuildBefore()
    {
        $this->parameterBag->set('price_list_id', 1);

        $priceList = $this->createMock(PriceAttributePriceList::class);
        $priceList->expects($this->once())
            ->method('getCurrencies')
            ->willReturn(['USD']);

        $datagridConfig = $this->createMock(DatagridConfiguration::class);
        $datagridConfig->expects($this->exactly(2))
            ->method('offsetGetByPath')
            ->willReturnMap([
                ['[columns][USD]', [], []],
                ['[sorters][columns][USD]', [], []]
            ]);
        $datagridConfig->expects($this->exactly(2))
            ->method('offsetSetByPath')
            ->withConsecutive(
                [
                    '[columns][USD]',
                    [
                        'label' => 'USD',
                        'type' => 'twig',
                        'template' => '@OroPricing/Datagrid/Column/priceValue.html.twig',
                        'frontend_type' => 'html'
                    ]
                ],
                ['[sorters][columns][USD]', ['data_name' => 'USD']]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->willReturn($priceList);

        $buildBeforeEvent = new BuildBefore($this->datagrid, $datagridConfig);
        $this->productPriceAttributesGridListener->onBuildBefore($buildBeforeEvent);
    }

    public function testOnBuildAfterWithWrongDatasource()
    {
        $this->expectException(\LogicException::class);
        $this->datagrid->expects($this->never())
            ->method('getParameters')
            ->willReturn($this->parameterBag);
        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($this->createMock(OrmDatasource::class));

        $buildAfterEvent = new BuildAfter($this->datagrid);
        $this->productPriceAttributesGridListener->onBuildAfter($buildAfterEvent);
    }

    /**
     * @dataProvider onBuildAfterWithEmptyRequiredParamsProvider
     */
    public function testOnBuildAfterWithEmptyRequiredParams(?int $productId, ?int $priceListId)
    {
        $this->expectException(\LogicException::class);
        $this->parameterBag->set('product_id', $productId);
        $this->parameterBag->set('price_list_id', $priceListId);

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($this->createMock(ArrayDatasource::class));

        $buildAfterEvent = new BuildAfter($this->datagrid);
        $this->productPriceAttributesGridListener->onBuildAfter($buildAfterEvent);
    }

    /**
     * @dataProvider onBuildAfterWithEmptyRequiredEntitiesProvider
     */
    public function testOnBuildAfterWithEmptyRequiredEntities(?Product $product, ?PriceAttributePriceList $priceList)
    {
        $this->expectException(\LogicException::class);
        $this->parameterBag->set('product_id', 1);
        $this->parameterBag->set('price_list_id', 2);

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
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

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($this->arrayDatasource);

        $currencies = ['USD', 'EURO', 'GBP'];
        $priceList->expects($this->once())
            ->method('getCurrencies')
            ->willReturn($currencies);

        $usdPrice = $this->createPrice(110);
        $euroPrice = $this->createPrice(95);
        $gbpPrice = $this->createPrice(34);

        $this->priceAttributePricesProvider->expects($this->once())
            ->method('getPricesWithUnitAndCurrencies')
            ->willReturn(['set' => ['USD' => $usdPrice, 'EURO' => $euroPrice, 'GBP' => $gbpPrice]]);

        $buildAfterEvent = new BuildAfter($this->datagrid);
        $this->productPriceAttributesGridListener->onBuildAfter($buildAfterEvent);

        $this->assertEquals(
            [
                ['unit' => 'set', 'USD' => 110, 'EURO' => 95, 'GBP' => 34,],
            ],
            $this->arrayDatasource->getArraySource()
        );
    }

    public function onBuildAfterWithEmptyRequiredParamsProvider(): array
    {
        return [
            [null, null],
            [null, 1],
            [1, null],
        ];
    }

    public function onBuildAfterWithEmptyRequiredEntitiesProvider(): array
    {
        return [
            [null, null],
            [null, new PriceAttributePriceList()],
            [new Product(), null],
        ];
    }

    private function createPrice(float $value): BaseProductPrice
    {
        return (new BaseProductPrice())->setPrice((new Price())->setValue($value));
    }
}
