<?php

namespace OroB2B\Bundle\ProductBundle\Form\DataTransformer;

use OroB2B\Bundle\ProductBundle\Model\QuickAddRow;
use Symfony\Component\Form\DataTransformerInterface;

use OroB2B\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Symfony\Component\Form\Exception\TransformationFailedException;

class TextareaToRowCollectionTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        return (string) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        $collection = new QuickAddRowCollection();
        $lineNumber = 1;

        foreach (explode(PHP_EOL, $value) as $line) {
            $data = preg_split('/[\t\,]/', $line);
            $collection->add(new QuickAddRow(
                $lineNumber++,
                trim($data[0]),
                array_key_exists(1, $data) ? trim($data[1]) : null
            ));
        }

        return $collection;
    }
}
