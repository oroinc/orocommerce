<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PricingBundle\Entity\BaseCombinedPriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use OroB2B\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;

/**
 * @dbIsolation
 */
class CombinedPriceListScheduleResolverTest extends WebTestCase
{
    /**
     * @var CombinedPriceListScheduleResolver
     */
    protected $resolver;

    /**
     * @var EntityManagerInterface
     */
    protected $manager;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists'
            ]
        );
        $this->resolver = $this->getContainer()->get('orob2b_pricing.resolver.combined_product_schedule_resolver');
        $this->configManager = $this->getContainer()->get('oro_config.global');
    }

    /**
     * @dataProvider CPLSwitchingDataProvider
     * @param array $rules
     * @param array $cplRelationsExpected
     * @param array $cplConfig
     * @param \DateTime $now
     */
    public function testCPLSwitching(array $rules, array $cplRelationsExpected, array $cplConfig, \DateTime $now)
    {
        $this->setConfigCPL($cplConfig);
        $this->createActivationRules($rules);
        $this->resolver->updateRelations($now);
        $fullCPLName = $cplRelationsExpected['full'];
        $currentCPLName = $cplRelationsExpected['actual'];
        /** @var CombinedPriceList $fullCPL */
        $fullCPL = $this->getReference($fullCPLName);
        /** @var CombinedPriceList $currentCPL */
        $currentCPL = $this->getReference($currentCPLName);
        $relations = $this->getInvalidRelations(
            'OroB2BPricingBundle:CombinedPriceListToAccount',
            $fullCPL,
            $currentCPL
        );
        $this->assertEmpty($relations);
        $relations = $this->getInvalidRelations(
            'OroB2BPricingBundle:CombinedPriceListToAccountGroup',
            $fullCPL,
            $currentCPL
        );
        $this->assertEmpty($relations);
        $relations = $this->getInvalidRelations(
            'OroB2BPricingBundle:CombinedPriceListToWebsite',
            $fullCPL,
            $currentCPL
        );
        $this->assertEmpty($relations);
        $this->checkConfigCPL($cplConfig);
    }

    /**
     * @return array
     */
    public function CPLSwitchingDataProvider()
    {
        return [
            [
                'rules' => [
                    [
                        'activateAt' => $this->createDateTime(2),
                        'expireAt' => $this->createDateTime(3),
                        'fullChainPriceList' => '2f_1t_3t',
                        'combinedPriceList' => '2f',
                    ],
                ],
                'cplRelationsExpected' => [
                    'full' => '2f_1t_3t',
                    'actual' => '2f_1t_3t',
                ],
                'cplConfig' => [
                    'actualCpl' => '2f_1t_3t',
                    'fullCpl' => '2f_1t_3t',
                    'expectedActualCpl' => '2f_1t_3t',
                    'expectedFullCpl' => '2f_1t_3t',
                ],
                'now' => $this->createDateTime(1),
            ],
            [
                'rules' => [
                    [
                        'activateAt' => null,
                        'expireAt' => $this->createDateTime(5),
                        'fullChainPriceList' => '2f_1t_3t',
                        'combinedPriceList' => '2f',
                    ],
                ],
                'cplRelationsExpected' => [
                    'full' => '2f_1t_3t',
                    'actual' => '2f',
                ],
                'cplConfig' => [
                    'actualCpl' => '2f_1t_3t',
                    'fullCpl' => '2f_1t_3t',
                    'expectedActualCpl' => '2f',
                    'expectedFullCpl' => '2f_1t_3t',
                ],
                'now' => $this->createDateTime(4),
            ],
            [
                'rules' => [
                    [
                        'activateAt' => $this->createDateTime(5),
                        'expireAt' => null,
                        'fullChainPriceList' => '2f_1t_3t',
                        'combinedPriceList' => '2f',
                    ],
                ],
                'cplRelationsExpected' => [
                    'full' => '2f_1t_3t',
                    'actual' => '2f',
                ],
                'cplConfig' => [
                    'actualCpl' => '2f_1t_3t',
                    'fullCpl' => '2f_1t_3t',
                    'expectedActualCpl' => '2f',
                    'expectedFullCpl' => '2f_1t_3t',
                ],
                'now' => $this->createDateTime(6),
            ],
        ];
    }

    /**
     * @param array $rules
     */
    protected function createActivationRules(array $rules)
    {
        $manager = $this->getManager();
        foreach ($rules as $ruleData) {
            /** @var CombinedPriceList $cpl */
            $cpl = $this->getReference($ruleData['combinedPriceList']);
            /** @var CombinedPriceList $fullCpl */
            $fullCpl = $this->getReference($ruleData['fullChainPriceList']);

            $rule = new CombinedPriceListActivationRule();
            $rule->setActivateAt($ruleData['activateAt'])
                ->setExpireAt($ruleData['expireAt'])
                ->setCombinedPriceList($cpl)
                ->setFullChainPriceList($fullCpl);
            $manager->persist($rule);
        }
        $manager->flush();
    }

    /**
     * @param $timestamp
     * @return \DateTime
     */
    protected function createDateTime($timestamp)
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->setTimestamp($timestamp);

        return $date;
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getManager()
    {
        if (!$this->manager) {
            $this->manager = $this->getContainer()->get('doctrine')
                ->getManagerForClass('OroB2BPricingBundle:CombinedPriceListActivationRule');
        }

        return $this->manager;
    }

    /**
     * @param $entityName
     * @param CombinedPriceList $fullCPL
     * @param CombinedPriceList $currentCPL
     * @return BaseCombinedPriceListRelation[]
     */
    protected function getInvalidRelations($entityName, CombinedPriceList $fullCPL, CombinedPriceList $currentCPL)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getManager()->createQueryBuilder();
        $qb->select('r')
            ->from($entityName, 'r')
            ->where('r.fullChainPriceList = :fullCPl AND r.priceList != :currentCPL')
            ->setParameter('fullCPl', $fullCPL)
            ->setParameter('currentCPL', $currentCPL);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $cplConfig
     */
    protected function setConfigCPL(array $cplConfig)
    {
        $actualCPLConfigKey = Configuration::getConfigKeyToPriceList();
        $fullCPLConfigKey = Configuration::getConfigKeyToFullPriceList();
        $fullCpl = null;
        $actualCpl = null;
        if ($cplConfig['actualCpl']) {
            $actualCpl = $this->getReference($cplConfig['actualCpl'])->getId();
        }
        if ($cplConfig['fullCpl']) {
            $fullCpl = $this->getReference($cplConfig['fullCpl'])->getId();
        }
        $this->configManager->set($actualCPLConfigKey, $actualCpl);
        $this->configManager->set($fullCPLConfigKey, $fullCpl);

        $this->configManager->flush();
    }

    /**
     * @param array $cplConfig
     */
    protected function checkConfigCPL(array $cplConfig)
    {
        $this->configManager->reload();
        $fullCPLConfigKey = Configuration::getConfigKeyToFullPriceList();
        $actualCPLConfigKey = Configuration::getConfigKeyToPriceList();
        $expectedActualCpl = null;
        $expectedFullCpl = null;
        if ($cplConfig['expectedActualCpl']) {
            $expectedActualCpl = $this->getReference($cplConfig['expectedActualCpl'])->getId();
        }
        if ($cplConfig['expectedFullCpl']) {
            $expectedFullCpl = $this->getReference($cplConfig['expectedFullCpl'])->getId();
        }
        $this->assertSame($expectedActualCpl, $this->configManager->get($actualCPLConfigKey));
        $this->assertSame($expectedFullCpl, $this->configManager->get($fullCPLConfigKey));
    }
}
