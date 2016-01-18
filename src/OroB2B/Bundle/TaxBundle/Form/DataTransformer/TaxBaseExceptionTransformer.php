<?php

namespace OroB2B\Bundle\TaxBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use OroB2B\Bundle\TaxBundle\Model\TaxBaseException;

class TaxBaseExceptionTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     * @param TaxBaseException[]|array $taxBaseExceptions
     */
    public function transform($taxBaseExceptions)
    {
        if (empty($taxBaseExceptions)) {
            return [];
        }

        $result = [];
        foreach ($taxBaseExceptions as $taxBaseException) {
            $result[] = $taxBaseException;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     * @param array $ids
     */
    public function reverseTransform($ids)
    {
        if (empty($ids)) {
            return [];
        }

        $taxCodes = [];
        foreach ($ids as $id) {
            $taxCodes[] = $id;
        }

        /*
         usort(
            $taxCodes,
            function ($a, $b) {
                /** @var TaxBaseException $a * /
                /** @var TaxBaseException $b * /
                return ($a->getCode() < $b->getCode()) ? -1 : 1;
            }
        );
        */

        return $taxCodes;
    }
}
