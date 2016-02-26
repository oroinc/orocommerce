<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

class FrontendProductPriceDatagridListener extends AbstractProductPriceDatagridListener
{
    /**
     * {@inheritDoc}
     */
    protected function buildColumnName($currencyIsoCode, $unitCode = null)
    {
        return 'price_column' . ($unitCode ? '_' . strtolower($unitCode) : '');
    }

    /**
     * {@inheritDoc}
     */
    protected function getColumnTemplate()
    {
        return 'OroB2BPricingBundle:Datagrid:Column/Frontend/productPrice.html.twig';
    }

    /**
     * {@inheritDoc}
     */
    protected function providePriceList()
    {
        return $this->priceListRequestHandler->getPriceListByAccount();
    }
}
