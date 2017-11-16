<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\RelatedItem\Helper\RelatedItemConfigHelper;

class RelatedItemExtension extends \Twig_Extension
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
            new \Twig_SimpleFunction(
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
