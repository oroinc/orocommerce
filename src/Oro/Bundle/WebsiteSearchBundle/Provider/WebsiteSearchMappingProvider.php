<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Loader\ConfigurationLoaderInterface;

class WebsiteSearchMappingProvider extends AbstractSearchMappingProvider
{
    /**
     * @var ConfigurationLoaderInterface
     */
    private $mappingConfigurationLoader;

    /**
     * @var array
     */
    private $configuration;

    /**
     * @param ConfigurationLoaderInterface $mappingConfigurationLoader
     */
    public function __construct(ConfigurationLoaderInterface $mappingConfigurationLoader)
    {
        $this->mappingConfigurationLoader = $mappingConfigurationLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function getMappingConfig()
    {
        if (!$this->configuration) {
            $this->configuration = $this->mappingConfigurationLoader->getConfiguration();
        }

        return $this->configuration;
    }

    /**
     * @param Query $query
     * @param array $item
     * @return array|null
     */
    public function mapSelectedData(Query $query, array $item)
    {
        $selects = $query->getSelect();

        if (empty($selects)) {
            return null;
        }

        $result = [];

        foreach ($selects as $select) {
            list ($type, $name) = Criteria::explodeFieldTypeName($select);

            $result[$name] = '';

            if (isset($item[$name])) {
                $value = $item[$name];
                if (is_array($value)) {
                    $value = array_shift($value);
                }

                $result[$name] = $value;
            }
        }

        return $result;
    }
}
