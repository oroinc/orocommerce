<?php

namespace Oro\Component\WebCatalog\Form;

use Symfony\Component\Form\AbstractType;

/**
 * Form type for page variant content variants.
 */
class PageVariantType extends AbstractType
{
    public const NAME = 'page_variant';

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
