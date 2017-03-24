<?php

namespace Oro\Bundle\UPSBundle\Client\Url\Provider\Basic;

use Oro\Bundle\UPSBundle\Client\Url\Provider\UpsClientUrlProviderInterface;

class BasicUpsClientUrlProvider implements UpsClientUrlProviderInterface
{
    /**
     * @var string
     */
    private $productionUrl;

    /**
     * @var string
     */
    private $testUrl;

    /**
     * @param string $productionUrl
     * @param string $testUrl
     */
    public function __construct($productionUrl, $testUrl)
    {
        $this->productionUrl = $productionUrl;
        $this->testUrl = $testUrl;
    }

    /**
     * {@inheritDoc}
     */
    public function getUpsUrl($isTestMode)
    {
        if ($isTestMode) {
            return $this->testUrl;
        }

        return $this->productionUrl;
    }
}
