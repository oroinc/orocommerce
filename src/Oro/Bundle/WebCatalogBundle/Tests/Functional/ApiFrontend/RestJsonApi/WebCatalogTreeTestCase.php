<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class WebCatalogTreeTestCase extends FrontendRestJsonApiTestCase
{
    private ?int $initialWebCatalogId;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initialWebCatalogId = self::getConfigManager()->get('oro_web_catalog.web_catalog');
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (false !== $this->initialWebCatalogId) {
            $configManager = self::getConfigManager();
            $configManager->set('oro_web_catalog.web_catalog', $this->initialWebCatalogId);
            $configManager->flush();
        }
        parent::tearDown();
    }

    #[\Override]
    protected function preFixtureLoad(): void
    {
        parent::preFixtureLoad();

        $this->loadWebCatalog();
        $this->setCurrentWebsite();
        $this->switchToWebCatalog();
    }

    protected function loadWebCatalog(): void
    {
        $em = $this->getEntityManager();
        $webCatalog = new WebCatalog();
        $webCatalog->setName('Web Catalog 1');
        $webCatalog->setOrganization($em->getRepository(Organization::class)->getFirst());
        $this->getReferenceRepository()->setReference('catalog1', $webCatalog);
        $em->persist($webCatalog);
        $em->flush();
    }

    protected function switchToWebCatalog(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_web_catalog.web_catalog', $this->getReference('catalog1')->getId());
        $configManager->flush();
    }

    protected function switchToMasterCatalog(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_web_catalog.web_catalog', null);
        $configManager->flush();
    }

    protected function getCurrentLocalization(): Localization
    {
        /** @var UserLocalizationManager $localizationManager */
        $localizationManager = self::getContainer()->get('oro_frontend_localization.manager.user_localization');

        return $localizationManager->getCurrentLocalization();
    }

    protected function getCurrentLocalizationId(): int
    {
        return $this->getCurrentLocalization()->getId();
    }
}
