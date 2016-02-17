<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use OroB2B\Bundle\TaxBundle\Matcher\MatcherInterface;

abstract class AbstractItemResolver implements ResolverInterface
{
    /**
     * @var UnitResolver
     */
    protected $unitResolver;

    /**
     * @var RowTotalResolver
     */
    protected $rowTotalResolver;

    /**
     * @var MatcherInterface
     */
    protected $matcher;

    /**
     * @param UnitResolver $unitResolver
     * @param RowTotalResolver $rowTotalResolver
     * @param MatcherInterface $matcher
     */
    public function __construct(
        UnitResolver $unitResolver,
        RowTotalResolver $rowTotalResolver,
        MatcherInterface $matcher
    ) {
        $this->unitResolver = $unitResolver;
        $this->rowTotalResolver = $rowTotalResolver;
        $this->matcher = $matcher;
    }
}
