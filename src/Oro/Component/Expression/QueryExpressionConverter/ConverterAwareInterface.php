<?php

namespace Oro\Component\Expression\QueryExpressionConverter;

interface ConverterAwareInterface
{
    public function setConverter(QueryExpressionConverterInterface $converter);
}
