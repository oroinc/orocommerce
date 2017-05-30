<?php

namespace Oro\Bundle\PromotionBundle\Context;

interface ContextConverterInterface
{
    /**
     * @param PromotionContextInterface $context
     * @return array
     */
    public function convert(PromotionContextInterface $context): array;
}
