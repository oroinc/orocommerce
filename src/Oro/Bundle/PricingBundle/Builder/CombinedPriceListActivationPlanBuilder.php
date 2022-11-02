<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListScheduleRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListRelationHelperInterface;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\PricingBundle\Resolver\PriceListScheduleResolver;

/**
 * Generate activation plans for Combined Price Lists based on Price Lists Schedules.
 * Full Chain CPL - is a CPL which consists of all Price Lists in the chain.
 * Active CPL - is a CPL which consists of Price Lists active at the moment.
 * Activation plan may exist only for Full Chain CPLs that are assigned to some level: Config, Website, Group, Customer
 */
class CombinedPriceListActivationPlanBuilder
{
    public const SKIP_ACTIVATION_PLAN_BUILD = 'skip_activation_plan_build';

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
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var CombinedPriceListRelationHelperInterface
     */
    protected $relationHelper;

    /**
     * @var array
     */
    protected $processedPriceLists = [];

    /**
     * @var array
     */
    protected $processedCPLs = [];

    public function __construct(
        DoctrineHelper $doctrineHelper,
        PriceListScheduleResolver $schedulerResolver,
        CombinedPriceListProvider $combinedPriceListProvider,
        CombinedPriceListRelationHelperInterface $relationHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->schedulerResolver = $schedulerResolver;
        $this->combinedPriceListProvider = $combinedPriceListProvider;
        $this->relationHelper = $relationHelper;
    }

    public function buildByPriceList(PriceList $priceList)
    {
        if ($this->isPriceListProcessed($priceList)) {
            return;
        }
        $cplIterator = $this->getCombinedPriceListRepository()->getCombinedPriceListsByPriceList($priceList);

        foreach ($cplIterator as $cpl) {
            // Activation plan should be built only for Full Chain CPLs
            if ($this->relationHelper->isFullChainCpl($cpl)) {
                $this->buildByCombinedPriceList($cpl);
            }
        }
        $this->addPriceListProcessed($priceList);
    }

    public function buildByCombinedPriceList(CombinedPriceList $cpl)
    {
        if ($this->isCPLProcessed($cpl)) {
            return;
        }
        $this->getCPLActivationRuleRepository()->deleteRulesByCPL($cpl);
        $this->generateActivationRules($cpl);
        $this->addCPLProcessed($cpl);
    }

    protected function generateActivationRules(CombinedPriceList $cpl)
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $priceListSchedules = $this->getPriceListScheduleRepository()->getSchedulesByCPL($cpl);
        $priceListRelations = $this->getCPLToPriceListRepository()->getPriceListRelations($cpl);

        $entities = [];
        $rawRules = $this->schedulerResolver->mergeSchedule($priceListSchedules, $priceListRelations);
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
            $actualCPL = $this->combinedActualCombinedPriceList(
                $priceListRelations,
                $ruleData[PriceListScheduleResolver::PRICE_LISTS_KEY]
            );

            $rule->setCombinedPriceList($actualCPL);
            $this->getManager()->persist($rule);
            $entities[] = $rule;
        }
        $this->getManager()->flush($entities);
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
            [self::SKIP_ACTIVATION_PLAN_BUILD => true]
        );
    }

    /**
     * @return CombinedPriceListRepository
     */
    protected function getCombinedPriceListRepository()
    {
        if (!$this->combinedPriceListRepository) {
            $this->combinedPriceListRepository = $this->doctrineHelper
                ->getEntityRepository(CombinedPriceList::class);
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
                ->getEntityRepository(PriceListSchedule::class);
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
                ->getEntityRepository(CombinedPriceListToPriceList::class);
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
                ->getEntityRepository(CombinedPriceListActivationRule::class);
        }
        return $this->CPLActivationRuleRepository;
    }

    /**
     * @return ObjectManager
     */
    public function getManager()
    {
        if (!$this->manager) {
            $this->manager = $this->doctrineHelper
                ->getEntityManagerForClass(CombinedPriceListActivationRule::class);
        }

        return $this->manager;
    }

    /**
     * @param PriceList $priceList
     * @return bool
     */
    protected function isPriceListProcessed(PriceList $priceList)
    {
        return !empty($this->processedPriceLists[$priceList->getId()]);
    }

    /**
     * @param CombinedPriceList $cpl
     * @return bool
     */
    protected function isCPLProcessed(CombinedPriceList $cpl)
    {
        return !empty($this->processedCPLs[$cpl->getId()]);
    }

    protected function addPriceListProcessed(PriceList $priceList)
    {
        $this->processedPriceLists[$priceList->getId()] = true;
    }

    protected function addCPLProcessed(CombinedPriceList $cpl)
    {
        $this->processedCPLs[$cpl->getId()] = true;
    }
}
