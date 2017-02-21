<?php

namespace Oro\Bundle\RedirectBundle\Form\DataTransformer;

use Oro\Bundle\RedirectBundle\Model\PrefixWithRedirect;
use Symfony\Component\Form\DataTransformerInterface;

class PrefixWithRedirectToStringTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
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
     * {@inheritdoc}
     * @param PrefixWithRedirect $value
     */
    public function reverseTransform($value)
    {
        if (null === $value) {
            return null;
        }

        return $value->getPrefix();
    }
}
