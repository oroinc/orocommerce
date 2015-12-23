<?php

namespace OroB2B\Bundle\TaxBundle\Provider;

class BuiltInTaxProvider implements TaxProviderInterface
{
    const NAME = 'orob2b_tax.provider.built-in';
    const LABEL = 'Built-In Provider';

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
