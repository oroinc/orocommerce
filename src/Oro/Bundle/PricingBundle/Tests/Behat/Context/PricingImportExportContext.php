<?php

namespace Oro\Bundle\PricingBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Oro\Bundle\ImportExportBundle\Tests\Behat\Context\ImportExportContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class PricingImportExportContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    private ?ImportExportContext $importExportContext = null;
    private ?OroMainContext $oroMainContext = null;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();
        if ($environment->hasContextClass(OroMainContext::class)) {
            $this->oroMainContext = $environment->getContext(OroMainContext::class);
        }

        if ($environment->hasContextClass(ImportExportContext::class)) {
            $this->importExportContext = $environment->getContext(ImportExportContext::class);
        }
    }

    /**
     * @When /^(?:|I )import product prices file with strategy "(?P<strategy>([\w\s\.]+))"$/
     */
    public function iImportFileWithStrategy($strategy): void
    {
        $this->importExportContext->tryImportFileWithStrategy($strategy);

        $confirmButton = $this->createElement('ConfirmButton');
        if ($confirmButton->isIsset()) {
            $confirmButton->press();
            $this->getDriver()->waitForAjax(240000);
        }

        $flashMessage = 'Import started successfully. You will receive an email notification upon completion.';
        $this->oroMainContext->iShouldSeeFlashMessage($flashMessage);
    }
}
