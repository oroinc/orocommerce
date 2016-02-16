<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

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
     * @param UnitResolver     $unitResolver
     * @param RowTotalResolver $rowTotalResolver
     */
    public function __construct(
        UnitResolver $unitResolver,
        RowTotalResolver $rowTotalResolver
    ) {
        $this->unitResolver = $unitResolver;
        $this->rowTotalResolver = $rowTotalResolver;
    }
}
