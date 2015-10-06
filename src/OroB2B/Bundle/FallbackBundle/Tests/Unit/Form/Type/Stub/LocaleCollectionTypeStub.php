<?php

namespace OroB2B\Bundle\FallbackBundle\Tests\Unit\Form\Type\Stub;

use OroB2B\Bundle\FallbackBundle\Form\Type\LocaleCollectionType;

class LocaleCollectionTypeStub extends LocaleCollectionType
{
    /** {@inheritdoc} */
    public function __construct()
    {
    }

    /** {@inheritdoc} */
    protected function getLocales()
    {
        return [];
    }
}
