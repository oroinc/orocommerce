<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\PricingBundle\Model\AbstractPriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;

class ProductPriceDatagridListener
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var AbstractPriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     * @param AbstractPriceListRequestHandler $priceListRequestHandler
     */
    public function __construct(
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        AbstractPriceListRequestHandler $priceListRequestHandler
    ) {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->priceListRequestHandler = $priceListRequestHandler;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $currencies = $this->getCurrencies();
        if (!$currencies) {
            return;
        }

        $config = $event->getConfig();

        foreach ($currencies as $currencyIsoCode) {
            $columnName = $this->buildColumnName($currencyIsoCode);
            $column = [
                'label' => $this->translator->trans(
                    'orob2b.pricing.productprice.price_in_%currency%',
                    ['%currency%' => $currencyIsoCode]
                ),
                'type' => 'twig',
                'template' => 'OroB2BPricingBundle:Datagrid:Column/productPrice.html.twig',
                'frontend_type' => 'html',
            ];

            $config->offsetSetByPath(sprintf('[columns][%s]', $columnName), $column);

            $filter = [
                'type' => 'product-price',
                'data_name' => $currencyIsoCode
            ];

            $config->offsetSetByPath(sprintf('[filters][columns][%s]', $columnName), $filter);
        }
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        $currencies = $this->getCurrencies();
        if (!$currencies) {
            return;
        }

        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $productIds = [];
        foreach ($records as $record) {
            $productIds[] = $record->getValue('id');
        }

        /** @var ProductPriceRepository $priceRepository */
        $priceRepository = $this->doctrineHelper->getEntityRepository('OroB2BPricingBundle:ProductPrice');

        $priceList = $this->getPriceList();
        $showTierPrices = $this->priceListRequestHandler->getShowTierPrices();
        $prices = $priceRepository->findByPriceListIdAndProductIds($priceList->getId(), $productIds, $showTierPrices);
        $groupedPrices = $this->groupPrices($prices);

        foreach ($records as $record) {
            $record->addData(['showTierPrices' => $showTierPrices]);

            $productId = $record->getValue('id');
            $priceContainer = [];
            foreach ($currencies as $currencyIsoCode) {
                $columnName = $this->buildColumnName($currencyIsoCode);
                if (isset($groupedPrices[$productId][$currencyIsoCode])) {
                    $priceContainer[$columnName] = $groupedPrices[$productId][$currencyIsoCode];
                } else {
                    $priceContainer[$columnName] = [];
                }
            }
            if ($priceContainer) {
                $record->addData($priceContainer);
            }
        }
    }

    /**
     * @param string $currencyIsoCode
     * @return string
     */
    protected function buildColumnName($currencyIsoCode)
    {
        return 'price_column_' . strtolower($currencyIsoCode);
    }

    /**
     * @return PriceList
     */
    protected function getPriceList()
    {
        return $this->priceListRequestHandler->getPriceList();
    }

    /**
     * @return array
     */
    protected function getCurrencies()
    {
        return $this->priceListRequestHandler->getPriceListSelectedCurrencies();
    }

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
}
