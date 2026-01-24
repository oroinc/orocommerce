<?php

namespace Oro\Bundle\TaxBundle\Resolver;

/**
 * Thrown by tax resolvers to stop the tax resolution event propagation.
 *
 * When a tax resolver throws this exception, it signals that the tax calculation is complete
 * and no further resolvers should be executed. This is typically used when a resolver has determined
 * that tax calculation should be skipped (e.g., when loading cached tax values)
 * or when a resolver has fully satisfied the tax calculation requirements and subsequent resolvers are not needed.
 *
 * @see \Oro\Bundle\TaxBundle\Event\ResolverEventConnector
 */
class StopPropagationException extends \Exception
{
}
