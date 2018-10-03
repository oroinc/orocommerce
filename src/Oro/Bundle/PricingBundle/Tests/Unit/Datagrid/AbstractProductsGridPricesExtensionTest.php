<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;

abstract class AbstractProductsGridPricesExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var PriceListRequestHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $priceListRequestHandler;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var SelectedFieldsProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $selectedFieldsProvider;

    /** @var DatagridConfiguration|\PHPUnit_Framework_MockObject_MockObject */
    protected $datagridConfiguration;

    /** @var ParameterBag|\PHPUnit_Framework_MockObject_MockObject */
    protected $datagridParameters;

    /** @var AbstractExtension */
    protected $extension;

    protected function setUp()
    {
        $this->priceListRequestHandler = $this->createMock(PriceListRequestHandler::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->selectedFieldsProvider = $this->createMock(SelectedFieldsProviderInterface::class);

        $this->datagridConfiguration = $this->createMock(DatagridConfiguration::class);
        $this->datagridParameters = $this->createMock(ParameterBag::class);
    }

    public function testGetPriority(): void
    {
        self::assertEquals(10, $this->extension->getPriority());
    }

    public function testIsApplicable(): void
    {
        $this->datagridParameters
            ->expects(self::once())
            ->method('get')
            ->with(ParameterBag::DATAGRID_MODES_PARAMETER)
            ->willReturn([]);

        $this->mockDatagridName('products-grid');

        $this->extension->setParameters($this->datagridParameters);
        self::assertTrue($this->extension->isApplicable($this->datagridConfiguration));
    }

    public function testIsApplicableWhenAnotherDatagrid(): void
    {
        $this->mockDatagridName('unsupported-grid');

        $this->extension->setParameters($this->datagridParameters);
        self::assertFalse($this->extension->isApplicable($this->datagridConfiguration));
    }

    public function testIsApplicableWhenAlreadyApplied(): void
    {
        $this->mockPriceListCurrencies(null, []);

        $this->datagridConfiguration
            ->expects(self::never())
            ->method('getName');

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->processConfigs($this->datagridConfiguration);
        self::assertFalse($this->extension->isApplicable($this->datagridConfiguration));
    }

    /**
     * @param null|PriceList $priceList
     * @param array $currencies
     */
    protected function mockPriceListCurrencies(?PriceList $priceList, array $currencies): void
    {
        $this->priceListRequestHandler
            ->expects(self::once())
            ->method('getPriceList')
            ->willReturn($priceList);

        $this->priceListRequestHandler
            ->expects(self::exactly($priceList !== null ? 1 : 0))
            ->method('getPriceListSelectedCurrencies')
            ->with($priceList)
            ->willReturn($currencies);
    }

    /**
     * @param string $name
     */
    protected function mockDatagridName(string $name): void
    {
        $this->datagridConfiguration
            ->expects(self::once())
            ->method('getName')
            ->willReturn($name);
    }

    public function testProcessConfigsWhenNoPriceListNoCurrencies(): void
    {
        $this->mockPriceListCurrencies(null, []);

        $this->datagridConfiguration
            ->expects(self::never())
            ->method('offsetAddToArrayByPath');
        $this->extension->processConfigs($this->datagridConfiguration);
    }

    public function testProcessConfigsWhenNoCurrencies(): void
    {
        $this->mockPriceListCurrencies($this->createMock(PriceList::class), []);

        $this->datagridConfiguration
            ->expects(self::never())
            ->method('offsetAddToArrayByPath');
        $this->extension->processConfigs($this->datagridConfiguration);
    }
}
