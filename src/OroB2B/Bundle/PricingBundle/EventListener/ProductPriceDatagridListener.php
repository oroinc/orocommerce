<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

class ProductPriceDatagridListener extends AbstractProductPriceDatagridListener
{
    /**
     * {@inheritDoc}
     */
    protected function buildColumnName($currencyIsoCode, $unitCode = null)
    {
        $result = 'price_column_' . strtolower($currencyIsoCode);
        return $unitCode ? sprintf('%s_%s', $result, strtolower($unitCode)) : $result;
    }

    /**
     * {@inheritDoc}
     */
    protected function getColumnTemplate()
    {
        return 'OroB2BPricingBundle:Datagrid:Column/productPrice.html.twig';
    }

    /**
     * {@inheritDoc}
     */
    protected function providePriceList()
    {
        return $this->priceListRequestHandler->getPriceList();
    }
}
