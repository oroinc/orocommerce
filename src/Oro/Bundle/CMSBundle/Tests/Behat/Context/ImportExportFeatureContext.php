<?php

namespace Oro\Bundle\CMSBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ImportExportBundle\Tests\Behat\Context\ImportExportContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class ImportExportFeatureContext extends OroFeatureContext
{
    private ?ImportExportContext $importExportContext = null;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();
        $this->importExportContext = $environment->getContext(ImportExportContext::class);
    }

    /**
     * Download data template for extend entity
     *
     * @When /^(?:|I )download Data Template file for "(?P<entity>([\w\s]+))" extend entity/
     * @When /^(?:|I )download Data Template file for "(?P<entity>.*)" entity/
     * @param string $className The `classname` of extend entity
     */
    public function iDownloadDataTemplateFileForExtendEntity(string $className): void
    {
        /** @var ConfigManager $entityConfigManager */
        $entityConfigManager = $this->getAppContainer()->get('oro_entity_config.config_manager');
        if (!$entityConfigManager->hasConfigEntityModel($className)) {
            $className = sprintf('%s%s', ExtendHelper::ENTITY_NAMESPACE, $className);
        }
        $entityModel = $entityConfigManager->getConfigEntityModel($className);
        static::assertNotNull($entityModel, sprintf('No entity model found for class "%s"', $className));

        $this->importExportContext->downloadTemplateFileByProcessor(
            'oro_entity_config_entity_field.export_template',
            ['entity_id' => $entityModel->getId()]
        );
    }
}
