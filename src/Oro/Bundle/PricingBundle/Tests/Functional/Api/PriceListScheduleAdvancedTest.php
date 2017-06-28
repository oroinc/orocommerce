<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class PriceListScheduleAdvancedTest extends AbstractPriceListScheduleTest
{
    public function testUpdateSchedulesIntersect()
    {
        $schedule = $this->getScheduleToTest();

        /** @var PriceListSchedule $scheduleToUpdate */
        $scheduleToUpdate = $this->getReference('schedule.2');

        $response = $this->request(
            'PATCH',
            $this->getUrl(
                'oro_rest_api_patch',
                [
                    'entity' => 'pricelistschedules',
                    'id' => $scheduleToUpdate->getId(),
                ]
            ),
            [
                'data' =>
                    [
                        'type' => 'pricelistschedules',
                        'id' => (string)$scheduleToUpdate->getId(),
                        'attributes' =>
                            [
                                'activeAt' => $schedule->getActiveAt()->format('c'),
                                'deactivateAt' => $schedule->getDeactivateAt()->format('c'),
                            ],
                    ],
            ]
        );

        static::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        static::assertContains(
            'Price list schedule segments should not intersect',
            $response->getContent()
        );
    }

    public function testCombinedPriceListBuildOnScheduleCreate()
    {
        $defaultPriceList = $this->getPriceListRepository()->getDefault();

        $createdActivationRules = $this->getCombinedPriceListActivationRuleRepository()->findAll();

        $this->assertCount(0, $createdActivationRules);

        $this->sendCreateScheduleRequest(
            new \DateTime(),
            new \DateTime('tomorrow'),
            $defaultPriceList
        );

        $createdActivationRules = $this->getCombinedPriceListActivationRuleRepository()->findAll();

        $this->assertGreaterThan(0, count($createdActivationRules));
    }

    public function testCombinedPriceListBuildOnScheduleUpdate()
    {
        $defaultPriceList = $this->getPriceListRepository()->getDefault();

        $priceListSchedule = new PriceListSchedule(new \DateTime(), new \DateTime('tomorrow'));
        $priceListSchedule->setPriceList($defaultPriceList);

        $this->getEntityManager()->persist($priceListSchedule);
        $this->getEntityManager()->flush();

        $createdActivationRules = $this->getCombinedPriceListActivationRuleRepository()->findAll();

        $this->assertCount(0, $createdActivationRules);

        $this->sendUpdateScheduleRequest(
            $priceListSchedule,
            new \DateTime('+2 days'),
            new \DateTime('+3 days')
        );

        $createdActivationRules = $this->getCombinedPriceListActivationRuleRepository()->findAll();

        $this->assertGreaterThan(0, count($createdActivationRules));
    }

    public function testCombinedPriceListBuildOnScheduleDelete()
    {
        $defaultPriceList = $this->getPriceListRepository()->getDefault();

        $priceListSchedule = new PriceListSchedule(new \DateTime(), new \DateTime('tomorrow'));
        $priceListSchedule->setPriceList($defaultPriceList);

        $priceListScheduleTwo = new PriceListSchedule(new \DateTime('+2 days'), new \DateTime('+3 days'));
        $priceListScheduleTwo->setPriceList($defaultPriceList);

        $this->getEntityManager()->persist($priceListSchedule);
        $this->getEntityManager()->persist($priceListScheduleTwo);
        $this->getEntityManager()->flush();

        $createdActivationRules = $this->getCombinedPriceListActivationRuleRepository()->findAll();

        $this->assertCount(0, $createdActivationRules);

        $this->delete(['entity' => 'pricelistschedules', 'id' => $priceListScheduleTwo->getId()]);

        $createdActivationRules = $this->getCombinedPriceListActivationRuleRepository()->findAll();

        $this->assertGreaterThan(0, count($createdActivationRules));
    }

    public function testCombinedPriceListBuildOnScheduleListDelete()
    {
        $defaultPriceList = $this->getPriceListRepository()->getDefault();

        $priceListSchedule = new PriceListSchedule(new \DateTime(), new \DateTime('tomorrow'));
        $priceListSchedule->setPriceList($defaultPriceList);

        $priceListScheduleTwo = new PriceListSchedule(new \DateTime('+2 days'), new \DateTime('+3 days'));
        $priceListScheduleTwo->setPriceList($defaultPriceList);

        $this->getEntityManager()->persist($priceListSchedule);
        $this->getEntityManager()->persist($priceListScheduleTwo);
        $this->getEntityManager()->flush();

        $createdActivationRules = $this->getCombinedPriceListActivationRuleRepository()->findAll();

        $this->assertCount(0, $createdActivationRules);

        $this->cdelete(
            ['entity' => 'pricelistschedules'],
            [
                'filter' => [
                    'id' => $priceListScheduleTwo->getId(),
                ],
            ]
        );

        $createdActivationRules = $this->getCombinedPriceListActivationRuleRepository()->findAll();

        $this->assertGreaterThan(0, count($createdActivationRules));
    }

    public function testCreateUpdatesScheduleContains()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_5');

        $this->assertFalse($priceList->isContainSchedule());

        $this->sendCreateScheduleRequest(new \DateTime(), new \DateTime(), $priceList);

        $this->assertTrue($priceList->isContainSchedule());
    }

    public function testDeleteScheduleContains()
    {
        /** @var PriceListSchedule $priceListSchedule */
        $priceListSchedule = $this->getReference('schedule.4');

        $this->assertTrue($priceListSchedule->getPriceList()->isContainSchedule());

        $this->delete(['entity' => 'pricelistschedules', 'id' => $priceListSchedule->getId()]);

        $this->assertFalse($priceListSchedule->getPriceList()->isContainSchedule());
    }

    public function testDeleteListScheduleContains()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $this->assertTrue($priceList->isContainSchedule());

        $this->cdelete(
            ['entity' => 'pricelistschedules'],
            [
                'filter' => [
                    'priceList' => $priceList->getId(),
                ],
            ]
        );

        $this->assertFalse($priceList->isContainSchedule());
    }
}
