<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Builder;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Component\Testing\WebTestCase;

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
     * @var ObjectManager
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
        $this->now = new \DateTime();
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
    public function testA($schedule, $combinedPriceListsChanges)
    {
        $combinedPriceListsChanges2 = [];
        foreach ($combinedPriceListsChanges as $cplName => $cplSchedule) {
            foreach ($cplSchedule as $item) {
                $combinedPriceListsChanges2[$cplName][$this->getCplName($item['priceLists'])] = $item;
            }
        }
        $this->updateSchedule($schedule);
        $this->comparePlan($combinedPriceListsChanges2);
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
                        ['activateAt' => '+1 day', 'deactivateAt' => '+2 days'],
                    ],
                ],
                'combinedPriceListsChanges' => [
                    '1t_2t_3t' => [
                        [
                            'active' => true,
                            'activateAt' => null,
                            'expireAt' => '+2 days',
                            'priceLists' => ['price_list_1' => true, 'price_list_3' => true]
                        ],
                        [
                            'active' => false,
                            'activateAt' => '+1 day',
                            'expireAt' => '+2 days',
                            'priceLists' => ['price_list_1' => true, 'price_list_2' => true, 'price_list_3' => true]
                        ],
                        [
                            'active' => false,
                            'activateAt' => '+2 day',
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
            }
            $this->manager->flush();
            $this->cplActivationPlanBuilder->buildByPriceList($priceList);
        }
    }

    /**
     * @param $combinedPriceListsChanges
     */
    protected function comparePlan($combinedPriceListsChanges)
    {
        $totalRules = 0;
        foreach ($combinedPriceListsChanges as $cplKey => $plan) {
            $cpl = $this->getReference($cplKey);
            /** @var CombinedPriceListActivationRule[] $rules */
            $rules = $this->getActivationRulesRepository()->findBy(
                ['fullChainPriceList' => $cpl]
            );
            foreach ($rules as $rule) {
                $currentCPLName = $rule->getCombinedPriceList()->getName();
                $expectedData = $plan[$currentCPLName];
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
                $this->assertEquals($rule->isActive(), $expectedData['active']);
                $this->assertEquals($rule->getActivateAt(), $activeAt);
                $this->assertEquals($rule->getExpireAt(), $expireAt);

            }
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
        return implode("_", $name);
    }
}
