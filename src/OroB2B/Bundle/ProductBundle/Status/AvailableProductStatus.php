<?php

namespace OroB2B\Bundle\ProductBundle\Status;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class AvailableProductStatus
{
    /** @var TranslatorInterface  */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public function getAvailableProductStatus()
    {
        return [
            Product::STATUS_DISABLED => $this->translator->trans('orob2b.product.status.disabled'),
            Product::STATUS_ENABLED => $this->translator->trans('orob2b.product.status.enabled')
        ];
    }
}
