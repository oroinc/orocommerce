<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

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
     * @var Request
     */
    protected $request;

    /**
     * @var string
     */
    protected $priceListClass;

    /**
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(TranslatorInterface $translator, DoctrineHelper $doctrineHelper)
    {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * @param string $priceListClass
     */
    public function setPriceListClass($priceListClass)
    {
        $this->priceListClass = $priceListClass;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        if (!$this->request) {
            return;
        }

        $priceListId = $this->getPriceListId();
        $currencies = $this->getCurrencies();
        if (!$priceListId || !$currencies) {
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

            $select = $config->offsetGetByPath('[columns]');
            $select[$columnName] = $column;
            $config->offsetSetByPath('[columns]', $select);
        }
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        $priceListId = $this->getPriceListId();
        $currencies = $this->getCurrencies();
        if (!$priceListId || !$currencies) {
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
        $prices = $priceRepository->findByPriceListIdAndProductIds($priceListId, $productIds);
        $groupedPrices = $this->groupPrices($prices);

        foreach ($records as $record) {
            $productId = $record->getValue('id');
            $priceContainer = [];
            foreach ($currencies as $currencyIsoCode) {
                $columnName = $this->buildColumnName($currencyIsoCode);
                if (isset($groupedPrices[$productId][$currencyIsoCode])) {
                    $priceContainer[$columnName] = $groupedPrices[$productId][$currencyIsoCode];
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
     * @return int
     */
    protected function getPriceListId()
    {
        return (int)$this->request->get('priceListId');
    }

    /**
     * @return array
     */
    protected function getCurrencies()
    {
        $currencies = (array)$this->request->get('priceCurrencies', []);

        foreach ($currencies as $key => $code) {
            if (!preg_match('/^[a-zA-Z0-9]+$/', $code)) {
                unset($currencies[$key]);
            }
        }

        return $currencies;
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
