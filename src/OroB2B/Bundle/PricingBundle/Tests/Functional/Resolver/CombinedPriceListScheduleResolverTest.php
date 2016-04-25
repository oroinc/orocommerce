<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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
    }

    /**
     * @dataProvider CPLSwitchingDataProvider
     * @param array $rules
     * @param $cplRelationsExpected
     * @param $now
     */
    public function testCPLSwitching(array $rules, $cplRelationsExpected, $now)
    {
        $cplRelationsEntityNames = [
            'OroB2BPricingBundle:CombinedPriceListToAccount',
            'OroB2BPricingBundle:CombinedPriceListToAccountGroup',
            'OroB2BPricingBundle:CombinedPriceListToWebsite',
        ];
        $this->createActivationRules($rules);
        $this->resolver->updateRelations($now);

        foreach ($cplRelationsExpected as $fullCPLName => $currentCPLName) {
            /** @var CombinedPriceList $fullCPL */
            $fullCPL = $this->getReference($fullCPLName);
            /** @var CombinedPriceList $currentCPL */
            $currentCPL = $this->getReference($currentCPLName);
            foreach ($cplRelationsEntityNames as $entityName) {
                $relations = $this->getInvalidRelations($entityName, $fullCPL, $currentCPL);
                $this->assertEmpty($relations);
            }
        }
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
                    '2f_1t_3t' => '2f_1t_3t'
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
                    '2f_1t_3t' => '2f'
                ],
                'now' => $this->createDateTime(4),
            ],
            [
                'rules' => [
                    [
                        'activateAt' => $this->createDateTime(7),
                        'expireAt' => null,
                        'fullChainPriceList' => '2f_1t_3t',
                        'combinedPriceList' => '2f',
                    ],
                ],
                'cplRelationsExpected' => [
                    '2f_1t_3t' => '2f'
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
}
