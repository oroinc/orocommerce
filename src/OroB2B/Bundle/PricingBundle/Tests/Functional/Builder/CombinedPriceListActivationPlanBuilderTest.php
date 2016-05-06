<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Builder;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListSchedule;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;

/**
 * @dbIsolation
 */
class CombinedPriceListActivationPlanBuilderTest extends WebTestCase
{
    /**
     * @var CombinedPriceListActivationPlanBuilder
     */
    protected $cplActivationPlanBuilder;

    /**
     * @var \DateTime
     */
    protected $now;

    /**
     * @var EntityManager
     */
    protected $manager;

    /**
     * @var CombinedPriceListActivationRuleRepository
     */
    protected $activationRulesRepository;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->cplActivationPlanBuilder = $this->getContainer()
            ->get('orob2b_pricing.builder.combined_price_list_activation_plan_builder');
        $this->now = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists',
            ]
        );
        $this->manager = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2B\Bundle\PricingBundle\Entity\PriceListSchedule');
    }

    /**
     * @dataProvider activationPlanDataProvider
     * @param $schedule
     * @param $combinedPriceListsChanges
     */
    public function testBuildActivationPlan($schedule, $combinedPriceListsChanges)
    {
        $this->updateSchedule($schedule);
        $this->comparePlan($combinedPriceListsChanges);
    }

    /**
     * @return array
     */
    public function activationPlanDataProvider()
    {
        return [
            [
                'schedule' => [],
                'combinedPriceListsChanges' => []
            ],
            [
                'schedule' => [
                    'price_list_2' => [
                        ['activateAt' => '+1 day', 'deactivateAt' => '+3 days'],
                    ],
                    'price_list_3' => [
                        ['activateAt' => '+2 day', 'deactivateAt' => null],
                    ],
                ],
                'combinedPriceListsChanges' => [
                    '1t_2t_3t' => [
                        [
                            'active' => false,
                            'activateAt' => null,
                            'expireAt' => '+1 days',
                            'priceLists' => ['price_list_1' => true]
                        ],
                        [
                            'active' => false,
                            'activateAt' => '+1 day',
                            'expireAt' => '+2 days',
                            'priceLists' => ['price_list_1' => true, 'price_list_2' => true]
                        ],
                        [
                            'active' => false,
                            'activateAt' => '+2 day',
                            'expireAt' => '+3 days',
                            'priceLists' => ['price_list_1' => true, 'price_list_2' => true, 'price_list_3' => true]
                        ],
                        [
                            'active' => false,
                            'activateAt' => '+3 day',
                            'expireAt' => null,
                            'priceLists' => ['price_list_1' => true, 'price_list_3' => true]
                        ],
                    ],
                ]
            ],
        ];
    }

    /**
     * @param array $scheduleData
     */
    protected function updateSchedule(array $scheduleData)
    {
        foreach ($scheduleData as $priceListKey => $schedule) {
            /** @var PriceList $priceList */
            $priceList = $this->getReference($priceListKey);
            foreach ($schedule as $scheduleItem) {
                $item = new PriceListSchedule();
                $item->setPriceList($priceList);
                if ($scheduleItem['activateAt']) {
                    $activateAt = clone $this->now;
                    $activateAt->modify($scheduleItem['activateAt']);
                    $item->setActiveAt($activateAt);
                }
                if ($scheduleItem['deactivateAt']) {
                    $activateAt = clone $this->now;
                    $activateAt->modify($scheduleItem['deactivateAt']);
                    $item->setDeactivateAt($activateAt);
                }
                $this->manager->persist($item);
                $this->manager->flush($item);
            }
        }
        foreach ($scheduleData as $priceListKey => $schedule) {
            /** @var PriceList $priceList */
            $priceList = $this->getReference($priceListKey);
            $this->cplActivationPlanBuilder->buildByPriceList($priceList);
        }
    }

    /**
     * @param $combinedPriceListsChanges
     */
    protected function comparePlan($combinedPriceListsChanges)
    {
        foreach ($combinedPriceListsChanges as $cplKey => $plan) {
            $totalRules = 0;
            $cpl = $this->getReference($cplKey);
            /** @var CombinedPriceListActivationRule[] $rules */
            $rules = $this->getActivationRulesRepository()->findBy(
                ['fullChainPriceList' => $cpl],
                ['id' => 'ASC']
            );
            foreach ($rules as $i => $rule) {
                $totalRules++;
                $expectedData = $plan[$i];
                $activeAt = null;
                if ($expectedData['activateAt']) {
                    $activeAt = clone $this->now;
                    $activeAt->modify($expectedData['activateAt']);
                }
                $expireAt = null;
                if ($expectedData['expireAt']) {
                    $expireAt = clone $this->now;
                    $expireAt->modify($expectedData['expireAt']);
                }
                $currentCPLName = $rule->getCombinedPriceList()->getName();
                $expectedCplName = $this->getCplName($expectedData['priceLists']);
                $this->assertSame($expectedCplName, $currentCPLName);
                $this->assertEquals($rule->isActive(), $expectedData['active']);
                $this->assertEquals($rule->getActivateAt(), $activeAt);
                $this->assertEquals($rule->getExpireAt(), $expireAt);

            }
            $this->assertSame(count($combinedPriceListsChanges[$cplKey]), $totalRules);
        }
    }

    /**
     * @return CombinedPriceListActivationRuleRepository
     */
    protected function getActivationRulesRepository()
    {
        if (!$this->activationRulesRepository) {
            $this->activationRulesRepository = $this->manager
                ->getRepository('OroB2BPricingBundle:CombinedPriceListActivationRule');
        }

        return $this->activationRulesRepository;
    }

    /**
     * @param array $priceLists
     * @return string
     */
    protected function getCplName(array $priceLists)
    {
        $name = [];
        foreach ($priceLists as $priceList => $merge) {
            if ($merge) {
                $name[] = $this->getReference($priceList)->getId() . 't';
            } else {
                $name[] = $this->getReference($priceList)->getId() . 'f';
            }
        }
        return md5(implode("_", $name));
    }
}
