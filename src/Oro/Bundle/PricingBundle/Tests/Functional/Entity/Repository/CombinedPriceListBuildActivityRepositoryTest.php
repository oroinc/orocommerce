<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceListBuildActivity;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListBuildActivityRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CombinedPriceListBuildActivityRepositoryTest extends WebTestCase
{
    private CombinedPriceListBuildActivityRepository $repository;

    protected function setUp(): void
    {
        $this->initClient();

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository(CombinedPriceListBuildActivity::class);
        $this->loadFixtures([
            LoadCombinedPriceLists::class,
        ]);
    }

    public function testBuildActivityManipulations()
    {
        $jobId1 = 10;
        $jobId2 = 20;
        $cpl1 = $this->getReference('1t_2t_3t');
        $cpl2 = $this->getReference('1f');
        $cpl3 = $this->getReference('2t_3t');

        $this->assertCount(0, $this->repository->findAll());

        $this->repository->addBuildActivities([$cpl1, $cpl2, $cpl3], $jobId1);
        $this->repository->addBuildActivities([$cpl1, $cpl2], $jobId2);
        $this->assertCount(5, $this->repository->findAll());

        $this->repository->deleteActivityRecordsForCombinedPriceList($cpl2);
        $this->assertCount(3, $this->repository->findAll());

        $this->repository->deleteActivityRecordsForJob($jobId1);
        $this->assertCount(1, $this->repository->findAll());
    }
}
