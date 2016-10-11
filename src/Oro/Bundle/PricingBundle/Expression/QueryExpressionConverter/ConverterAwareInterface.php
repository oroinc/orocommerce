<?php

namespace Oro\Bundle\PricingBundle\Expression\QueryExpressionConverter;

interface ConverterAwareInterface
{
    /**
     * @param QueryExpressionConverterInterface $converter
     */
    public function setConverter(QueryExpressionConverterInterface $converter);
}
