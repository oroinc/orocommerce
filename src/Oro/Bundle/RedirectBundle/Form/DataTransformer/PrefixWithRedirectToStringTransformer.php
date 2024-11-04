<?php

namespace Oro\Bundle\RedirectBundle\Form\DataTransformer;

use Oro\Bundle\RedirectBundle\Model\PrefixWithRedirect;
use Symfony\Component\Form\DataTransformerInterface;

class PrefixWithRedirectToStringTransformer implements DataTransformerInterface
{
    #[\Override]
    public function transform($value)
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
    public function reverseTransform($value)
    {
        if (null === $value) {
            return null;
        }

        return $value->getPrefix();
    }
}
