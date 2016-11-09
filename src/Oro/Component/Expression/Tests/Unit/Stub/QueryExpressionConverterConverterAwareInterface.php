<?php

namespace Oro\Component\Expression\Tests\Unit\Stub;

use Oro\Component\Expression\QueryExpressionConverter\ConverterAwareInterface;
use Oro\Component\Expression\QueryExpressionConverter\QueryExpressionConverterInterface;

interface QueryExpressionConverterConverterAwareInterface extends
    QueryExpressionConverterInterface,
    ConverterAwareInterface
{

}
