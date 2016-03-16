<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\ORM\Query\Expr;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

abstract class AbstractProductPriceDatagridListener
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var PriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * @var string
     */
    protected $productPriceClass;

    /**
     * @var BasePriceList
     */
    protected $priceList;

    /**
     * @param TranslatorInterface $translator
     * @param PriceListRequestHandler $priceListRequestHandler
     */
    public function __construct(TranslatorInterface $translator, PriceListRequestHandler $priceListRequestHandler)
    {
        $this->translator = $translator;
        $this->priceListRequestHandler = $priceListRequestHandler;
    }

    /**
     * @param string $productPriceClass
     */
    public function setProductPriceClass($productPriceClass)
    {
        $this->productPriceClass = $productPriceClass;
    }

    /**
     * @param BuildBefore $event
     */
    abstract public function onBuildBefore(BuildBefore $event);

    /**
     * @param string $currencyIsoCode
     * @param string $unitCode
     * @return string
     */
    abstract protected function buildColumnName($currencyIsoCode, $unitCode = null);

    /**
     * @param string $columnName
     * @return string
     */
    protected function buildJoinAlias($columnName)
    {
        return $columnName . '_table';
    }

    /**
     * @return BasePriceList
     */
    protected function getPriceList()
    {
        if (!$this->priceList) {
            $this->priceList = $this->providePriceList();
        }
        return $this->priceList;
    }

    /**
     * @return BasePriceList
     */
    abstract protected function providePriceList();

    /**
     * @return array
     */
    abstract protected function getCurrencies();

    /**
     * @param ProductPrice[] $prices
     * @return array
     */
    protected function groupPrices(array $prices)
    {
        $groupedPrices = [];
        foreach ($prices as $price) {
            $productId = $price->getProduct()->getId();
            $currencyIsoCode = $price->getPrice()->getCurrency();
            if (!isset($groupedPrices[$productId][$currencyIsoCode])) {
                $groupedPrices[$productId][$currencyIsoCode] = [];
            }
            $groupedPrices[$productId][$currencyIsoCode][] = $price;
        }

        return $groupedPrices;
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $currency
     * @param ProductUnit|null $unit
     */
    protected function addConfigProductPriceJoin(DatagridConfiguration $config, $currency, $unit = null)
    {
        $columnName = $this->buildColumnName($currency, $unit);
        $joinAlias = $this->buildJoinAlias($columnName);
        $priceList = $this->getPriceList();
        $expr = new Expr();
        $joinExpr = $expr
            ->andX(sprintf('%s.product = product.id', $joinAlias))
            ->add($expr->eq(sprintf('%s.currency', $joinAlias), $expr->literal($currency)))
            ->add($expr->eq(sprintf('%s.priceList', $joinAlias), $expr->literal($priceList->getId())))
            ->add($expr->eq(sprintf('%s.quantity', $joinAlias), 1));
        if ($unit) {
            $joinExpr->add($expr->eq(sprintf('%s.unit', $joinAlias), $expr->literal($unit)));
        }
        $this->addConfigElement($config, '[source][query][join][left]', [
            'join' => $this->productPriceClass,
            'alias' => $joinAlias,
            'conditionType' => Expr\Join::WITH,
            'condition' => (string)$joinExpr,
        ]);
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $currency
     * @param bool $enabled
     * @param ProductUnit|null $unit
     */
    protected function addConfigFilter(DatagridConfiguration $config, $currency, $enabled = true, $unit = null)
    {
        $columnName = $this->buildColumnName($currency, $unit);
        $filter = ['type' => 'product-price', 'data_name' => $currency];
        if ($unit) {
            $filter = [
                'type' => 'number-range',
                'data_name' => $columnName,
                'enabled' => $enabled,
            ];
        }
        $this->addConfigElement($config, '[filters][columns]', $filter, $columnName);
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $path
     * @param mixed $element
     * @param mixed $key
     */
    protected function addConfigElement(DatagridConfiguration $config, $path, $element, $key = null)
    {
        $select = $config->offsetGetByPath($path);
        if ($key) {
            $select[$key] = $element;
        } else {
            $select[] = $element;
        }
        $config->offsetSetByPath($path, $select);
    }
}
