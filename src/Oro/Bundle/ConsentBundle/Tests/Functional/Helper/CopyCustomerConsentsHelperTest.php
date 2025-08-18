<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\Helper;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Helper\CopyCustomerConsentsHelper;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadConsentsData;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CopyCustomerConsentsHelperTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ?int $initialWebCatalogId;
    private CopyCustomerConsentsHelper $helper;
    private DoctrineHelper $doctrineHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadConsentsData::class]);

        $configManager = self::getConfigManager();
        $this->initialWebCatalogId = $configManager->get('oro_web_catalog.web_catalog');
        $configManager->set(
            'oro_web_catalog.web_catalog',
            $this->getReference(LoadWebCatalogData::CATALOG_1_USE_IN_ROUTING)->getId()
        );
        $configManager->flush();

        $this->doctrineHelper = self::getContainer()->get('oro_entity.doctrine_helper');
        $this->helper = self::getContainer()->get('oro_consent.helper.copy_customer_consents');
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_web_catalog.web_catalog', $this->initialWebCatalogId);
        $configManager->flush();
    }

    public function testCopyConsentsIfTargetHasNoAcceptedConsents(): void
    {
        $consentAcceptancesAttachedToSourceCustomer =
            [
                $this->getReference(
                    LoadConsentsData::getConsentAcceptanceRefFromConsentRef(
                        LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_CMS
                    )
                ),
                $this->getReference(
                    LoadConsentsData::getConsentAcceptanceRefFromConsentRef(
                        LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_SYSTEM
                    )
                ),
                $this->getReference(
                    LoadConsentsData::getConsentAcceptanceRefFromConsentRef(
                        LoadConsentsData::CONSENT_OPTIONAL_NODE2_WITH_CMS
                    )
                ),
                $this->getReference(
                    LoadConsentsData::getConsentAcceptanceRefFromConsentRef(
                        LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_CMS
                    )
                ),
                $this->getReference(
                    LoadConsentsData::getConsentAcceptanceRefFromConsentRef(
                        LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_SYSTEM
                    )
                ),
                $this->getReference(
                    LoadConsentsData::getConsentAcceptanceRefFromConsentRef(
                        LoadConsentsData::CONSENT_REQUIRED_NODE2_WITH_CMS
                    )
                ),
                $this->getReference(
                    LoadConsentsData::getConsentAcceptanceRefFromConsentRef(
                        LoadConsentsData::CONSENT_OPTIONAL_WITHOUT_NODE
                    )
                ),
                $this->getReference(
                    LoadConsentsData::getConsentAcceptanceRefFromConsentRef(
                        LoadConsentsData::CONSENT_REQUIRED_WITHOUT_NODE
                    )
                ),
            ];

        $customerUserWithConsents = $this->getReference(LoadCustomerUserData::EMAIL);
        $newCustomerUser = new CustomerUser();
        $this->helper->copyConsentsIfTargetHasNoAcceptedConsents(
            $customerUserWithConsents,
            $newCustomerUser
        );

        $entitiesOnInsert = $this->doctrineHelper
            ->getEntityManager(ConsentAcceptance::class)
            ->getUnitOfWork()
            ->getScheduledEntityInsertions();

        $expectedConsentAcceptances = $this->getSourceConsentAcceptanceFromTargetConsentAcceptance(
            $consentAcceptancesAttachedToSourceCustomer,
            $newCustomerUser
        );

        $missedConsentAcceptances = array_filter(
            $expectedConsentAcceptances,
            function ($expectedConsentAcceptance) use ($entitiesOnInsert) {
                return in_array($expectedConsentAcceptance, $entitiesOnInsert, true);
            }
        );

        $this->assertEmpty($missedConsentAcceptances);
    }

    private function getSourceConsentAcceptanceFromTargetConsentAcceptance(
        array $sourceConsentAcceptances,
        CustomerUser $targetCustomerUser
    ): array {
        $targetConsentAcceptances = [];
        foreach ($sourceConsentAcceptances as $sourceConsentAcceptance) {
            $consentAcceptance = new ConsentAcceptance();
            $consentAcceptance->setCustomerUser($targetCustomerUser);
            $consentAcceptance->setConsent($sourceConsentAcceptance->getConsent());

            if ($sourceConsentAcceptance->getLandingPage() instanceof Page) {
                $consentAcceptance->setLandingPage($sourceConsentAcceptance->getLandingPage());
            }
            $targetConsentAcceptances[] = $consentAcceptance;
        }

        return $targetConsentAcceptances;
    }
}
