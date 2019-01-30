<?php

namespace Oro\Bundle\ConsentBundle\Form\DataTransformer;

use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfig;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfigConverter;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms form array of ConsentConfig objects to array of arrays like
 * [
 *     'consent' => $id,
 *     'sort_order' => $sortOrder,
 * ]
 * for storing in config, and back.
 */
class ConsentCollectionTransformer implements DataTransformerInterface
{
    /** @var ConsentConfigConverter */
    protected $converter;

    /**
     * @param ConsentConfigConverter $converter
     */
    public function __construct(ConsentConfigConverter $converter)
    {
        $this->converter = $converter;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (!is_array($value) || empty($value)) {
            return null;
        }

        return $this->converter->convertFromSaved($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        $value = array_filter(
            $value,
            function (ConsentConfig $consentConfig) {
                return $consentConfig->getConsent() !== null;
            }
        );
        return $this->converter->convertBeforeSave($value);
    }
}
