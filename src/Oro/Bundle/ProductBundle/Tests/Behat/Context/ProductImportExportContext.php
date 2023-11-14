<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\EmailBundle\Tests\Behat\Context\EmailContext;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\ImportExportBundle\Tests\Behat\Context\ImportExportContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Symfony\Component\Finder\Finder;

/**
 * Behat context for product import/export functionality.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductImportExportContext extends OroFeatureContext
{
    private const PRODUCT_ENTITY = 'Products';
    private const PRODUCT_PROCESSOR = 'oro_product_product';
    private const PRODUCT_ATTRIBUTES_PROCESSOR = 'oro_entity_config_attribute.export_template';

    private ?ImportExportContext $importExportContext = null;
    private ?EmailContext $emailContext = null;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->importExportContext = $environment->getContext(ImportExportContext::class);
        $this->emailContext = $environment->getContext(EmailContext::class);
    }

    /**
     * This method makes non-strict comparison of data from the downloaded file for exported Products.
     *
     * Checks whether the listed columns (in any order) and corresponding data is present.
     *
     * @Given /^Exported file with Products contains at least the following data:$/
     */
    public function exportedFileWithProductsContainsAtLeastFollowingData(TableNode $expectedEntities)
    {
        $this->importExportContext->exportedFileForEntityWithProcessorContainsAtLeastFollowingColumns(
            self::PRODUCT_ENTITY,
            $expectedEntities,
            self::PRODUCT_PROCESSOR
        );
    }

    //@codingStandardsIgnoreStart
    /**
     * Example: Exported file with Product Kits divided by "\n" delimiter contains at least the following data
     *
     * @Given /^Exported file with Product Kits divided by "(?P<delimeter>(?:[^"]+))" delimiter contains at least the following data:$/
     */
    //@codingStandardsIgnoreEnd
    public function exportedFileWithProductKitsDividedByDelimiterContainsAtLeastTheFollowingData(
        $delimiter,
        TableNode $table
    ) {
        $table = $this->splitKitItems($delimiter, $table);

        $this->emailContext->downloadedFileFromEmailMustContains($table);
    }

    /**
     * Fill downloaded csv file template
     * Example: And I fill product KitItems template divided by "\n" delimiter with data:
     *            | SKU   | Name      | Kit Items                                                                      |
     *            | PSKU1 | Product 1 | id=,label=Base,optional=true,products=5TJ23|2RW93,min_qty=1,max_qty=1,unit=set |
     *
     * @Given /^(?:|I )fill product KitItems template divided by "(?P<delimeter>(?:[^"]+))" delimiter with data:$/
     */
    public function iFillProductKitItemsTemplateDividedByDelimiterWithData($delimiter, TableNode $table)
    {
        $table = $this->splitKitItems($delimiter, $table);

        $this->importExportContext->iFillTemplateWithData($table);
    }

    /**
     * This method prepares product image for product image import.
     *
     * @Given I upload product images files
     */
    public function copyImageFromFixturesToImageImportExportDir()
    {
        $this->copyFilesToStorage(
            __DIR__ . '/../Features/Fixtures/product_images_import',
            $this->getProductImportImagesFileManager()
        );
    }

    /**
     * This method prepares product image for product image import.
     *
     * @Given I copy product fixture files to upload directories
     */
    public function copyProductFixtureFilesToUploadDirs(): void
    {
        $sourcePath = sprintf('%s%s', __DIR__, '/../Features/Fixtures/files_import');
        // used for test a relative path
        $this->copyFilesToStorage($sourcePath, $this->getProductImportImagesFileManager());
        // used for test a URL
        $this->copyFilesToStorage($sourcePath, $this->getPublicMediaCacheFileManager(), 'test_import');
        // used for test an absolute path
        $this->copyFiles(
            $sourcePath,
            $this->getAppContainer()->getParameter('kernel.project_dir') . '/var/data/test_import/'
        );
    }

    /**
     * This method copies fixture images to public dir.
     *
     * @Given I copy product fixture files to public directory
     */
    public function copyProductFixtureFilesToPublicDir(): void
    {
        $sourcePath = sprintf('%s%s', __DIR__, '/../Features/Fixtures/files_import');
        $this->copyFiles(
            $sourcePath,
            $this->getAppContainer()->getParameter('kernel.project_dir') . '/public/media/cache/fixtures/'
        );
    }

    //@codingStandardsIgnoreStart
    /**
     * Example: Given I copy product fixture "000.png" to public directory as "091.png"
     *
     * @Given /^I copy product fixture "(?P<filename>(?:[^"]|\\")*)" to public directory as "(?P<newFilename>(?:[^"]|\\")*)"$/
     */
    //@codingStandardsIgnoreEnd
    public function copyProductFixtureFileToPublicDir(string $filename, string $newFilename): void
    {
        $sourcePath = sprintf(
            '%s/%s/%s',
            __DIR__,
            '../Features/Fixtures/files_import',
            $this->fixStepArgument($filename)
        );
        $targetPath = $this->getAppContainer()->getParameter('kernel.project_dir')
            . '/public/media/cache/fixtures/' . $this->fixStepArgument($newFilename);

        $this->copyFiles($sourcePath, $targetPath);
    }

    //@codingStandardsIgnoreStart
    /**
     * Example: Given I copy product fixture "000.png" to import upload dir as "091.png"
     *
     * @Given /^I copy product fixture "(?P<filename>(?:[^"]|\\")*)" to import upload dir as "(?P<newFilename>(?:[^"]|\\")*)"$/
     */
    //@codingStandardsIgnoreEnd
    public function copyProductFixtureFileToImportFilesDir(string $filename, string $newFilename): void
    {
        $this->getProductImportImagesFileManager()->writeFileToStorage(
            __DIR__ . '/../Features/Fixtures/files_import/' . $this->fixStepArgument($filename),
            $newFilename
        );
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
        $websiteUrl = $this->getAppContainer()->get('oro_website.resolver.website_url_resolver')->getWebsiteUrl();

        $this->importExportContext->setAbsoluteUrl($websiteUrl);
        $this->importExportContext->iFillImportFileWithData($table);
        $this->importExportContext->setAbsoluteUrl(null);
    }

    private function copyFilesToStorage(string $filesPath, FileManager $fileManager, string $directory = null): void
    {
        $finder = new Finder();
        /** @var \SplFileInfo[] $files */
        $files = $finder->files()->in($filesPath);
        foreach ($files as $file) {
            $fileName = $file->getFilename();
            if ($directory) {
                $fileName = $directory . '/' . $fileName;
            }
            $fileManager->writeFileToStorage($file->getPathname(), $fileName);
        }
    }

    private function splitKitItems($delimiter, TableNode $table): TableNode
    {
        $rows = $table->getRows();
        $kitItemIndex = array_search('Kit Items', $rows[0], true);
        if ($kitItemIndex) {
            foreach ($rows as $key => $row) {
                if ($key === 0 || empty($row[$kitItemIndex])) {
                    continue;
                }

                $rows[$key][$kitItemIndex] = str_replace($delimiter, PHP_EOL, $row[$kitItemIndex]);
            }

            $table = new TableNode($rows);
        }

        return $table;
    }

    private function getProductImportImagesFileManager(): FileManager
    {
        return $this->getAppContainer()->get('oro_product.importexport.file_manager.product_images');
    }

    private function getPublicMediaCacheFileManager(): FileManager
    {
        return $this->getAppContainer()->get('oro_attachment.manager.public_mediacache');
    }
}
