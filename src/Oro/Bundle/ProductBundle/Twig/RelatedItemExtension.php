<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\RelatedItem\Helper\RelatedItemConfigHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to find out the translation key of a "related items" relation label:
 *   - get_related_items_translation_key
 */
class RelatedItemExtension extends AbstractExtension
{
    /** @var RelatedItemConfigHelper */
    private $helper;

    /**
     * @param RelatedItemConfigHelper $helper
     */
    public function __construct(RelatedItemConfigHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'get_related_items_translation_key',
                [$this, 'getRelatedItemsTranslationKey']
            ),
        ];
    }

    /**
     * @return string
     */
    public function getRelatedItemsTranslationKey()
    {
        return $this->helper->getRelatedItemsTranslationKey();
    }
}
