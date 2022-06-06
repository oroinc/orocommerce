<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Builder;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceListsForGC;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CombinedPriceListGarbageCollectorTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * @var CombinedPriceListGarbageCollector
     */
    private $gc;

    /**
     * @var int
     */
    private $prevCpl;

    /**
     * @var int
     */
    private $prevFullCpl;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->gc = $this->getContainer()
            ->get('oro_pricing.builder.combined_price_list_garbage_collector');

        $this->loadFixtures([
            LoadCombinedPriceListsForGC::class,
        ]);
        $this->manager = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(CombinedPriceList::class);

        /** @var CombinedPriceList $fullCpl */
        $fullCpl = $this->getReference('cpl_conf_f');
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getReference('cpl_conf');

        $cm = self::getConfigManager('global');
        $this->prevCpl = $cm->get('oro_pricing.combined_price_list');
        $this->prevFullCpl = $cm->get('oro_pricing.full_combined_price_list');
        $cm->set('oro_pricing.combined_price_list', $cpl->getId());
        $cm->set('oro_pricing.full_combined_price_list', $fullCpl->getId());
        $cm->flush();

        // Set offset to 0 to apply cleanups immediately
        $this->gc->setGcOffsetMinutes(0);
    }

    protected function tearDown(): void
    {
        $cm = self::getConfigManager('global');
        $cm->set('oro_pricing.combined_price_list', $this->prevCpl);
        $cm->set('oro_pricing.full_combined_price_list', $this->prevFullCpl);
        $cm->flush();

        // Revert the default offset
        $this->gc->setGcOffsetMinutes(60);

        parent::tearDown();
    }

    public function testCleanCombinedPriceLists()
    {
        $this->assertFalse($this->gc->hasPriceListsScheduledForRemoval());
        $this->gc->cleanCombinedPriceLists();

        $cpls = $this->manager->getRepository(CombinedPriceList::class)->findAll();
        $cplIds = array_map(
            static function (CombinedPriceList $combinedPriceList) {
                return $combinedPriceList->getId();
            },
            $cpls
        );

        $expectedToExist = [
            'cpl_ws_f',
            'cpl_ws',
            'cpl_ws_alt',
            'cpl_cg_f',
            'cpl_cg',
            'cpl_c_f',
            'cpl_c',
            'cpl_conf_f',
            'cpl_conf',
            'cpl_conf_alt'
        ];
        foreach ($expectedToExist as $ref) {
            $this->assertContains(
                $this->getReference($ref)->getId(),
                $cplIds,
                'CPL ' . $ref . ' was not expected to be removed'
            );
        }

        $expectedToBeRemoved = [
            'cpl_broken_ar_f',
            'cpl_broken_ar',
            'cpl_unassigned'
        ];
        foreach ($expectedToBeRemoved as $ref) {
            $this->assertContains(
                $this->getReference($ref)->getId(),
                $cplIds,
                'CPL ' . $ref . ' was not expected to be removed'
            );
        }

        $this->assertTrue($this->gc->hasPriceListsScheduledForRemoval());
        $this->gc->removeScheduledUnusedPriceLists();
        $cpls = $this->manager->getRepository(CombinedPriceList::class)->findAll();
        $cplIds = array_map(
            static function (CombinedPriceList $combinedPriceList) {
                return $combinedPriceList->getId();
            },
            $cpls
        );

        foreach ($expectedToBeRemoved as $ref) {
            $this->assertNotContains(
                $this->getReference($ref)->getId(),
                $cplIds,
                'CPL ' . $ref . ' was expected to be removed'
            );
        }
    }
}
