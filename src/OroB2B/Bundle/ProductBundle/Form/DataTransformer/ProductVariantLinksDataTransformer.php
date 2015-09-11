<?php

namespace OroB2B\Bundle\ProductBundle\Form\DataTransformer;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\Form\DataTransformerInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductVariantLink;

class ProductVariantLinksDataTransformer implements DataTransformerInterface
{
    /**
     * @var Collection
     */
    private $variantLinks;

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            return null;
        }

        $this->variantLinks = $value;

        return [
            'appendVariants' => [],
            'removeVariants' => []
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return null;
        }

        $appendVariants = $value['appendVariants'];
        $removeVariants = $value['removeVariants'];

        $this->appendVariants($appendVariants);
        $this->removeVariants($removeVariants);

        return $this->variantLinks;
    }

    /**
     * @param Product[] $variants
     */
    private function appendVariants(array $variants)
    {
        foreach ($variants as $variant) {
            $this->variantLinks->add(new ProductVariantLink(null, $variant));
        }
    }

    /**
     * @param Product[] $variants
     * @return null
     */
    private function removeVariants(array $variants)
    {
        if (!$variants) {
            return null;
        }

        foreach ($this->variantLinks as $variantLink) {
            if (in_array($variantLink->getProduct(), $variants)) {
                $this->variantLinks->removeElement($variantLink);
            }
        }
    }
}
