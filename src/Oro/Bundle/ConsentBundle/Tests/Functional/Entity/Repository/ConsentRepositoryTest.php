<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentRepository;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadConsentsData;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ConsentRepositoryTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ?int $initialWebCatalogId;
    private ConsentRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadConsentsData::class]);

        $configManager = self::getConfigManager();
        $this->initialWebCatalogId = $configManager->get('oro_web_catalog.web_catalog');
        $configManager->set(
            'oro_web_catalog.web_catalog',
            $this->getReference(LoadWebCatalogData::CATALOG_1_USE_IN_ROUTING)->getId()
        );
        $configManager->flush();

        $this->repository = self::getContainer()->get('doctrine')->getRepository(Consent::class);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_web_catalog.web_catalog', $this->initialWebCatalogId);
        $configManager->flush();
    }

    /**
     * @dataProvider getNonExistentConsentIdsProvider
     */
    public function testGetNonExistentConsentIds(
        callable $checkedConsentIdsCallback,
        array $expectedNonExistentConsentIds
    ): void {
        $checkedConsentIds = $checkedConsentIdsCallback();
        $actualNonExistentConsentIds = $this->repository->getNonExistentConsentIds($checkedConsentIds);

        self::assertArrayIntersectEquals(
            $expectedNonExistentConsentIds,
            array_values($actualNonExistentConsentIds)
        );
    }

    public function getNonExistentConsentIdsProvider(): array
    {
        return [
            'No consent ids' => [
                'checkedConsentIdsCallback' => function () {
                    return [];
                },
                'expectedNonExistentConsentIds' => [],
            ],
            'No removed consent ids' => [
                'checkedConsentIdsCallback' => function () {
                    return [
                        $this->getReference(LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_CMS)->getId(),
                        $this->getReference(LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_CMS)->getId()
                    ];
                },
                'expectedNonExistentConsentIds' => [],
            ],
            'Several removed consent ids' => [
                'checkedConsentIdsCallback' => function () {
                    return [
                        $this->getReference(LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_CMS)->getId(),
                        $this->getReference(LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_CMS)->getId(),
                        self::BIGINT - 1,
                        self::BIGINT
                    ];
                },
                'expectedNonExistentConsentIds' => [
                    self::BIGINT - 1,
                    self::BIGINT
                ],
            ],
        ];
    }
}
