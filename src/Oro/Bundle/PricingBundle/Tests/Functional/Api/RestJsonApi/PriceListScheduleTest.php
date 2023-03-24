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

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class PriceListScheduleTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadPriceListSchedules::class]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequestDataFolderName(): string
    {
        return parent::getRequestDataFolderName() . DIRECTORY_SEPARATOR . 'price_list_schedule';
    }

    /**
     * {@inheritdoc}
     */
    protected function getResponseDataFolderName(): string
    {
        return parent::getResponseDataFolderName() . DIRECTORY_SEPARATOR . 'price_list_schedule';
    }

    private function getPriceListRepository(): PriceListRepository
    {
        return $this->getEntityManager()->getRepository(PriceList::class);
    }

    private function getSchedulesRepository(): PriceListScheduleRepository
    {
        return $this->getEntityManager()->getRepository(PriceListSchedule::class);
    }

    private function getCombinedPriceListActivationRuleRepository(): CombinedPriceListActivationRuleRepository
    {
        return $this->getEntityManager()->getRepository(CombinedPriceListActivationRule::class);
    }

    private function getFirstPriceList(): PriceList
    {
        return $this->getEntityManager()->getRepository(PriceList::class)
            ->createQueryBuilder('p')
            ->orderBy('p.id')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleResult();
    }

    private function sendCreateScheduleRequest(
        \DateTime $activateAt,
        \DateTime $deactivateAt,
        PriceList $priceList
    ): void {
        $this->post(
            ['entity' => 'pricelistschedules'],
            [
                'data' => [
                    'type'          => 'pricelistschedules',
                    'attributes'    => [
                        'activeAt'     => $activateAt->format('c'),
                        'deactivateAt' => $deactivateAt->format('c')
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => [
                                'type' => 'pricelists',
                                'id'   => (string)$priceList->getId()
                            ]
                        ]
                    ]
                ]
            ]
        );
    }

    private function sendUpdateScheduleRequest(
        PriceListSchedule $schedule,
        \DateTime $activateAt,
        \DateTime $deactivateAt
    ): void {
        $this->patch(
            ['entity' => 'pricelistschedules', 'id' => $schedule->getId()],
            [
                'data' =>
                    [
                        'type'       => 'pricelistschedules',
                        'id'         => (string)$schedule->getId(),
                        'attributes' => [
                            'activeAt'     => $activateAt->format('c'),
                            'deactivateAt' => $deactivateAt->format('c')
                        ]
                    ]
            ]
        );
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'pricelistschedules', 'id' => '<toString(@schedule.3->id)>']
        );

        $this->assertResponseContains('price_list_schedules_get.yml', $response);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'pricelistschedules']);

        $this->assertResponseContains('price_list_schedules_get_list.yml', $response);
    }

    public function testGetListByPriceListFilter()
    {
        $response = $this->cget(
            ['entity' => 'pricelistschedules'],
            ['filter' => ['priceList' => '@price_list_1->id']]
        );

        $this->assertResponseContains('price_list_schedules_get_list_by_pl_filter.yml', $response);
    }

    public function testCreate()
    {
        $data = [
            'data' => [
                'type'          => 'pricelistschedules',
                'attributes'    => [
                    'activeAt'     => '2017-04-12T14:11:39Z',
                    'deactivateAt' => '2017-04-24T14:11:39Z'
                ],
                'relationships' => [
                    'priceList' => ['data' => ['type' => 'pricelists', 'id' => '<toString(@price_list_1->id)>']]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'pricelistschedules'], $data);

        $this->assertResponseContains($data, $response);

        $schedule = $this->getEntityManager()->find(PriceListSchedule::class, (int)$this->getResourceId($response));
        self::assertTrue($schedule->getPriceList()->getSchedules()->contains($schedule));
    }

    public function testCreateTogetherWithPriceList()
    {
        $data = [
            'data'     => [
                'type'          => 'pricelistschedules',
                'attributes'    => [
                    'activeAt'     => '2017-04-12T14:11:39Z',
                    'deactivateAt' => '2017-04-24T14:11:39Z'
                ],
                'relationships' => [
                    'priceList' => ['data' => ['type' => 'pricelists', 'id' => 'new_price_list']]
                ]
            ],
            'included' => [
                [
                    'type'       => 'pricelists',
                    'id'         => 'new_price_list',
                    'attributes' => [
                        'name'                => 'New Price List 1',
                        'priceListCurrencies' => ['USD']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'pricelistschedules'], $data);

        $scheduleId = (int)$this->getResourceId($response);
        $schedule = $this->getEntityManager()->find(PriceListSchedule::class, $scheduleId);
        $expectedData = $data;
        $expectedData['data']['id'] = (string)$scheduleId;
        $expectedData['data']['relationships']['priceList']['data']['id'] = (string)$schedule->getPriceList()->getId();
        $expectedData['included'][0]['id'] = (string)$schedule->getPriceList()->getId();
        $expectedData['included'][0]['meta']['includeId'] = 'new_price_list';
        $expectedData['included'][0]['relationships']['schedules']['data'][] = [
            'type' => 'pricelistschedules',
            'id'   => (string)$scheduleId
        ];
        $this->assertResponseContains($expectedData, $response);

        self::assertTrue($schedule->getPriceList()->getSchedules()->contains($schedule));
    }

    public function testUpdate()
    {
        $scheduleId = $this->getReference('schedule.1')->getId();

        $data = [
            'data' => [
                'type'       => 'pricelistschedules',
                'id'         => '<toString(@schedule.1->id)>',
                'attributes' => [
                    'activeAt'     => '2017-04-12T14:11:39Z',
                    'deactivateAt' => '2017-04-24T14:11:39Z'
                ]
            ]
        ];
        $this->patch(['entity' => 'pricelistschedules', 'id' => (string)$scheduleId], $data);

        /** @var PriceListSchedule $schedule */
        $schedule = $this->getEntityManager()->find(PriceListSchedule::class, $scheduleId);
        self::assertEquals(new \DateTime('2017-04-12T14:11:39Z'), $schedule->getActiveAt());
        self::assertEquals(new \DateTime('2017-04-24T14:11:39Z'), $schedule->getDeactivateAt());
    }

    public function testDelete()
    {
        $scheduleId = $this->getReference('schedule.1')->getId();

        $this->delete(['entity' => 'pricelistschedules', 'id' => $scheduleId]);

        self::assertNull($this->getSchedulesRepository()->find($scheduleId));
    }

    public function testDeleteList()
    {
        $priceListId = $this->getReference('price_list_1')->getId();

        $this->cdelete(
            ['entity' => 'pricelistschedules'],
            ['filter' => ['priceList' => $priceListId]]
        );

        self::assertCount(0, $this->getSchedulesRepository()->findBy(['priceList' => $priceListId]));
    }

    public function testGetSubResourceForPriceList()
    {
        $response = $this->getSubresource(
            ['entity' => 'pricelistschedules', 'id' => '<toString(@schedule.1->id)>', 'association' => 'priceList']
        );

        $this->assertResponseContains('price_list_schedules_get_sub_resources_pl.yml', $response);
    }

    public function testGetRelationshipForPriceList()
    {
        $response = $this->getRelationship(
            ['entity' => 'pricelistschedules', 'id' => '<toString(@schedule.1->id)>', 'association' => 'priceList']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'pricelists',
                    'id'   => '<toString(@price_list_1->id)>'
                ]
            ],
            $response
        );
    }

    public function testUpdateSchedulesIntersect()
    {
        $schedule = $this->getReference('schedule.1');

        $response = $this->patch(
            ['entity' => 'pricelistschedules', 'id' => '<toString(@schedule.2->id)>'],
            [
                'data' => [
                    'type'       => 'pricelistschedules',
                    'id'         => '<toString(@schedule.2->id)>',
                    'attributes' => [
                        'activeAt'     => $schedule->getActiveAt()->format('c'),
                        'deactivateAt' => $schedule->getDeactivateAt()->format('c')
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'schedule intervals intersection constraint',
                'detail' => 'Price list schedule segments should not intersect'
            ],
            $response
        );
    }

    public function testUpdateSchedulesIntersectB()
    {
        $schedule = $this->getReference('schedule.1');

        $response = $this->patch(
            ['entity' => 'pricelistschedules', 'id' => '<toString(@schedule.2->id)>'],
            [
                'data' => [
                    'type'       => 'pricelistschedules',
                    'id'         => '<toString(@schedule.2->id)>',
                    'attributes' => [
                        'deactivateAt' => $schedule->getDeactivateAt()
                            ->add(new \DateInterval('P1D'))
                            ->format('c')
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'schedule intervals intersection constraint',
                'detail' => 'Price list schedule segments should not intersect'
            ],
            $response
        );
    }

    public function testCreateSchedulesIntersect()
    {
        $data = [
            'data' => [
                'type'          => 'pricelistschedules',
                'attributes'    => [
                    'activeAt'     => (new \DateTime('-2 day'))->format('c'),
                    'deactivateAt' => (new \DateTime())->format('c')
                ],
                'relationships' => [
                    'priceList' => ['data' => ['type' => 'pricelists', 'id' => '<toString(@price_list_1->id)>']]
                ]
            ]
        ];

        $response = $this->post(['entity' => 'pricelistschedules'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'schedule intervals intersection constraint',
                'detail' => 'Price list schedule segments should not intersect'
            ],
            $response
        );
    }

    public function testCombinedPriceListBuildOnScheduleCreate()
    {
        $priceList = $this->getFirstPriceList();

        $createdActivationRules = $this->getCombinedPriceListActivationRuleRepository()->findAll();
        self::assertCount(0, $createdActivationRules);

        $this->sendCreateScheduleRequest(
            new \DateTime(),
            new \DateTime('tomorrow'),
            $priceList
        );

        $createdActivationRules = $this->getCombinedPriceListActivationRuleRepository()->findAll();
        self::assertGreaterThan(0, count($createdActivationRules));
    }

    public function testCombinedPriceListBuildOnScheduleUpdate()
    {
        $priceList = $this->getFirstPriceList();

        $schedule = new PriceListSchedule(new \DateTime(), new \DateTime('tomorrow'));
        $schedule->setPriceList($priceList);
        $this->getEntityManager()->persist($schedule);
        $this->getEntityManager()->flush();

        $createdActivationRules = $this->getCombinedPriceListActivationRuleRepository()->findAll();
        self::assertCount(0, $createdActivationRules);

        $this->sendUpdateScheduleRequest(
            $schedule,
            new \DateTime('+2 days'),
            new \DateTime('+3 days')
        );

        $createdActivationRules = $this->getCombinedPriceListActivationRuleRepository()->findAll();
        self::assertGreaterThan(0, count($createdActivationRules));
    }

    public function testCombinedPriceListBuildOnScheduleDelete()
    {
        $priceList = $this->getFirstPriceList();

        $schedule = new PriceListSchedule(new \DateTime(), new \DateTime('tomorrow'));
        $schedule->setPriceList($priceList);

        $scheduleTwo = new PriceListSchedule(new \DateTime('+2 days'), new \DateTime('+3 days'));
        $scheduleTwo->setPriceList($priceList);

        $this->getEntityManager()->persist($schedule);
        $this->getEntityManager()->persist($scheduleTwo);
        $this->getEntityManager()->flush();

        $createdActivationRules = $this->getCombinedPriceListActivationRuleRepository()->findAll();
        self::assertCount(0, $createdActivationRules);

        $this->delete(['entity' => 'pricelistschedules', 'id' => $scheduleTwo->getId()]);

        $createdActivationRules = $this->getCombinedPriceListActivationRuleRepository()->findAll();
        self::assertGreaterThan(0, count($createdActivationRules));
    }

    public function testCombinedPriceListBuildOnScheduleListDelete()
    {
        $priceList = $this->getFirstPriceList();

        $schedule = new PriceListSchedule(new \DateTime(), new \DateTime('tomorrow'));
        $schedule->setPriceList($priceList);

        $scheduleTwo = new PriceListSchedule(new \DateTime('+2 days'), new \DateTime('+3 days'));
        $scheduleTwo->setPriceList($priceList);

        $this->getEntityManager()->persist($schedule);
        $this->getEntityManager()->persist($scheduleTwo);
        $this->getEntityManager()->flush();

        $createdActivationRules = $this->getCombinedPriceListActivationRuleRepository()->findAll();
        self::assertCount(0, $createdActivationRules);

        $this->cdelete(
            ['entity' => 'pricelistschedules'],
            ['filter' => ['id' => $scheduleTwo->getId()]]
        );

        $createdActivationRules = $this->getCombinedPriceListActivationRuleRepository()->findAll();
        self::assertGreaterThan(0, count($createdActivationRules));
    }

    public function testCombinedPriceListBuildOnScheduleCreateAsIncludedData()
    {
        $priceList = $this->getFirstPriceList();

        $createdActivationRules = $this->getCombinedPriceListActivationRuleRepository()->findAll();
        self::assertCount(0, $createdActivationRules);

        $this->patch(
            ['entity' => 'pricelists', 'id' => (string)$priceList->getId()],
            [
                'data'     => [
                    'type'          => 'pricelists',
                    'id'            => (string)$priceList->getId(),
                    'relationships' => [
                        'schedules' => [
                            'data' => [
                                ['type' => 'pricelistschedules', 'id' => 'new_schedule']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'pricelistschedules',
                        'id'         => 'new_schedule',
                        'attributes' => [
                            'activeAt'     => (new \DateTime())->format('c'),
                            'deactivateAt' => (new \DateTime('tomorrow'))->format('c')
                        ]
                    ]
                ]
            ]
        );

        $createdActivationRules = $this->getCombinedPriceListActivationRuleRepository()->findAll();
        self::assertGreaterThan(0, count($createdActivationRules));
    }

    public function testCombinedPriceListBuildOnScheduleUpdateAsIncludedData()
    {
        $priceList = $this->getFirstPriceList();

        $schedule = new PriceListSchedule(new \DateTime(), new \DateTime('tomorrow'));
        $schedule->setPriceList($priceList);
        $this->getEntityManager()->persist($schedule);
        $this->getEntityManager()->flush();

        $createdActivationRules = $this->getCombinedPriceListActivationRuleRepository()->findAll();
        self::assertCount(0, $createdActivationRules);

        $this->patch(
            ['entity' => 'pricelists', 'id' => (string)$priceList->getId()],
            [
                'data'     => [
                    'type'          => 'pricelists',
                    'id'            => (string)$priceList->getId(),
                    'relationships' => [
                        'schedules' => [
                            'data' => [
                                ['type' => 'pricelistschedules', 'id' => (string)$schedule->getId()]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'meta'       => ['update' => true],
                        'type'       => 'pricelistschedules',
                        'id'         => (string)$schedule->getId(),
                        'attributes' => [
                            'activeAt'     => (new \DateTime('+2 days'))->format('c'),
                            'deactivateAt' => (new \DateTime('+3 days'))->format('c')
                        ]
                    ]
                ]
            ]
        );

        $this->sendUpdateScheduleRequest(
            $schedule,
            new \DateTime('+2 days'),
            new \DateTime('+3 days')
        );

        $createdActivationRules = $this->getCombinedPriceListActivationRuleRepository()->findAll();
        self::assertGreaterThan(0, count($createdActivationRules));
    }

    public function testCreateUpdatesScheduleContains()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_5');
        $priceListId = $priceList->getId();

        self::assertFalse($priceList->isContainSchedule());

        $this->sendCreateScheduleRequest(new \DateTime(), new \DateTime(), $priceList);

        /** @var PriceList $priceList */
        $priceList = $this->getEntityManager()->find(PriceList::class, $priceListId);
        self::assertTrue($priceList->isContainSchedule());
    }

    public function testDeleteScheduleContains()
    {
        /** @var PriceListSchedule $schedule */
        $schedule = $this->getReference('schedule.4');
        $scheduleId = $schedule->getId();
        $priceListId = $schedule->getPriceList()->getId();

        self::assertTrue($schedule->getPriceList()->isContainSchedule());

        $this->delete(['entity' => 'pricelistschedules', 'id' => (string)$scheduleId]);

        /** @var PriceList $priceList */
        $priceList = $this->getEntityManager()->find(PriceList::class, $priceListId);
        self::assertFalse($priceList->isContainSchedule());
    }

    public function testDeleteListScheduleContains()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $priceListId = $priceList->getId();

        self::assertTrue($priceList->isContainSchedule());

        $this->cdelete(
            ['entity' => 'pricelistschedules'],
            ['filter' => ['priceList' => $priceListId]]
        );

        $priceList = $this->getEntityManager()->find(PriceList::class, $priceListId);
        self::assertFalse($priceList->isContainSchedule());
    }

    public function testCreateAsIncludedDataUpdatesScheduleContains()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_5');
        $priceListId = $priceList->getId();

        self::assertFalse($priceList->isContainSchedule());

        $this->patch(
            ['entity' => 'pricelists', 'id' => (string)$priceListId],
            [
                'data'     => [
                    'type'          => 'pricelists',
                    'id'            => (string)$priceListId,
                    'relationships' => [
                        'schedules' => [
                            'data' => [
                                ['type' => 'pricelistschedules', 'id' => 'new_schedule']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'pricelistschedules',
                        'id'         => 'new_schedule',
                        'attributes' => [
                            'activeAt'     => (new \DateTime())->format('c'),
                            'deactivateAt' => (new \DateTime())->format('c')
                        ]
                    ]
                ]
            ]
        );

        $priceList = $this->getEntityManager()->find(PriceList::class, $priceListId);
        self::assertTrue($priceList->isContainSchedule());
    }
}
