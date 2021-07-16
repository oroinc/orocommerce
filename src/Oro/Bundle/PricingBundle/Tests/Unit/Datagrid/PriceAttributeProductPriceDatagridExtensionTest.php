<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Datagrid;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmQueryConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\PricingBundle\Datagrid\PriceAttributeProductPriceDatagridExtension;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class PriceAttributeProductPriceDatagridExtensionTest extends AbstractProductsGridPricesExtensionTest
{
    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var PriceAttributeProductPriceDatagridExtension */
    protected $extension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->extension = new PriceAttributeProductPriceDatagridExtension(
            $this->priceListRequestHandler,
            $this->doctrineHelper,
            $this->selectedFieldsProvider,
            $this->aclHelper
        );
    }

    public function testProcessConfigsWhenNoAttributesWithCurrencies(): void
    {
        $this->mockAttributesWithCurrencies([], ['USD']);

        $this->datagridConfiguration
            ->expects($this->never())
            ->method('offsetAddToArrayByPath');
        $this->extension->processConfigs($this->datagridConfiguration);
    }

    private function mockAttributesWithCurrencies(array $attributesWithCurrencies, array $currencies): void
    {
        $this->mockPriceListCurrencies($this->createMock(PriceList::class), $currencies);

        $repository = $this->createMock(PriceAttributePriceListRepository::class);
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(PriceAttributePriceList::class)
            ->willReturn($repository);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($attributesWithCurrencies);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $repository->expects($this->once())
            ->method('getAttributesWithCurrenciesQueryBuilder')
            ->with($currencies)
            ->willReturn($queryBuilder);

        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);
    }

    public function testProcessConfigsWhenNoSelectedFields(): void
    {
        $this->assertColumnsAddedToConfig();

        $this->selectedFieldsProvider
            ->expects($this->once())
            ->method('getSelectedFields')
            ->with($this->datagridConfiguration, $this->datagridParameters)
            ->willReturn([]);

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->processConfigs($this->datagridConfiguration);
    }

    private function assertColumnsAddedToConfig(): void
    {
        $this->mockAttributesWithCurrencies([['id' => 1, 'name' => 'Sample Attribute', 'currency' => 'USD']], ['USD']);

        $this->datagridConfiguration
            ->expects($this->any())
            ->method('offsetAddToArrayByPath')
            ->withConsecutive(
                [
                    '[columns]',
                    [
                        'price_attribute_price_column_usd_1' => [
                            'label' => 'Sample Attribute (USD)',
                            'type' => 'twig',
                            'template' => '@OroPricing/Datagrid/Column/productPrice.html.twig',
                            'frontend_type' => 'html',
                            'renderable' => true,
                        ],
                    ],
                ],
                [
                    '[filters][columns]',
                    [
                        'price_attribute_price_column_usd_1' => [
                            'type' => 'price-attribute-product-price',
                            'data_name' => 'USD',
                        ],
                    ],
                ],
                [
                    '[sorters][columns]',
                    [
                        'price_attribute_price_column_usd_1' => ['data_name' => 'price_attribute_price_column_usd_1'],
                    ],
                ]
            );
    }

    public function testProcessConfigsWhenSelectedFieldPresent(): void
    {
        $this->assertColumnsAddedToConfig();

        $joinAlias = 'price_attribute_price_column_usd_1_table';
        $columnName = 'price_attribute_price_column_usd_1';
        $selectExpression = sprintf(
            "GROUP_CONCAT(DISTINCT CONCAT_WS('|', %s.value, IDENTITY(%s.unit)) SEPARATOR ';') as %s",
            $joinAlias,
            $joinAlias,
            $columnName
        );

        $joinExpression = sprintf(
            '%1$s.product = product.id AND %1$s.currency = \'USD\' AND %1$s.priceList = 1 AND %1$s.quantity = 1',
            $joinAlias
        );

        $this->assertColumnsAddedToQueryConfig(
            'price_attribute_price_column_usd_1',
            $selectExpression,
            $joinExpression
        );

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->processConfigs($this->datagridConfiguration);
    }

    private function assertColumnsAddedToQueryConfig(
        string $selectedField,
        string $selectExpression,
        string $joinExpression
    ): void {
        $this->selectedFieldsProvider
            ->expects($this->once())
            ->method('getSelectedFields')
            ->with($this->datagridConfiguration, $this->datagridParameters)
            ->willReturn([$selectedField]);

        $this->datagridConfiguration
            ->expects($this->any())
            ->method('getOrmQuery')
            ->willReturn($ormQueryConfiguration = $this->createMock(OrmQueryConfiguration::class));

        $ormQueryConfiguration
            ->expects($this->once())
            ->method('addSelect')
            ->with($selectExpression);

        $ormQueryConfiguration
            ->expects($this->once())
            ->method('addLeftJoin')
            ->with(
                PriceAttributeProductPrice::class,
                $selectedField . '_table',
                Expr\Join::WITH,
                $joinExpression
            );
    }

    public function testVisitResultWhenNoRelevantPriceColumns(): void
    {
        $resultsObject = $this->createMock(ResultsObject::class);
        $resultsObject
            ->expects($this->never())
            ->method('getData');

        $this->extension->visitResult($this->datagridConfiguration, $resultsObject);
    }

    /**
     * @dataProvider visitResultDataProvider
     */
    public function testVisitResult(string $rawPrices, array $unpackedPrices): void
    {
        $this->assertColumnsAddedToConfig();

        $this->selectedFieldsProvider
            ->expects($this->once())
            ->method('getSelectedFields')
            ->with($this->datagridConfiguration, $this->datagridParameters)
            ->willReturn([$selectedField = 'price_attribute_price_column_usd_1']);

        $this->datagridConfiguration
            ->expects($this->any())
            ->method('getOrmQuery')
            ->willReturn($this->createMock(OrmQueryConfiguration::class));

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->processConfigs($this->datagridConfiguration);

        $resultsObject = $this->createMock(ResultsObject::class);
        $resultsObject
            ->expects($this->once())
            ->method('getData')
            ->willReturn([$record = $this->createMock(ResultRecord::class)]);

        $record
            ->expects($this->once())
            ->method('getValue')
            ->with($selectedField)
            ->willReturn($rawPrices);

        $record
            ->expects($this->once())
            ->method('setValue')
            ->with($selectedField, $unpackedPrices);

        $this->extension->visitResult($this->datagridConfiguration, $resultsObject);
    }

    public function visitResultDataProvider(): array
    {
        return [
            '1 price' => [
                'rawPrices' => '10.25|item',
                'unpackedPrices' => [
                    [
                        'price' => Price::create('10.25', 'USD'),
                        'unitCode' => 'item',
                        'quantity' => 1,
                    ],
                ],
            ],
            '2 prices' => [
                'rawPrices' => '10.25|item;41|set',
                'unpackedPrices' => [
                    [
                        'price' => Price::create('10.25', 'USD'),
                        'unitCode' => 'item',
                        'quantity' => 1,
                    ],
                    [
                        'price' => Price::create('41', 'USD'),
                        'unitCode' => 'set',
                        'quantity' => 1,
                    ],
                ],
            ],
        ];
    }
}
