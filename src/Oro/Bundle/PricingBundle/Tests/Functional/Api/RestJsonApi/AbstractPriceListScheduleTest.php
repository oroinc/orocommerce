<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListScheduleRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListSchedules;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractPriceListScheduleTest extends RestJsonApiTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures(
            [
                LoadPriceListSchedules::class,
            ]
        );
    }

    /**
     * @param int $entityId
     * @param string $associationName
     * @param string $associationClassName
     * @param string $expectedAssociationId
     */
    protected function assertGetRelationship(
        $entityId,
        $associationName,
        $associationClassName,
        $expectedAssociationId
    ) {
        $response = $this->getRelationship(
            ['entity' => 'pricelistschedules', 'id' => $entityId, 'association' => $associationName]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $this->getEntityType($associationClassName),
                    'id' => (string)$expectedAssociationId,
                ],
            ],
            $response
        );
    }

    /**
     * @return PriceListRepository
     */
    protected function getPriceListRepository(): PriceListRepository
    {
        return $this->getEntityManager()->getRepository(PriceList::class);
    }

    /**
     * @return PriceListScheduleRepository
     */
    protected function getSchedulesRepository(): PriceListScheduleRepository
    {
        return $this->getEntityManager()->getRepository(PriceListSchedule::class);
    }

    /**
     * @return CombinedPriceListActivationRuleRepository
     */
    protected function getCombinedPriceListActivationRuleRepository(): CombinedPriceListActivationRuleRepository
    {
        return $this->getEntityManager()->getRepository(CombinedPriceListActivationRule::class);
    }

    /**
     * @return PriceListSchedule
     */
    protected function getScheduleToTest()
    {
        return $this->getReference('schedule.1');
    }

    /**
     * @param \DateTime $activateAt
     * @param \DateTime $deactivateAt
     * @param PriceList $priceList
     *
     * @return Response
     */
    protected function sendCreateScheduleRequest(
        \DateTime $activateAt,
        \DateTime $deactivateAt,
        PriceList $priceList
    ): Response {
        return $this->post(
            ['entity' => 'pricelistschedules'],
            [
                'data' => [
                    'type' => 'pricelistschedules',
                    'attributes' => [
                        'activeAt' => $activateAt->format('c'),
                        'deactivateAt' => $deactivateAt->format('c'),
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => [
                                'type' => 'pricelists',
                                'id' => (string)$priceList->getId(),
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * @param PriceListSchedule $schedule
     * @param \DateTime $activateAt
     * @param \DateTime $deactivateAt
     *
     * @return Response
     */
    protected function sendUpdateScheduleRequest(
        PriceListSchedule $schedule,
        \DateTime $activateAt,
        \DateTime $deactivateAt
    ): Response {
        return $this->patch(
            ['entity' => 'pricelistschedules', 'id' => $schedule->getId()],
            [
                'data' =>
                    [
                        'type' => 'pricelistschedules',
                        'id' => (string)$schedule->getId(),
                        'attributes' =>
                            [
                                'activeAt' => $activateAt->format('c'),
                                'deactivateAt' => $deactivateAt->format('c'),
                            ],
                    ],
            ]
        );
    }
}
