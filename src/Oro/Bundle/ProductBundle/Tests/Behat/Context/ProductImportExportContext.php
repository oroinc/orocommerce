<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\ImportExportBundle\Tests\Behat\Context\ImportExportContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class ProductImportExportContext extends OroFeatureContext
{
    const PRODUCT_ENTITY = 'Products';
    const PRODUCT_PROCESSOR = 'oro_product_product';

    /**
     * @var ImportExportContext
     */
    private $importExportContext;

    /**
     * @BeforeScenario
     *
     * @param BeforeScenarioScope $scope
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->importExportContext = $environment->getContext(ImportExportContext::class);
    }

    /**
     * This method makes non-strict comparison of data from the downloaded file for exported Products.
     *
     * Checks whether the listed columns (in any order) and corresponding data is present.
     *
     * @Given /^Exported file with Products contains at least the following data:$/
     *
     * @param TableNode $expectedEntities
     */
    public function exportedFileWithProductsContainsAtLeastFollowingData(TableNode $expectedEntities)
    {
        $this->importExportContext->exportedFileContainsAtLeastFollowingColumns(
            self::PRODUCT_ENTITY,
            $expectedEntities,
            self::PRODUCT_PROCESSOR
        );
    }
}
