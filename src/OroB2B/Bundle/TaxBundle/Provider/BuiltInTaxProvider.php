<?php

namespace OroB2B\Bundle\TaxBundle\Provider;

class BuiltInTaxProvider implements TaxProviderInterface
{
    const NAME = 'built_in';
    const LABEL = 'orob2b.tax.providers.built_in.label';

    /**
     * {@inheritdoc}
     */
    public function isApplicable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return self::LABEL;
    }
}
