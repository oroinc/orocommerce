<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\ImportExportBundle\Tests\Behat\Context\ImportExportContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class ProductImportExportContext extends OroFeatureContext implements KernelAwareContext
{
    use KernelDictionary;

    const PRODUCT_ENTITY = 'Products';
    const PRODUCT_PROCESSOR = 'oro_product_product';
    const PRODUCT_ATTRIBUTES_PROCESSOR = 'oro_entity_config_attribute.export_template';

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

    /**
     * This method prepares product image for product image import.
     *
     * @Given I upload product images files
     */
    public function copyImageFromFixturesToImageImportExportDir()
    {
        $fs = new Filesystem();
        $imagePath = sprintf(
            '%s%s',
            __DIR__,
            '/../Features/Fixtures/product_images_import'
        );

        $importImageDir = sprintf(
            '%s%s',
            $this->getContainer()->getParameter('kernel.root_dir'),
            '/import_export/product_images'
        );

        try {
            if ($fs->exists($importImageDir)) {
                $fs->mkdir($importImageDir);
            }
            $fs->mirror(
                $imagePath,
                $importImageDir
            );
        } catch (IOExceptionInterface $e) {
            echo "An error occurred while copying image" . $imagePath;
        }
    }

    /**
     * Download product attributes' data template from attributes grid page
     *
     * @When /^(?:|I )download Product Attributes' Data Template file$/
     */
    public function downloadProductAttributesDataTemplate()
    {
        $this->importExportContext->downloadTemplateFileByProcessor(self::PRODUCT_ATTRIBUTES_PROCESSOR);
    }
}
