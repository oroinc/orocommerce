<?php

namespace Oro\Component\Expression\QueryExpressionConverter;

interface ConverterAwareInterface
{
    /**
     * @param QueryExpressionConverterInterface $converter
     */
    public function setConverter(QueryExpressionConverterInterface $converter);
}
