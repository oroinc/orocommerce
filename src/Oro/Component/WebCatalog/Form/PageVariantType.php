<?php

namespace Oro\Component\WebCatalog\Form;

use Symfony\Component\Form\AbstractType;

class PageVariantType extends AbstractType
{
    const NAME = 'page_variant';

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
