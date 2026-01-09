<?php

namespace Oro\Bundle\RedirectBundle\Form\DataTransformer;

use Oro\Bundle\RedirectBundle\Model\PrefixWithRedirect;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms between string prefixes and PrefixWithRedirect objects.
 */
class PrefixWithRedirectToStringTransformer implements DataTransformerInterface
{
    #[\Override]
    public function transform($value): mixed
    {
        if (null === $value) {
            return null;
        }

        $result = new PrefixWithRedirect();
        $result->setPrefix($value);
        $result->setCreateRedirect(false);

        return $result;
    }

    /**
     * @param PrefixWithRedirect $value
     */
    #[\Override]
    public function reverseTransform($value): mixed
    {
        if (null === $value) {
            return null;
        }

        return $value->getPrefix();
    }
}
