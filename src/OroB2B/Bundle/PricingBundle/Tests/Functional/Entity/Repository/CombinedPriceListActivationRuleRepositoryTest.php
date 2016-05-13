<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;

/**
 * @dbIsolation
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

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $className = 'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule';
        $this->manager = $this->getContainer()->get('doctrine')
            ->getManagerForClass($className);
        $this->repository = $this->manager->getRepository($className);
        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists',
            ]
        );
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
        /** @var CombinedPriceList $cpl */
        $rules = $this->repository->getNewActualRules($now);
        $this->assertCount(1, $rules);
        $rule = $rules[0];
        $cpl = $this->getReference('1f');
        /** @var $rule CombinedPriceListActivationRule */
        $this->assertSame($cpl->getName(), $rule->getCombinedPriceList()->getName());
    }

    public function testDeleteExpiredRules()
    {
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

    /**
     * @param array $rulesData
     */
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
}
