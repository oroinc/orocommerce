<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentRepository;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadConsentsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ConsentRepositoryTest extends WebTestCase
{
    private ConsentRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadConsentsData::class]);

        $this->repository = self::getContainer()->get('doctrine')->getRepository(Consent::class);
    }

    /**
     * @dataProvider getNonExistentConsentIdsProvider
     */
    public function testGetNonExistentConsentIds(
        callable $checkedConsentIdsCallback,
        array $expectedNonExistentConsentIds
    ) {
        $checkedConsentIds = $checkedConsentIdsCallback();
        $actualNonExistentConsentIds = $this->repository->getNonExistentConsentIds($checkedConsentIds);

        $this->assertArrayIntersectEquals(
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
