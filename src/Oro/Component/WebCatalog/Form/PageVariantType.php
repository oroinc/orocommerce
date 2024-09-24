<?php

namespace Oro\Component\WebCatalog\Form;

use Symfony\Component\Form\AbstractType;

class PageVariantType extends AbstractType
{
    const NAME = 'page_variant';

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
