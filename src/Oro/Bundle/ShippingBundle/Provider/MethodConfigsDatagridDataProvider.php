<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodOrganizationProvider;
use Twig\Environment;

/**
 * Renders the "Configurations" datagrid cell for "shipping-methods-configs-rule-grid" datagrid
 * according to shipping method organization.
 */
class MethodConfigsDatagridDataProvider
{
    private ShippingMethodOrganizationProvider $organizationProvider;
    private Environment $twig;

    public function __construct(ShippingMethodOrganizationProvider $organizationProvider, Environment $twig)
    {
        $this->organizationProvider = $organizationProvider;
        $this->twig = $twig;
    }

    public function getMethodsConfigs(ResultRecordInterface $record): string
    {
        $previousOrganization = $this->organizationProvider->getOrganization();

        $this->organizationProvider->setOrganization($record->getValue('organization'));
        try {
            return $this->twig->render(
                '@OroShipping/ShippingMethodsConfigsRule/Datagrid/configurations.html.twig',
                ['record' => $record]
            );
        } finally {
            $this->organizationProvider->setOrganization($previousOrganization);
        }
    }
}
