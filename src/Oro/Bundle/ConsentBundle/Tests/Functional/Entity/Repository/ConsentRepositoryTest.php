<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentRepository;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadConsentsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ConsentRepositoryTest extends WebTestCase
{
    /**
     * @var ConsentRepository
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadConsentsData::class,
        ]);

        $this->repository = $this
            ->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass(Consent::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->repository);
    }

    /**
     * @dataProvider getNonExistentConsentIdsProvider
     *
     * @param callable $checkedConsentIdsCallback
     * @param array $expectedNonExistentConsentIds
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

    /**
     * @return array
     */
    public function getNonExistentConsentIdsProvider()
    {
        return [
            "No consent ids" => [
                'checkedConsentIdsCallback' => function () {
                    return [];
                },
                'expectedNonExistentConsentIds' => [],
            ],
            "No removed consent ids" => [
                'checkedConsentIdsCallback' => function () {
                    return [
                        $this->getReference(LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_CMS)->getId(),
                        $this->getReference(LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_CMS)->getId()
                    ];
                },
                'expectedNonExistentConsentIds' => [],
            ],
            "Several removed consent ids" => [
                'checkedConsentIdsCallback' => function () {
                    return [
                        $this->getReference(LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_CMS)->getId(),
                        $this->getReference(LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_CMS)->getId(),
                        PHP_INT_MAX - 1,
                        PHP_INT_MAX
                    ];
                },
                'expectedNonExistentConsentIds' => [
                    PHP_INT_MAX - 1,
                    PHP_INT_MAX
                ],
            ],
        ];
    }
}
