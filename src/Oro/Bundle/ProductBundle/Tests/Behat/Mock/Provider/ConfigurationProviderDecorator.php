<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Mock\Provider;

use Oro\Bundle\DataGridBundle\Provider\ConfigurationProvider;

class ConfigurationProviderDecorator extends ConfigurationProvider
{
    /**
     * @var ConfigurationProvider
     */
    private $configurationProvider;

    /**
     * @param ConfigurationProvider $configurationProvider
     */
    public function __construct(ConfigurationProvider $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration($gridName)
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
    public function isApplicable($gridName)
    {
        return $this->configurationProvider->isApplicable($gridName);
    }

    /**
     * @param string $gridName
     *
     * @return array
     */
    public function getRawConfiguration(string $gridName): array
    {
        return $this->configurationProvider->getRawConfiguration($gridName);
    }
}
