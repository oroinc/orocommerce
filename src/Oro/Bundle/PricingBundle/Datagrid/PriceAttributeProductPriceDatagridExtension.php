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
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Adds price attribute columns, sorters, filters for each currency enabled in current price list.
 */
class PriceAttributeProductPriceDatagridExtension extends AbstractExtension
{
    private const SUPPORTED_GRID = 'products-grid';

    /** @var bool */
    private $applied = false;

    /** @var PriceListRequestHandler */
    private $priceListRequestHandler;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var SelectedFieldsProviderInterface */
    private $selectedFieldsProvider;

    /** @var AclHelper */
    private $aclHelper;

    /** @var array */
    private $enabledPriceColumns;

    public function __construct(
        PriceListRequestHandler $priceListRequestHandler,
        DoctrineHelper $doctrineHelper,
        SelectedFieldsProviderInterface $selectedFieldsProvider,
        AclHelper $aclHelper
    ) {
        $this->priceListRequestHandler = $priceListRequestHandler;
        $this->doctrineHelper = $doctrineHelper;
        $this->selectedFieldsProvider = $selectedFieldsProvider;
        $this->aclHelper = $aclHelper;
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
        return
            !$this->applied
            && static::SUPPORTED_GRID === $config->getName()
            && parent::isApplicable($config);
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
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
        foreach ($records as $record) {
            foreach ($this->enabledPriceColumns as $columnName => list($currencyIsoCode)) {
                $this->unpackPrices($record, $columnName, $currencyIsoCode);
            }
        }
    }

    private function addColumns(DatagridConfiguration $config): void
    {
        $attributesWithCurrencies = $this->getAttributesWithCurrencies();
        if (!$attributesWithCurrencies) {
            return;
        }

        $priceColumns = [];

        // Add price column for each price attribute.
        foreach ($attributesWithCurrencies as $attributeWithCurrency) {
            $currencyIsoCode = $attributeWithCurrency['currency'];
            $priceAttributeId = $attributeWithCurrency['id'];

            $columnName = $this->getColumnName($currencyIsoCode, $priceAttributeId);
            $priceColumns[$columnName] = [$currencyIsoCode, $priceAttributeId];

            $this->addColumnToConfig(
                $config,
                $columnName,
                $currencyIsoCode,
                $attributeWithCurrency['name']
            );
        }

        // Add selected fields to query config.
        $selectedFields = $this->selectedFieldsProvider->getSelectedFields($config, $this->getParameters());
        $this->enabledPriceColumns = array_intersect_key($priceColumns, array_flip($selectedFields));
        if ($this->enabledPriceColumns) {
            foreach ($this->enabledPriceColumns as $columnName => list($currencyIsoCode, $priceAttributeId)) {
                $this->addColumnToQueryConfig($config, $columnName, $currencyIsoCode, $priceAttributeId);
            }
        }
    }

    private function getAttributesWithCurrencies(): array
    {
        $priceList = $this->priceListRequestHandler->getPriceList();
        if ($priceList === null) {
            return [];
        }

        $currencies = $this->priceListRequestHandler->getPriceListSelectedCurrencies($priceList);
        if (!$currencies) {
            return [];
        }

        /** @var PriceAttributePriceListRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(PriceAttributePriceList::class);
        $qb = $repository->getAttributesWithCurrenciesQueryBuilder($currencies);

        return $this->aclHelper->apply($qb)->getResult();
    }

    private function addColumnToConfig(
        DatagridConfiguration $config,
        string $columnName,
        string $currencyIsoCode,
        string $priceAttributeName
    ): void {
        $columnConfig = [
            'label' => sprintf('%s (%s)', $priceAttributeName, $currencyIsoCode),
            'type' => 'twig',
            'template' => '@OroPricing/Datagrid/Column/productPrice.html.twig',
            'frontend_type' => 'html',
            'renderable' => true,
        ];
        $filterConfig = ['type' => 'price-attribute-product-price', 'data_name' => $currencyIsoCode];

        $config->offsetAddToArrayByPath('[columns]', [$columnName => $columnConfig]);
        $config->offsetAddToArrayByPath('[filters][columns]', [$columnName => $filterConfig]);
        $config->offsetAddToArrayByPath('[sorters][columns]', [$columnName => ['data_name' => $columnName]]);
    }

    /**
     * @param string $currencyIsoCode
     * @param integer $priceAttributeId
     *
     * @return string
     */
    private function getColumnName(string $currencyIsoCode, int $priceAttributeId): string
    {
        return sprintf('price_attribute_price_column_%s_%s', strtolower($currencyIsoCode), $priceAttributeId);
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
        int $priceAttributeId
    ): void {
        $joinAlias = $this->getJoinAlias($columnName);

        $select = sprintf(
            "GROUP_CONCAT(DISTINCT CONCAT_WS('|', %s.value, IDENTITY(%s.unit)) SEPARATOR ';') as %s",
            $joinAlias,
            $joinAlias,
            $columnName
        );

        $config->getOrmQuery()->addSelect($select);

        $this->addJoinToQueryConfig($config, $columnName, $currencyIsoCode, $priceAttributeId);
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $columnName
     * @param string $currencyIsoCode
     * @param integer $priceAttributeId
     */
    private function addJoinToQueryConfig(
        DatagridConfiguration $config,
        string $columnName,
        string $currencyIsoCode,
        int $priceAttributeId
    ): void {
        $joinAlias = $this->getJoinAlias($columnName);
        $expr = new Expr();
        $joinExpr = $expr
            ->andX(sprintf('%s.product = product.id', $joinAlias))
            ->add($expr->eq(sprintf('%s.currency', $joinAlias), $expr->literal($currencyIsoCode)))
            ->add($expr->eq(sprintf('%s.priceList', $joinAlias), $expr->literal($priceAttributeId)))
            // Quantity is always 1 because price attribute contains price for single product.
            ->add($expr->eq(sprintf('%s.quantity', $joinAlias), 1));

        $config->getOrmQuery()->addLeftJoin(
            PriceAttributeProductPrice::class,
            $joinAlias,
            Expr\Join::WITH,
            (string)$joinExpr
        );
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
            [$priceValue, $unitCode] = explode('|', $rawPrice);
            $price = Price::create($priceValue, $currencyIsoCode);
            $prices[] = [
                'price' => $price,
                'unitCode' => $unitCode,
                // Always 1 because price attribute contains price for single product.
                'quantity' => 1,
            ];
        }

        return $prices;
    }
}
