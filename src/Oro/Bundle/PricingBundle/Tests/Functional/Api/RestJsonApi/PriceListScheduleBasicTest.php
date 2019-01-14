<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\PricingBundle\Entity\PriceList;

/**
 * @dbIsolationPerTest
 */
class PriceListScheduleBasicTest extends AbstractPriceListScheduleTest
{
    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'pricelistschedules', 'id' => '<toString(@schedule.3->id)>']
        );

        $this->assertResponseContains('price_list_schedule/price_list_schedules_get.yml', $response);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'pricelistschedules']);

        $this->assertResponseContains('price_list_schedule/price_list_schedules_get_list.yml', $response);
    }

    public function testGetListByPriceListFilter()
    {
        $response = $this->cget(
            ['entity' => 'pricelistschedules'],
            ['filter' => ['priceList' => '@price_list_1->id']]
        );

        $this->assertResponseContains('price_list_schedule/price_list_schedules_get_list_by_pl_filter.yml', $response);
    }

    public function testGetSubResourcePriceList()
    {
        $response = $this->getSubresource(
            ['entity' => 'pricelistschedules', 'id' => '@schedule.1->id', 'association' => 'priceList']
        );

        $this->assertResponseContains('price_list_schedule/price_list_schedules_get_sub_resources_pl.yml', $response);
    }

    public function testGetRelationships()
    {
        $schedule = $this->getScheduleToTest();

        $this->assertGetRelationship(
            $schedule->getId(),
            'priceList',
            PriceList::class,
            $schedule->getPriceList()->getId()
        );
    }

    public function testCreate()
    {
        $data = $this->getRequestData('price_list_schedule/price_list_schedules_create.yml');
        $response = $this->post(
            ['entity' => 'pricelistschedules'],
            $data
        );

        $this->assertResponseContains($data, $response);
    }

    public function testUpdate()
    {
        $schedule = $this->getScheduleToTest();

        $this->patch(
            ['entity' => 'pricelistschedules', 'id' => $schedule->getId()],
            'price_list_schedule/price_list_schedules_update.yml'
        );

        self::assertEquals(new \DateTime('2017-04-12T14:11:39Z'), $schedule->getActiveAt());
        self::assertEquals(new \DateTime('2017-04-24T14:11:39Z'), $schedule->getDeactivateAt());
    }

    public function testDelete()
    {
        $id = $this->getScheduleToTest()->getId();

        $this->delete(['entity' => 'pricelistschedules', 'id' => $id]);

        $priceListScheduleAfterDelete = $this->getSchedulesRepository()->find($id);

        $this->assertNull($priceListScheduleAfterDelete);
    }

    public function testDeleteList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $this->cdelete(
            ['entity' => 'pricelistschedules'],
            ['filter' => ['priceList' => $priceList->getId()]]
        );

        $removedSchedules = $this->getSchedulesRepository()->findBy(['priceList' => $priceList->getId()]);

        self::assertCount(0, $removedSchedules);
    }
}
