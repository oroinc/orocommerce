<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListScheduleRepository;
use OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use OroB2B\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use OroB2B\Bundle\PricingBundle\Resolver\PriceListScheduleResolver;

class CombinedPriceListActivationPlanBuilder
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var PriceListScheduleResolver
     */
    protected $schedulerResolver;

    /**
     * @var CombinedPriceListProvider
     */
    protected $combinedPriceListProvider;

    /**
     * @var CombinedPriceListRepository
     */
    protected $combinedPriceListRepository;

    /**
     * @var PriceListScheduleRepository
     */
    protected $priceListScheduleRepository;

    /**
     * @var CombinedPriceListToPriceListRepository
     */
    protected $CPLToPriceListRepository;

    /**
     * @var CombinedPriceListActivationRuleRepository
     */
    protected $CPLActivationRuleRepository;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param PriceListScheduleResolver $schedulerResolver
     * @param CombinedPriceListProvider $combinedPriceListProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        PriceListScheduleResolver $schedulerResolver,
        CombinedPriceListProvider $combinedPriceListProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->schedulerResolver = $schedulerResolver;
        $this->combinedPriceListProvider = $combinedPriceListProvider;
    }

    /**
     * @param PriceList $priceList
     */
    public function buildByPriceList(PriceList $priceList)
    {
        $cplIterator = $this->getCombinedPriceListRepository()->getCombinedPriceListsByPriceList($priceList);

        foreach ($cplIterator as $cpl) {
            $this->buildByCombinedPriceList($cpl);
        }
    }

    /**
     * @param CombinedPriceList $cpl
     */
    public function buildByCombinedPriceList(CombinedPriceList $cpl)
    {
        $this->getCPLActivationRuleRepository()->deleteRulesByCPL($cpl);
        $this->generateActivationRules($cpl);
    }

    /**
     * @param CombinedPriceList $cpl
     * @return CombinedPriceListActivationRule[]
     */
    protected function generateActivationRules(CombinedPriceList $cpl)
    {
        $priceListSchedules = $this->getPriceListScheduleRepository()->getSchedulesByCPL($cpl);
        $priceListRelations = $this->getCPLToPriceListRepository()->getPriceListRelations($cpl);

        $rawRules = $this->schedulerResolver->mergeSchedule($priceListSchedules, $priceListRelations);
        $activationRuleClass = 'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule';
        $manager = $this->doctrineHelper->getEntityManagerForClass($activationRuleClass);
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        foreach ($rawRules as $ruleData) {
            if ($ruleData[PriceListScheduleResolver::EXPIRE_AT_KEY] !== null
                && $now > $ruleData[PriceListScheduleResolver::EXPIRE_AT_KEY]) {
                //rule expired already, no need to add it to activation plan
                continue;
            }
            $rule = new CombinedPriceListActivationRule();
            $rule->setFullChainPriceList($cpl);
            if ($ruleData[PriceListScheduleResolver::EXPIRE_AT_KEY]) {
                $rule->setExpireAt($ruleData[PriceListScheduleResolver::EXPIRE_AT_KEY]);
            }
            if ($ruleData[PriceListScheduleResolver::ACTIVATE_AT_KEY]) {
                $rule->setActivateAt($ruleData[PriceListScheduleResolver::ACTIVATE_AT_KEY]);
            }
            if ($ruleData[PriceListScheduleResolver::ACTIVATE_AT_KEY] === null
                || $ruleData[PriceListScheduleResolver::ACTIVATE_AT_KEY] < $now) {
                $rule->setActive(true);
            }
            $actualCPL = $this->combinedActualCombinedPriceList(
                $priceListRelations,
                $ruleData[PriceListScheduleResolver::PRICE_LISTS_KEY]
            );
            $rule->setCombinedPriceList($actualCPL);
            $manager->persist($rule);
        }
        $manager->flush();
    }

    /**
     * @param CombinedPriceListToPriceList[] $priceListRelations
     * @param array $activePriceListIds
     * @return CombinedPriceList
     */
    protected function combinedActualCombinedPriceList(array $priceListRelations, array $activePriceListIds)
    {
        $sequence = [];
        foreach ($priceListRelations as $priceListRelation) {
            if (in_array($priceListRelation->getPriceList()->getId(), $activePriceListIds, true)) {
                $sequence[] = new PriceListSequenceMember(
                    $priceListRelation->getPriceList(),
                    $priceListRelation->isMergeAllowed()
                );
            }
        }
        return $this->combinedPriceListProvider->getCombinedPriceList(
            $sequence,
            CombinedPriceListProvider::BEHAVIOR_EMPTY
        );
    }

    /**
     * @return CombinedPriceListRepository
     */
    protected function getCombinedPriceListRepository()
    {
        if (!$this->combinedPriceListRepository) {
            $this->combinedPriceListRepository = $this->doctrineHelper
                ->getEntityRepository('OroB2BPricingBundle:CombinedPriceList');
        }

        return $this->combinedPriceListRepository;
    }

    /**
     * @return PriceListScheduleRepository
     */
    protected function getPriceListScheduleRepository()
    {
        if (!$this->priceListScheduleRepository) {
            $this->priceListScheduleRepository = $this->doctrineHelper
                ->getEntityRepository('OroB2BPricingBundle:PriceListSchedule');
        }

        return $this->priceListScheduleRepository;
    }

    /**
     * @return CombinedPriceListToPriceListRepository
     */
    protected function getCPLToPriceListRepository()
    {
        if (!$this->CPLToPriceListRepository) {
            $this->CPLToPriceListRepository = $this->doctrineHelper
                ->getEntityRepository('OroB2BPricingBundle:CombinedPriceListToPriceList');
        }

        return $this->CPLToPriceListRepository;
    }

    /**
     * @return CombinedPriceListActivationRuleRepository
     */
    protected function getCPLActivationRuleRepository()
    {
        if (!$this->CPLActivationRuleRepository) {
            $this->CPLActivationRuleRepository = $this->doctrineHelper
                ->getEntityRepository('OroB2BPricingBundle:CombinedPriceListActivationRule');
        }
        return $this->CPLActivationRuleRepository;
    }
}
