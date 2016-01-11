<?php

namespace OroB2B\Bundle\TaxBundle\Transformer;

use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultItem;

interface TaxTransformerInterface
{
    /**
     * Transform object to Result|ResultItem
     *
     * @param mixed $object
     * @return Result|ResultItem
     */
    public function transform($object);

    /**
     * Reverse transform Result|ResultItem to object
     *
     * @param Result|ResultItem $result
     * @return mixed
     */
    public function reverseTransform($result);
}
