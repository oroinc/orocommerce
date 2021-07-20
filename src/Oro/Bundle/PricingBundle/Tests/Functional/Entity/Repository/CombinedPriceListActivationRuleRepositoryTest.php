<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class CombinedPriceListActivationRuleRepositoryTest extends WebTestCase
{
    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var CombinedPriceListActivationRuleRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->manager = $this->getContainer()->get('doctrine')
            ->getManagerForClass(CombinedPriceListActivationRule::class);
        $this->repository = $this->manager->getRepository(CombinedPriceListActivationRule::class);
        $this->loadFixtures([
            LoadCombinedPriceLists::class,
        ]);
    }

    public function testDeleteRulesByCPL()
    {
        $data = [
            [
                'cplName' => '1f',
                'fullCPLName' => '1f',
            ],
            [
                'cplName' => '2f',
                'fullCPLName' => '2f',
            ],
        ];
        $this->createRules($data);
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getReference('1f');
        $rules = $this->repository->findBy(['fullChainPriceList' => $cpl]);
        $this->assertCount(1, $rules);
        $this->repository->deleteRulesByCPL($cpl);
        $rules = $this->repository->findAll();
        $this->assertCount(1, $rules);
        $rules = $this->repository->findBy(['fullChainPriceList' => $cpl]);
        $this->assertEmpty($rules);
        $cpl = $this->getReference('2f');
        $this->repository->deleteRulesByCPL($cpl);
        $rules = $this->repository->findBy(['fullChainPriceList' => $cpl]);
        $this->assertEmpty($rules);
    }

    public function testGetNewActualRules()
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $pastTime = new \DateTime('- 1 day', new \DateTimeZone('UTC'));
        $data = [
            [
                'cplName' => '2f',
                'fullCPLName' => '2f',
                'expiredAt' => $pastTime
            ],
            [
                'cplName' => '1f',
                'fullCPLName' => '2f',
                'activateAt' => $now
            ],
        ];
        $this->createRules($data);
        /** @var CombinedPriceListActivationRule[] $rules */
        $rules = $this->repository->getNewActualRules($now);
        $this->assertCount(1, $rules);
        $rule = $rules[0];
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getReference('1f');
        $this->assertSame($cpl->getName(), $rule->getCombinedPriceList()->getName());
    }

    public function testDeleteExpiredRules()
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $pastTime = new \DateTime('- 1 day', new \DateTimeZone('UTC'));
        $data = [
            [
                'cplName' => '2f',
                'fullCPLName' => '2f',
                'expiredAt' => $pastTime
            ],
            [
                'cplName' => '1f',
                'fullCPLName' => '2f',
                'activateAt' => $now
            ],
        ];
        $this->createRules($data);
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getReference('2f');
        $rules = $this->repository->findBy(['fullChainPriceList' => $cpl]);
        $this->assertCount(2, $rules);
        $this->repository->deleteRulesByCPL($cpl);
        $rules = $this->repository->findBy(['fullChainPriceList' => $cpl]);
        $this->assertCount(0, $rules);
    }

    public function testUpdateRulesActivity()
    {
        $data = [
            [
                'cplName' => '1f',
                'fullCPLName' => '2f',
            ],
            [
                'cplName' => '2f',
                'fullCPLName' => '2f',
            ],
        ];
        $this->createRules($data);
        $cpl = $this->getReference('2f');
        /** @var CombinedPriceListActivationRule[] $rules */
        $rules = $this->repository->findBy(['fullChainPriceList' => $cpl]);
        $this->assertCount(2, $rules);
        $this->assertFalse($rules[0]->isActive());
        $this->assertFalse($rules[1]->isActive());
        $this->repository->updateRulesActivity($rules, true);
        $rules = $this->repository->findBy(['fullChainPriceList' => $cpl]);
        $this->assertCount(2, $rules);
        $this->manager->refresh($rules[0]);
        $this->manager->refresh($rules[1]);
        $this->assertTrue($rules[0]->isActive());
        $this->assertTrue($rules[1]->isActive());
    }

    public function testDeleteUnlinkedRules()
    {
        $cpl1 = $this->getReference('2t_3t');
        $cpl2 = $this->getReference('2f');
        $this->prepareUnlinkedRulesData();

        $this->repository->deleteUnlinkedRules();
        // Check second call processed correctly (all rules already removed)
        $this->repository->deleteUnlinkedRules();

        /** @var CombinedPriceListActivationRule[] $rules1 */
        $rules1 = $this->repository->findBy(['fullChainPriceList' => $cpl1]);
        $this->assertCount(0, $rules1);

        /** @var CombinedPriceListActivationRule[] $rules1 */
        $rules2 = $this->repository->findBy(['fullChainPriceList' => $cpl2]);
        $this->assertNotEmpty($rules2);
    }

    public function testDeleteUnlinkedRulesSkipPriceList()
    {
        $cpl1 = $this->getReference('2t_3t');
        $cpl2 = $this->getReference('2f');
        $this->prepareUnlinkedRulesData();
        $this->repository->deleteUnlinkedRules([$cpl1]);

        /** @var CombinedPriceListActivationRule[] $rules1 */
        $rules1 = $this->repository->findBy(['fullChainPriceList' => $cpl1]);
        $this->assertNotEmpty($rules1);

        /** @var CombinedPriceListActivationRule[] $rules1 */
        $rules2 = $this->repository->findBy(['fullChainPriceList' => $cpl2]);
        $this->assertNotEmpty($rules2);
    }

    protected function createRules(array $rulesData)
    {
        foreach ($rulesData as $data) {
            $rule = new CombinedPriceListActivationRule();
            /** @var CombinedPriceList $cpl */
            $cpl = $this->getReference($data['cplName']);
            /** @var CombinedPriceList $fullCPL */
            $fullCPL = $this->getReference($data['fullCPLName']);
            $rule->setCombinedPriceList($cpl);
            $rule->setFullChainPriceList($fullCPL);
            if (!empty($data['activateAt'])) {
                $rule->setActivateAt($data['activateAt']);
            }
            if (!empty($data['expiredAt'])) {
                $rule->setExpireAt($data['expiredAt']);
            }
            $this->manager->persist($rule);
        }
        $this->manager->flush();
    }

    private function prepareUnlinkedRulesData(): void
    {
        $data = [
            [
                'fullCPLName' => '2t_3t',
                'cplName' => '2f'
            ],
            [
                'fullCPLName' => '2f',
                'cplName' => '2f'
            ],
        ];
        $this->createRules($data);

        $cpl1 = $this->getReference('2t_3t');
        $cpl2 = $this->getReference('2f');

        /** @var CombinedPriceListActivationRule[] $rules1 */
        $rules1 = $this->repository->findBy(['fullChainPriceList' => $cpl1]);
        $this->assertNotEmpty($rules1);

        /** @var CombinedPriceListActivationRule[] $rules1 */
        $rules2 = $this->repository->findBy(['fullChainPriceList' => $cpl2]);
        $this->assertNotEmpty($rules2);
    }

    public function testHasActivationRules()
    {
        $data = [
            [
                'cplName' => '2f',
                'fullCPLName' => '2f',
            ],
        ];
        $this->createRules($data);
        $cpl = $this->getReference('2f');
        $this->assertTrue($this->repository->hasActivationRules($cpl));
    }

    public function testHasActivationRulesFalse()
    {
        $cpl = $this->getReference('2f_1t_3t');
        $this->assertFalse($this->repository->hasActivationRules($cpl));
    }

    public function testGetActualRuleByCpl()
    {
        $pastTime = new \DateTime('- 1 day', new \DateTimeZone('UTC'));
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $futureTime = new \DateTime('+ 1 day', new \DateTimeZone('UTC'));
        $data = [
            [
                'cplName' => '2f',
                'fullCPLName' => '2f_1t_3t',
                'activateAt' => $pastTime,
                'expiredAt' => $futureTime
            ],
        ];
        $this->createRules($data);

        $cpl = $this->getReference('2f');
        $fullCpl = $this->getReference('2f_1t_3t');
        $rule = $this->repository->getActualRuleByCpl($fullCpl, $now);
        $this->assertInstanceOf(CombinedPriceListActivationRule::class, $rule);
        $this->assertSame($fullCpl, $rule->getFullChainPriceList());
        $this->assertSame($cpl, $rule->getCombinedPriceList());
        $this->assertEquals($pastTime, $rule->getActivateAt());
        $this->assertEquals($futureTime, $rule->getExpireAt());
    }

    public function testGetActiveRuleByScheduledCpl()
    {
        $pastTime = new \DateTime('- 1 day', new \DateTimeZone('UTC'));
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $futureTime = new \DateTime('+ 1 day', new \DateTimeZone('UTC'));
        $data = [
            [
                'cplName' => '2f',
                'fullCPLName' => '2f_1t_3t',
                'activateAt' => $pastTime,
                'expiredAt' => $futureTime
            ],
        ];
        $this->createRules($data);

        $cpl = $this->getReference('2f');
        $fullCpl = $this->getReference('2f_1t_3t');
        $rule = $this->repository->getActiveRuleByScheduledCpl($cpl, $now);
        $this->assertInstanceOf(CombinedPriceListActivationRule::class, $rule);
        $this->assertSame($fullCpl, $rule->getFullChainPriceList());
        $this->assertSame($cpl, $rule->getCombinedPriceList());
        $this->assertEquals($pastTime, $rule->getActivateAt());
        $this->assertEquals($futureTime, $rule->getExpireAt());
    }
}
