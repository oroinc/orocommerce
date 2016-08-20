<?php

namespace Oro\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Entity\Repository\TaxRuleRepository;
use Oro\Bundle\TaxBundle\Entity\TaxRule;

abstract class AbstractMatcher implements MatcherInterface
{
    const CACHE_KEY_DELIMITER = ':';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $taxRuleClass;

    /**
     * @var array
     */
    protected $taxRulesCache = [];

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string $taxRuleClass
     */
    public function __construct(DoctrineHelper $doctrineHelper, $taxRuleClass)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->taxRuleClass = $taxRuleClass;
    }

    /**
     * @return TaxRuleRepository
     */
    protected function getTaxRuleRepository()
    {
        return $this->doctrineHelper->getEntityRepositoryForClass($this->taxRuleClass);
    }

    /**
     * @param ...TaxRule[]
     * @return TaxRule[]
     */
    protected function mergeResult()
    {
        $arguments = func_get_args();

        $result = [];

        /** @var TaxRule[] $argument */
        foreach ($arguments as $argument) {
            foreach ($argument as $taxRule) {
                $result[$taxRule->getId()] = $taxRule;
            }
        }

        return array_values($result);
    }

    /**
     * @param ...string[]
     * @return string
     */
    protected function getCacheKey()
    {
        $arguments = func_get_args();

        return implode(
            self::CACHE_KEY_DELIMITER,
            array_map(
                function ($argument) {
                    if ($argument instanceof Country) {
                        return $argument->getIso2Code();
                    } elseif ($argument instanceof Region) {
                        return $argument->getCombinedCode();
                    }

                    return (string)$argument;
                },
                $arguments
            )
        );
    }
}
