<?php

namespace OroB2B\Bundle\TaxBundle\Matcher;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

abstract class AbstractMatcher implements MatcherInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $taxRuleClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string         $taxRuleClass
     */
    public function __construct(DoctrineHelper $doctrineHelper, $taxRuleClass)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->taxRuleClass = $taxRuleClass;
    }
}
