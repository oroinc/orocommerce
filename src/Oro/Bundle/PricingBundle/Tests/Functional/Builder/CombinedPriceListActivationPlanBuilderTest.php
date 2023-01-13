<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Builder;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;
use Oro\Bundle\PricingBundle\PricingStrategy\MergePricesCombiningStrategy;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceListsForActivationPlan;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CombinedPriceListActivationPlanBuilderTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    /** @var CombinedPriceListActivationPlanBuilder */
    private $cplActivationPlanBuilder;

    /** @var \DateTime */
    private $now;

    /** @var EntityManager */
    private $manager;

    /** @var CombinedPriceListActivationRuleRepository */
    private $activationRulesRepository;

    protected function setUp(): void
    {
        self::markTestSkipped('Must be fixed and unskipped in BB-21195');

        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        // Switch to merge by priority strategy to use old CPL naming
        self::getConfigManager()
            ->set('oro_pricing.price_strategy', MergePricesCombiningStrategy::NAME);
        $this->cplActivationPlanBuilder = $this->getContainer()
            ->get('oro_pricing.builder.combined_price_list_activation_plan_builder');
        $this->now = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->loadFixtures([
            LoadCombinedPriceListsForActivationPlan::class,
        ]);
        $this->manager = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(PriceListSchedule::class);
    }

    /**
     * @dataProvider activationPlanDataProvider
     */
    public function testBuildActivationPlan(array $schedule, array $combinedPriceListsChanges)
    {
        $this->updateSchedule($schedule);
        $this->comparePlan($combinedPriceListsChanges);
    }

    public function activationPlanDataProvider(): array
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

    private function updateSchedule(array $scheduleData): void
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

    private function comparePlan(array $combinedPriceListsChanges): void
    {
        foreach ($combinedPriceListsChanges as $cplKey => $plan) {
            /** @var CombinedPriceListActivationRule[] $rules */
            $rules = $this->getActivationRulesRepository()->findBy([], ['id' => 'ASC']);
            $this->assertCount(count($plan), $rules);
            foreach ($rules as $i => $rule) {
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
                $this->assertEquals(md5($cplKey), $rule->getFullChainPriceList()->getName());
                $this->assertEquals($rule->isActive(), $expectedData['active']);
                $this->assertEquals($rule->getActivateAt(), $activeAt);
                $this->assertEquals($rule->getExpireAt(), $expireAt);
            }
        }
    }

    private function getActivationRulesRepository(): CombinedPriceListActivationRuleRepository
    {
        if (!$this->activationRulesRepository) {
            $this->activationRulesRepository = $this->manager
                ->getRepository(CombinedPriceListActivationRule::class);
        }

        return $this->activationRulesRepository;
    }

    private function getCplName(array $priceLists): string
    {
        $name = [];
        foreach ($priceLists as $priceList => $merge) {
            if ($merge) {
                $name[] = $this->getReference($priceList)->getId() . 't';
            } else {
                $name[] = $this->getReference($priceList)->getId() . 'f';
            }
        }

        return md5(implode('_', $name));
    }
}
