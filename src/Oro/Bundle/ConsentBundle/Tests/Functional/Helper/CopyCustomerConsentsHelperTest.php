<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\Helper;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Helper\CopyCustomerConsentsHelper;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadConsentsData;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CopyCustomerConsentsHelperTest extends WebTestCase
{
    /** @var CopyCustomerConsentsHelper */
    private $helper;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                LoadConsentsData::class,
            ]
        );

        $this->doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $this->helper = $this->getContainer()->get('oro_consent.helper.copy_customer_consents');
    }

    public function testCopyConsentsIfTargetHasNoAcceptedConsents()
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

    /**
     * @param array        $sourceConsentAcceptances
     * @param CustomerUser $targetCustomerUser
     *
     * @return array
     */
    private function getSourceConsentAcceptanceFromTargetConsentAcceptance(
        array $sourceConsentAcceptances,
        CustomerUser $targetCustomerUser
    ) {
        $targetConsentAcceptances = [];
        foreach ($sourceConsentAcceptances as $sourceConsentAcceptance) {
            /**
             * @var $consentAcceptance ConsentAcceptance
             */
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
