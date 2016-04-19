<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
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
     * @param DoctrineHelper $doctrineHelper
     * @param PriceListScheduleResolver $schedulerResolver
     */
    public function __construct(DoctrineHelper $doctrineHelper, PriceListScheduleResolver $schedulerResolver)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->schedulerResolver = $schedulerResolver;
    }

    /**
     * @param PriceList $priceList
     */
    public function buildByPriceList(PriceList $priceList)
    {
        $cplIterator = $this->doctrineHelper->getEntityRepository('OroB2BPricingBundle:CombinedPriceList')
            ->getCombinedPriceListsByPriceList($priceList);

        foreach ($cplIterator as $cpl) {
            $this->buildByCombinedPriceList($cpl);
        }
    }

    /**
     * @param CombinedPriceList $cpl
     */
    public function buildByCombinedPriceList(CombinedPriceList $cpl)
    {
        $newRules = $this->getActivationRules($cpl);

        //TODO: update\create activation plan. BB-2790
        //TODO: delete all rules except current rule, update expireAt to current rule
    }

    /**
     * @param CombinedPriceList $cpl
     * @return CombinedPriceListActivationRule[]
     */
    protected function getActivationRules(CombinedPriceList $cpl)
    {
        $priceListSchedules = $this->doctrineHelper
            ->getEntityRepository('OroB2BPricingBundle:PriceListSchedule')
            ->getSchedulesByCPL($cpl);
        $priceListRelations = $this->doctrineHelper
            ->getEntityRepository('OroB2BPricingBundle:CombinedPriceListToPriceList')
            ->findBy(['combinedPriceList' => $cpl], ['sortOrder']);

        $rawRules = $this->schedulerResolver->mergeSchedule($priceListSchedules, $priceListRelations);
        $lastRule = null;
        foreach ($rawRules as $rule) {
            //TODO: create Rules base on scheduler
            //TODO: connect empty CPL to rule
        }
    }
}
