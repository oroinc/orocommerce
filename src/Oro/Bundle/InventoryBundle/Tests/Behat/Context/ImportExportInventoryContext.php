<?php

namespace Oro\Bundle\InventoryBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\InventoryBundle\Tests\Behat\PreExportMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class ImportExportInventoryContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * @Given /^I change the export batch size to (?P<size>\d+)/
     *
     * @param int $size
     */
    public function changeExportBatchSize(int $size): void
    {
        $cache = $this->getCache();
        $cache->save(PreExportMessageProcessor::BATCH_SIZE_KEY, $size);
    }

    /**
     * @return CacheProvider|Object
     */
    private function getCache(): CacheProvider
    {
        return $this->getContainer()->get('oro_inventory.cache');
    }
}
