<?php

namespace Oro\Bundle\PricingBundle\Debug\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Datagrid\ProductPriceDatagridExtension;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandlerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds CPL price and price per unit columns, sorters, filters for each currency enabled in current price list.
 *
 * @internal This service is applicable for pricing debug purpose only.
 */
class CombinedProductPriceDatagridExtension extends ProductPriceDatagridExtension
{
    private PriceListRequestHandlerInterface $priceListRequestHandler;

    public function __construct(
        PriceListRequestHandlerInterface $priceListRequestHandler,
        DoctrineHelper $doctrineHelper,
        SelectedFieldsProviderInterface $selectedFieldsProvider,
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        parent::__construct(
            $priceListRequestHandler,
            $doctrineHelper,
            $selectedFieldsProvider,
            $translator,
            $authorizationChecker
        );

        $this->priceListRequestHandler = $priceListRequestHandler;
    }

    #[\Override]
    protected function getFilterType(): string
    {
        return 'combined-product-price';
    }

    #[\Override]
    protected function getPriceClassName(): string
    {
        return CombinedProductPrice::class;
    }

    #[\Override]
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        parent::visitResult($config, $result);

        foreach ($result->getData() as $record) {
            $record->setValue('customer', $this->priceListRequestHandler->getCustomer()?->getId());
            $record->setValue('website', $this->priceListRequestHandler->getWebsite()?->getId());
        }
    }

    #[\Override]
    public function processConfigs(DatagridConfiguration $config)
    {
        parent::processConfigs($config);

        $propertyConfig = [
            'type' => 'url',
            'route' => 'oro_pricing_price_product_debug_trace',
            'params' => ['id', 'website', 'customer']
        ];

        $config->offsetAddToArrayByPath(
            sprintf(
                '[%s][%s]',
                Configuration::PROPERTIES_KEY,
                'debug_link'
            ),
            $propertyConfig
        );

        $actions = [
            [
                'type' => 'navigate',
                'label' => 'oro.pricing.productprice.debug.trace.label',
                'translatable' => true,
                'rowAction' => true,
                'acl_resource' => 'oro_pricing_product_price_view',
                'link' => 'debug_link',
                'icon' => 'eye',
            ]
        ];

        $config->offsetSet(ActionExtension::ACTION_KEY, $actions);
    }
}
