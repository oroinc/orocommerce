<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Datagrid;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmQueryConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Datagrid\ProductPriceDatagridExtension;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardOutputResultModifier;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductPriceDatagridExtensionTest extends AbstractProductsGridPricesExtensionTest
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var ProductPriceDatagridExtension */
    protected $extension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->extension = new ProductPriceDatagridExtension(
            $this->priceListRequestHandler,
            $this->doctrineHelper,
            $this->selectedFieldsProvider,
            $this->translator,
            $this->authorizationChecker
        );
    }

    public function testIsApplicableWhenFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        $this->extension->setFeatureChecker($this->featureChecker);
        $this->extension->addFeature('feature1');
        $this->extension->setParameters($this->datagridParameters);
        $this->assertFalse($this->extension->isApplicable($this->datagridConfiguration));
    }

    public function testIsApplicable(): void
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        $this->extension->setFeatureChecker($this->featureChecker);
        $this->extension->addFeature('feature1');

        parent::testIsApplicable();
    }

    public function testIsApplicableWhenAlreadyApplied(): void
    {
        $this->mockAuthorizationChecker(true);

        parent::testIsApplicableWhenAlreadyApplied();
    }

    private function mockAuthorizationChecker(bool $isViewGranted): void
    {
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', 'entity:Oro\Bundle\PricingBundle\Entity\ProductPrice')
            ->willReturn($isViewGranted);
    }

    public function testProcessConfigsWhenNoPriceListNoCurrencies(): void
    {
        $this->mockAuthorizationChecker(true);

        parent::testProcessConfigsWhenNoPriceListNoCurrencies();
    }

    public function testProcessConfigsWhenNoCurrencies(): void
    {
        $this->mockAuthorizationChecker(true);

        parent::testProcessConfigsWhenNoPriceListNoCurrencies();
    }

    public function testProcessConfigsWhenPermissionViewForbidden(): void
    {
        $this->mockAuthorizationChecker(false);

        $this->datagridConfiguration->expects(self::never())
            ->method('offsetAddToArrayByPath');
        $this->extension->processConfigs($this->datagridConfiguration);
    }

    public function testProcessConfigsWhenNoSelectedFields(): void
    {
        $this->mockAuthorizationChecker(true);

        $this->assertColumnsAddedToConfig();

        $this->selectedFieldsProvider->expects(self::once())
            ->method('getSelectedFields')
            ->with($this->datagridConfiguration, $this->datagridParameters)
            ->willReturn([]);

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->processConfigs($this->datagridConfiguration);
    }

    private function assertColumnsAddedToConfig(): void
    {
        $priceList = $this->createMock(PriceList::class);
        $priceList->expects(self::any())
            ->method('getId')
            ->willReturn(1);

        $this->mockPriceListCurrencies($priceList, ['USD']);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(ProductUnit::class)
            ->willReturn($productUnitRepo = $this->createMock(ProductUnitRepository::class));

        $productUnitRepo->expects(self::once())
            ->method('getAllUnitCodes')
            ->willReturn(['item']);

        $this->mockTranslator();

        $this->datagridConfiguration->expects(self::any())
            ->method('offsetAddToArrayByPath')
            ->withConsecutive(
                [
                    '[columns]',
                    [
                        'price_column_usd' => [
                            'label' => 'oro.pricing.productprice.price_in_USD',
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
                        'price_column_usd' => ['type' => 'product-price', 'data_name' => 'USD'],
                    ],
                ],
                [
                    '[sorters][columns]',
                    [
                        'price_column_usd' => ['data_name' => 'price_column_usd'],
                    ],
                ],
                // Column, filter, sorter configs for price for currency and unit pair.
                [
                    '[columns]',
                    [
                        'price_column_usd_item' => [
                            'label' => 'oro.pricing.productprice.price_item_in_USD',
                            'type' => 'twig',
                            'template' => '@OroPricing/Datagrid/Column/productPrice.html.twig',
                            'frontend_type' => 'html',
                            'renderable' => false,
                        ],
                    ],
                ],
                [
                    '[filters][columns]',
                    [
                        'price_column_usd_item' => [
                            'type' => 'number-range',
                            'data_name' => 'price_column_usd_item__value',
                            'renderable' => false
                        ],
                    ],
                ],
                [
                    '[sorters][columns]',
                    [
                        'price_column_usd_item' => ['data_name' => 'price_column_usd_item'],
                    ],
                ]
            );
    }

    private function mockTranslator(): void
    {
        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function (string $id, ?array $parameters = []) {
                return str_replace(array_keys($parameters), array_values($parameters), $id);
            });
    }

    /**
     * @dataProvider processConfigsWhenSelectedFieldPresentDataProvider
     */
    public function testProcessConfigsWhenSelectedFieldPresent(
        string $selectedField,
        string $selectedTable,
        bool $showTierPrices,
        array $selectExpressions,
        string $joinExpression
    ): void {
        $this->mockAuthorizationChecker(true);

        $this->assertColumnsAddedToConfig();

        $this->assertColumnsAddedToQueryConfig(
            $selectedField,
            $selectedTable,
            $showTierPrices,
            $selectExpressions,
            $joinExpression
        );

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->processConfigs($this->datagridConfiguration);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processConfigsWhenSelectedFieldPresentDataProvider(): array
    {
        $select = "GROUP_CONCAT(DISTINCT CONCAT_WS('|', %s.value, CAST(%s.quantity as string), %s) SEPARATOR ';') " .
            'as %s';

        $joinAlias1 = 'price_column_usd_item_table';
        $column1 = 'price_column_usd_item';
        $joinAlias2 = 'price_column_usd_table';
        $column2 = 'price_column_usd';

        $unitCodePart = sprintf('IDENTITY(%s.unit)', $joinAlias2);

        return [
            'column with price / unit pair, showing tier prices enabled, filter selected' => [
                'selectedField' => 'price_column_usd_item__value',
                'selectedTable' => 'price_column_usd_item_table',
                'showTierPrices' => true,
                'select' => [
                    'price_column_usd_item_table.value as price_column_usd_item__value',
                    sprintf($select, $joinAlias1, $joinAlias1, "''", $column1)
                ],
                'joinExpression' => sprintf(
                    "%s.product = product.id AND %s.currency = 'USD' AND %s.priceList = 1 AND %s.unit = 'item'",
                    $joinAlias1,
                    $joinAlias1,
                    $joinAlias1,
                    $joinAlias1
                ),
            ],
            'column with price / unit pair, showing tier prices disabled, filter selected' => [
                'selectedField' => 'price_column_usd_item__value',
                'selectedTable' => 'price_column_usd_item_table',
                'showTierPrices' => false,
                'select' => [
                    'price_column_usd_item_table.value as price_column_usd_item__value',
                    sprintf($select, $joinAlias1, $joinAlias1, "''", $column1)
                ],
                'joinExpression' => sprintf(
                    "%s.product = product.id AND %s.currency = 'USD' AND "
                    . "%s.priceList = 1 AND %s.quantity = 1 AND %s.unit = 'item'",
                    $joinAlias1,
                    $joinAlias1,
                    $joinAlias1,
                    $joinAlias1,
                    $joinAlias1
                ),
            ],
            'column with price / unit pair, showing tier prices enabled' => [
                'selectedField' => $column1,
                'selectedTable' => $column1 . '_table',
                'showTierPrices' => true,
                'select' => [
                    'price_column_usd_item_table.value as price_column_usd_item__value',
                    sprintf($select, $joinAlias1, $joinAlias1, "''", $column1)
                ],
                'joinExpression' => sprintf(
                    "%s.product = product.id AND %s.currency = 'USD' AND %s.priceList = 1 AND %s.unit = 'item'",
                    $joinAlias1,
                    $joinAlias1,
                    $joinAlias1,
                    $joinAlias1
                ),
            ],
            'column with price / unit pair, showing tier prices disabled' => [
                'selectedField' => $column1,
                'selectedTable' => $column1 . '_table',
                'showTierPrices' => false,
                'select' => [
                    'price_column_usd_item_table.value as price_column_usd_item__value',
                    sprintf($select, $joinAlias1, $joinAlias1, "''", $column1)
                ],
                'joinExpression' => sprintf(
                    "%s.product = product.id AND %s.currency = 'USD' AND "
                    . "%s.priceList = 1 AND %s.quantity = 1 AND %s.unit = 'item'",
                    $joinAlias1,
                    $joinAlias1,
                    $joinAlias1,
                    $joinAlias1,
                    $joinAlias1
                ),
            ],
            'column with price only, showing tier prices enabled' => [
                'selectedField' => $column2,
                'selectedTable' => $column2 . '_table',
                'showTierPrices' => true,
                'select' => [sprintf($select, $joinAlias2, $joinAlias2, $unitCodePart, $column2)],
                'joinExpression' => sprintf(
                    "%s.product = product.id AND %s.currency = 'USD' AND %s.priceList = 1",
                    $joinAlias2,
                    $joinAlias2,
                    $joinAlias2
                ),
            ],
            'column with price only, showing tier prices disabled' => [
                'selectedField' => $column2,
                'selectedTable' => $column2 . '_table',
                'showTierPrices' => false,
                'select' => [sprintf($select, $joinAlias2, $joinAlias2, $unitCodePart, $column2)],
                'joinExpression' => sprintf(
                    "%s.product = product.id AND %s.currency = 'USD' AND %s.priceList = 1 AND %s.quantity = 1",
                    $joinAlias2,
                    $joinAlias2,
                    $joinAlias2,
                    $joinAlias2
                ),
            ],
        ];
    }

    private function assertColumnsAddedToQueryConfig(
        string $selectedField,
        string $selectedTable,
        bool $showTierPrices,
        array $selectExpressions,
        string $joinExpression
    ): void {
        $this->selectedFieldsProvider->expects(self::once())
            ->method('getSelectedFields')
            ->with($this->datagridConfiguration, $this->datagridParameters)
            ->willReturn([$selectedField]);

        $ormQueryConfiguration = $this->createMock(OrmQueryConfiguration::class);
        $ormQueryConfiguration->expects(self::once())
            ->method('addHint')
            ->with(PriceShardOutputResultModifier::HINT_PRICE_SHARD);
        $ormQueryConfiguration->expects(self::exactly(count($selectExpressions)))
            ->method('addSelect')
            ->withConsecutive(...array_map(
                function ($v) {
                    return [$v];
                },
                $selectExpressions
            ));

        $this->datagridConfiguration->expects(self::any())
            ->method('getOrmQuery')
            ->willReturn($ormQueryConfiguration);

        $this->priceListRequestHandler->expects(self::once())
            ->method('getShowTierPrices')
            ->willReturn($showTierPrices);

        $ormQueryConfiguration->expects(self::once())
            ->method('addLeftJoin')
            ->with(ProductPrice::class, $selectedTable, Expr\Join::WITH, $joinExpression);
    }

    public function testVisitResultWhenNoRelevantPriceColumns(): void
    {
        $resultsObject = $this->createMock(ResultsObject::class);
        $resultsObject->expects(self::never())
            ->method('getData');

        $this->extension->visitResult($this->datagridConfiguration, $resultsObject);
    }

    /**
     * @dataProvider visitResultDataProvider
     */
    public function testVisitResult(bool $showTierPrices, string $rawPrices, array $unpackedPrices): void
    {
        $this->mockAuthorizationChecker(true);

        $this->assertColumnsAddedToConfig();

        $this->selectedFieldsProvider->expects(self::once())
            ->method('getSelectedFields')
            ->with($this->datagridConfiguration, $this->datagridParameters)
            ->willReturn([$selectedField = 'price_column_usd']);

        $this->datagridConfiguration->expects(self::any())
            ->method('getOrmQuery')
            ->willReturn($this->createMock(OrmQueryConfiguration::class));

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->processConfigs($this->datagridConfiguration);

        $resultsObject = $this->createMock(ResultsObject::class);
        $resultsObject->expects(self::once())
            ->method('getData')
            ->willReturn([$record = $this->createMock(ResultRecord::class)]);

        $this->priceListRequestHandler->expects(self::once())
            ->method('getShowTierPrices')
            ->willReturn($showTierPrices);

        $record->expects(self::once())
            ->method('addData')
            ->with(['showTierPrices' => $showTierPrices]);
        $record->expects(self::once())
            ->method('getValue')
            ->with($selectedField)
            ->willReturn($rawPrices);
        $record->expects(self::once())
            ->method('setValue')
            ->with($selectedField, $unpackedPrices);

        $this->extension->visitResult($this->datagridConfiguration, $resultsObject);
    }

    public function visitResultDataProvider(): array
    {
        return [
            'showing tier prices enabled, 1 price' => [
                'showTierPrices' => true,
                'rawPrices' => '10.25|1|item',
                'unpackedPrices' => [
                    [
                        'price' => Price::create('10.25', 'USD'),
                        'unitCode' => 'item',
                        'quantity' => 1,
                    ],
                ],
            ],
            'showing tier prices disabled, 2 prices' => [
                'showTierPrices' => false,
                'rawPrices' => '10.25|1|item;41|2|set',
                'unpackedPrices' => [
                    [
                        'price' => Price::create('10.25', 'USD'),
                        'unitCode' => 'item',
                        'quantity' => 1,
                    ],
                    [
                        'price' => Price::create('41', 'USD'),
                        'unitCode' => 'set',
                        'quantity' => 2,
                    ],
                ],
            ],
        ];
    }
}
