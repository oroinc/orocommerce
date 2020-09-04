<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\ImportExportBundle\Tests\Behat\Context\ImportExportContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

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
        $this->importExportContext->exportedFileForEntityWithProcessorContainsAtLeastFollowingColumns(
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
        $imagePath = sprintf(
            '%s%s',
            __DIR__,
            '/../Features/Fixtures/product_images_import'
        );

        $importImageDir = sprintf(
            '%s%s',
            $this->getContainer()->getParameter('kernel.project_dir'),
            '/var/import_export/product_images'
        );

        $this->copyFiles($imagePath, $importImageDir);
    }

    /**
     * This method prepares product image for product image import.
     *
     * @Given I copy product fixture files to upload directories
     */
    public function copyProductFixtureFilesToUploadDirs(): void
    {
        $sourcePath = sprintf('%s%s', __DIR__, '/../Features/Fixtures/files_import');
        $projectDir = $this->getContainer()->getParameter('kernel.project_dir');
        $destinationDirs = [
            'relative' => sprintf('%s%s', $projectDir, '/var/import_export/files'),
            'absolute' => sprintf('%s%s', $projectDir, '/var/import_export'),
            'public' => sprintf('%s%s', $projectDir, '/public/media/cache/import_export'),
        ];

        foreach ($destinationDirs as $destinationPath) {
            $this->copyFiles($sourcePath, $destinationPath);
        }
    }

    //@codingStandardsIgnoreStart
    /**
     * Example: Given I copy product fixture "000.png" to import export upload dir as "091.png"
     *
     * @Given /^I copy product fixture "(?P<filename>(?:[^"]|\\")*)" to import export upload dir as "(?P<newFilename>(?:[^"]|\\")*)"$/
     *
     * @param string $filename
     * @param string $newFilename
     */
    //@codingStandardsIgnoreEnd
    public function copyProductFixtureFileToImportExportDir(string $filename, string $newFilename): void
    {
        $filename = $this->fixStepArgument($filename);
        $imagePath = sprintf('%s/../Features/Fixtures/files_import/%s', __DIR__, $filename);

        $importExportDir = sprintf(
            '%s/%s/%s',
            $this->getContainer()->getParameter('kernel.project_dir'),
            'var/import_export/files',
            $newFilename
        );

        $this->copyFiles($imagePath, $importExportDir);
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

    /**
     * Fill import csv file
     * Example: And I fill product import file with data:
     *            | Account Customer name | Channel Name        | Opportunity name | Status Id   |
     *            | Charlie               | First Sales Channel | Opportunity one  | in_progress |
     *            | Samantha              | First Sales Channel | Opportunity two  | in_progress |
     *
     * @Given /^(?:|I )fill product import file with data:$/
     */
    public function iFillImportFileWithData(TableNode $table)
    {
        $websiteUrl = $this->getContainer()->get('oro_website.resolver.website_url_resolver')->getWebsiteUrl();

        $this->importExportContext->setAbsoluteUrl($websiteUrl);
        $this->importExportContext->iFillImportFileWithData($table);
        $this->importExportContext->setAbsoluteUrl(null);
    }
}
