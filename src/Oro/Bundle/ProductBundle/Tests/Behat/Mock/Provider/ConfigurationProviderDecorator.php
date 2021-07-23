<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Mock\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;

class ConfigurationProviderDecorator implements ConfigurationProviderInterface
{
    /** @var ConfigurationProviderInterface */
    private $configurationProvider;

    public function __construct(ConfigurationProviderInterface $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration(string $gridName): DatagridConfiguration
    {
        $configuration = $this->configurationProvider->getConfiguration($gridName);

        if ($gridName == 'frontend-product-search-grid') {
            $configuration->offsetAddToArray(
                'options',
                [
                    'noDataMessages' => [
                        'emptyGrid' => 'oro.product.datagrid.empty_grid',
                        'emptyFilteredGrid' => 'oro.product.datagrid.empty_filtered_grid'
                    ]
                ]
            );
        }

        return $configuration;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(string $gridName): bool
    {
        return $this->configurationProvider->isApplicable($gridName);
    }
}
