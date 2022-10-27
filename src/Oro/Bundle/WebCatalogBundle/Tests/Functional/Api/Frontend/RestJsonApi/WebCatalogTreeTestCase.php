<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class WebCatalogTreeTestCase extends FrontendRestJsonApiTestCase
{
    private const WEB_CATALOG_CONFIG_NAME = 'oro_web_catalog.web_catalog';

    /** @var int|null */
    private $originalWebCatalog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalWebCatalog = false;
    }

    protected function tearDown(): void
    {
        if (false !== $this->originalWebCatalog) {
            $configManager = self::getConfigManager('global');
            $configManager->set(self::WEB_CATALOG_CONFIG_NAME, $this->originalWebCatalog);
            $configManager->flush();
        }
        parent::tearDown();
    }

    protected function preFixtureLoad()
    {
        parent::preFixtureLoad();

        $this->loadWebCatalog();
        $this->setCurrentWebsite();
        $this->switchToWebCatalog();
    }

    protected function loadWebCatalog()
    {
        $em = $this->getEntityManager();
        $webCatalog = new WebCatalog();
        $webCatalog->setName('Web Catalog 1');
        $webCatalog->setOrganization($em->getRepository(Organization::class)->getFirst());
        $this->getReferenceRepository()->setReference('catalog1', $webCatalog);
        $em->persist($webCatalog);
        $em->flush();
    }

    protected function switchToWebCatalog()
    {
        $configManager = self::getConfigManager('global');
        $this->originalWebCatalog = $configManager->get(self::WEB_CATALOG_CONFIG_NAME);
        $configManager->set(self::WEB_CATALOG_CONFIG_NAME, $this->getReference('catalog1')->getId());
        $configManager->flush();
    }

    protected function switchToMasterCatalog()
    {
        $configManager = self::getConfigManager('global');
        $configManager->set(self::WEB_CATALOG_CONFIG_NAME, null);
        $configManager->flush();
    }

    /**
     * @return Localization
     */
    protected function getCurrentLocalization()
    {
        /** @var UserLocalizationManager $localizationManager */
        $localizationManager = self::getContainer()->get('oro_frontend_localization.manager.user_localization');

        return $localizationManager->getCurrentLocalization();
    }

    /**
     * @return int
     */
    protected function getCurrentLocalizationId()
    {
        return $this->getCurrentLocalization()->getId();
    }
}
