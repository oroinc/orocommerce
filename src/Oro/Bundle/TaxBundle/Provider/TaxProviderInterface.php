<?php

namespace Oro\Bundle\TaxBundle\Provider;

interface TaxProviderInterface
{
    /**
     * Check if provider can be used
     *
     * @return bool
     */
    public function isApplicable();

    /**
     * Get provider name
     *
     * @return string
     */
    public function getName();

    /**
     * Return label key
     *
     * @return string
     */
    public function getLabel();
}
