<?php

namespace OroB2B\Bundle\AttributeBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class AttributeDisabledFieldsTransformer implements DataTransformerInterface
{
    /**
     * @var array
     */
    protected $disabledFields = ['code', 'type'];

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (!$value) {
            return $value;
        }

        if (!is_array($value)) {
            throw new UnexpectedTypeException($value, 'array');
        }

        foreach ($this->disabledFields as $field) {
            if (array_key_exists($field, $value)) {
                $value[$field . 'Disabled'] = $value[$field];
            }
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return $value;
        }

        if (!is_array($value)) {
            throw new UnexpectedTypeException($value, 'array');
        }

        foreach ($this->disabledFields as $field) {
            if (array_key_exists($field . 'Disabled', $value)) {
                unset($value[$field . 'Disabled']);
            }
        }

        return $value;
    }
}
