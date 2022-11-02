<?php

namespace Oro\Bundle\PricingBundle\Datagrid;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardOutputResultModifier;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds price and price per unit columns, sorters, filters for each currency enabled in current price list.
 */
class ProductPriceDatagridExtension extends AbstractExtension implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    private const SUPPORTED_GRID = 'products-grid';
    private const UNIT_FILTER_SUFFIX = '__value';

    /** @var bool */
    private $applied = false;

    /** @var PriceListRequestHandler */
    private $priceListRequestHandler;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var SelectedFieldsProviderInterface */
    private $selectedFieldsProvider;

    /** @var TranslatorInterface */
    private $translator;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var PriceList|null */
    private $priceList;

    /** @var array */
    private $enabledPriceColumns;

    public function __construct(
        PriceListRequestHandler $priceListRequestHandler,
        DoctrineHelper $doctrineHelper,
        SelectedFieldsProviderInterface $selectedFieldsProvider,
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->priceListRequestHandler = $priceListRequestHandler;
        $this->doctrineHelper = $doctrineHelper;
        $this->selectedFieldsProvider = $selectedFieldsProvider;
        $this->translator = $translator;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Must be called before FormatterExtension.
     *
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $this->isFeaturesEnabled()
            && !$this->applied
            && static::SUPPORTED_GRID === $config->getName()
            && parent::isApplicable($config);
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        if (!$this->isGrantedToViewPriceFields()) {
            return;
        }

        $this->addColumns($config);
        $this->applied = true;
    }

    /**
     * {@inheritdoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        if (!$this->enabledPriceColumns) {
            return;
        }

        /** @var ResultRecord[] $records */
        $records = $result->getData();
        $showTierPrices = $this->priceListRequestHandler->getShowTierPrices();

        foreach ($records as $record) {
            $record->addData(['showTierPrices' => $showTierPrices]);

            foreach ($this->enabledPriceColumns as $columnName => list($currencyIsoCode)) {
                $this->unpackPrices($record, $columnName, $currencyIsoCode);
            }
        }
    }

    private function addColumns(DatagridConfiguration $config): void
    {
        $currencies = $this->getCurrencies();
        if (!$currencies) {
            return;
        }

        $unitsCodes = $this->getAllUnitsCodes();
        $priceColumns = [];
        $dataColumns = [];

        // Add prices columns for each currency and currency + unit.
        foreach ($currencies as $currencyIsoCode) {
            $columnName = $this->getColumnName($currencyIsoCode);
            $priceColumns[$columnName] = [$currencyIsoCode, null];

            $dataColumns[$columnName] = $this->addColumnToConfig($config, $columnName, $currencyIsoCode);

            // Add price for each pair of currency + unit.
            foreach ($unitsCodes as $unitCode) {
                $columnName = $this->getColumnName($currencyIsoCode, $unitCode);
                $priceColumns[$columnName] = [$currencyIsoCode, $unitCode];

                $dataColumns[$columnName] = $this->addColumnToConfig($config, $columnName, $currencyIsoCode, $unitCode);
            }
        }

        // Add selected fields to query config.
        $selectedFields = $this->getSelectedFields($config, $dataColumns);
        $this->enabledPriceColumns = array_intersect_key($priceColumns, array_flip($selectedFields));
        if ($this->enabledPriceColumns) {
            $config->getOrmQuery()->addHint(PriceShardOutputResultModifier::HINT_PRICE_SHARD);
            foreach ($this->enabledPriceColumns as $columnName => list($currencyIsoCode, $unitCode)) {
                $this->addColumnToQueryConfig($config, $columnName, $currencyIsoCode, $unitCode);
            }
        }
    }

    private function getSelectedFields(DatagridConfiguration $config, array $dataColumns): array
    {
        array_walk(
            $dataColumns,
            function (&$dataColumns, $columnName) {
                $dataColumns = array_fill_keys($dataColumns, $columnName);
            }
        );

        $selectedFields = $this->selectedFieldsProvider->getSelectedFields($config, $this->getParameters());

        return array_intersect_key(
            array_merge(...array_values($dataColumns)),
            array_flip($selectedFields)
        );
    }

    private function getCurrencies(): array
    {
        $priceList = $this->getPriceList();
        if ($priceList === null) {
            return [];
        }

        return $this->priceListRequestHandler->getPriceListSelectedCurrencies($priceList);
    }

    /**
     * @return string[]
     */
    private function getAllUnitsCodes(): array
    {
        /** @var ProductUnitRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(ProductUnit::class);

        return $repository->getAllUnitCodes();
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $columnName
     * @param string $currencyIsoCode
     * @param string $unitCode
     * @return array
     */
    private function addColumnToConfig(
        DatagridConfiguration $config,
        string $columnName,
        string $currencyIsoCode,
        ?string $unitCode = null
    ): array {
        $columnConfig = $this->createPriceColumnConfig($currencyIsoCode, $unitCode);
        $filterConfig = $this->createPriceFilterConfig($columnName, $currencyIsoCode, $unitCode);

        $config->offsetAddToArrayByPath('[columns]', [$columnName => $columnConfig]);
        $config->offsetAddToArrayByPath('[filters][columns]', [$columnName => $filterConfig]);
        $config->offsetAddToArrayByPath('[sorters][columns]', [$columnName => ['data_name' => $columnName]]);

        return array_unique([$columnName, $filterConfig['data_name']]);
    }

    private function createPriceColumnConfig(
        string $currencyIsoCode,
        ?string $unitCode = null
    ): array {
        $message = 'oro.pricing.productprice.price_in_%currency%';
        $params = ['%currency%' => $currencyIsoCode];
        if ($unitCode !== null) {
            $message = 'oro.pricing.productprice.price_%unit%_in_%currency%';
            $params['%unit%'] = $unitCode;
        }

        return [
            'label' => $this->translator->trans($message, $params),
            'type' => 'twig',
            'template' => '@OroPricing/Datagrid/Column/productPrice.html.twig',
            'frontend_type' => 'html',
            // Filters for price / unit pairs must not be renderable by default.
            'renderable' => !$unitCode,
        ];
    }

    private function createPriceFilterConfig(
        string $columnName,
        string $currencyIsoCode,
        ?string $unitCode = null
    ): array {
        if ($unitCode) {
            $filter = [
                'type' => 'number-range',
                'data_name' => $columnName . self::UNIT_FILTER_SUFFIX,
                // Filters for price / unit pairs must be disabled by default.
                'renderable' => false,
            ];
        } else {
            $filter = ['type' => 'product-price', 'data_name' => $currencyIsoCode];
        }

        return $filter;
    }

    private function getColumnName(string $currencyIsoCode, ?string $unitCode = null): string
    {
        $result = 'price_column_' . strtolower($currencyIsoCode);

        return $unitCode ? sprintf('%s_%s', $result, strtolower($unitCode)) : $result;
    }

    private function getJoinAlias(string $columnName): string
    {
        QueryBuilderUtil::checkIdentifier($columnName);

        return $columnName . '_table';
    }

    private function addColumnToQueryConfig(
        DatagridConfiguration $config,
        string $columnName,
        string $currencyIsoCode,
        ?string $unitCode = null
    ): void {
        $joinAlias = $this->getJoinAlias($columnName);

        $unitCodePart = sprintf('IDENTITY(%s.unit)', $joinAlias);
        if ($unitCode) {
            // unit should not be displayed if it already exists in column name
            $unitCodePart = "''";

            // add additional selector to work with filters
            $config->getOrmQuery()->addSelect(
                sprintf('%s.value as %s', $joinAlias, $columnName . self::UNIT_FILTER_SUFFIX)
            );
        }

        $select = sprintf(
            "GROUP_CONCAT(DISTINCT CONCAT_WS('|', %s.value, CAST(%s.quantity as string), %s) SEPARATOR ';') as %s",
            $joinAlias,
            $joinAlias,
            $unitCodePart,
            $columnName
        );

        $config->getOrmQuery()->addSelect($select);

        $this->addJoinToQueryConfig($config, $columnName, $currencyIsoCode, $unitCode);
    }

    private function addJoinToQueryConfig(
        DatagridConfiguration $config,
        string $columnName,
        string $currencyIsoCode,
        ?string $unitCode = null
    ): void {
        $showTierPrices = $this->priceListRequestHandler->getShowTierPrices();
        $joinAlias = $this->getJoinAlias($columnName);
        /** It is assumed that we cannot get null here because we worked out this case earlier in ::getCurrencies() */
        /** @var PriceList $priceList */
        $priceList = $this->getPriceList();
        $expr = new Expr();
        $joinExpr = $expr
            ->andX(sprintf('%s.product = product.id', $joinAlias))
            ->add($expr->eq(sprintf('%s.currency', $joinAlias), $expr->literal($currencyIsoCode)))
            ->add($expr->eq(sprintf('%s.priceList', $joinAlias), $expr->literal($priceList->getId())));

        if (!$showTierPrices) {
            $joinExpr->add($expr->eq(sprintf('%s.quantity', $joinAlias), 1));
        }

        if ($unitCode) {
            $joinExpr->add($expr->eq(sprintf('%s.unit', $joinAlias), $expr->literal($unitCode)));
        }

        $config->getOrmQuery()->addLeftJoin(
            ProductPrice::class,
            $joinAlias,
            Expr\Join::WITH,
            (string)$joinExpr
        );
    }

    private function getPriceList(): ?PriceList
    {
        if (!$this->priceList) {
            $this->priceList = $this->priceListRequestHandler->getPriceList();
        }

        return $this->priceList;
    }

    private function unpackPrices(ResultRecord $record, string $columnName, string $currencyIsoCode): void
    {
        $rawPrices = $record->getValue($columnName);
        if ($rawPrices) {
            $prices = $this->unpackPriceFromRaw($rawPrices, $currencyIsoCode);
            $record->setValue($columnName, $prices);
        }
    }

    private function unpackPriceFromRaw(string $rawPrices, string $currencyIsoCode): array
    {
        $prices = [];
        foreach (explode(';', $rawPrices) as $rawPrice) {
            [$priceValue, $quantity, $unitCode] = explode('|', $rawPrice);
            $price = Price::create($priceValue, $currencyIsoCode);
            $prices[] = [
                'price' => $price,
                'unitCode' => $unitCode,
                'quantity' => $quantity,
            ];
        }

        return $prices;
    }

    private function isGrantedToViewPriceFields(): bool
    {
        return $this->authorizationChecker->isGranted('VIEW', sprintf('entity:%s', ProductPrice::class));
    }
}
