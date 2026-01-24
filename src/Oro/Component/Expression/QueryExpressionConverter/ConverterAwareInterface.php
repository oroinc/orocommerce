<?php

namespace Oro\Component\Expression\QueryExpressionConverter;

/**
 * Defines the contract for converters that need access to a parent query expression converter.
 *
 * This interface allows converters to be aware of and delegate to a parent converter,
 * enabling hierarchical conversion of complex expression nodes.
 */
interface ConverterAwareInterface
{
    public function setConverter(QueryExpressionConverterInterface $converter);
}
