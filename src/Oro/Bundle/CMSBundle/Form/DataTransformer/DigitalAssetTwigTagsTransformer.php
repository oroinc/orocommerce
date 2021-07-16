<?php

namespace Oro\Bundle\CMSBundle\Form\DataTransformer;

use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transforms a value contains digital asset TWIG tags to HTML string and vise versa.
 */
class DigitalAssetTwigTagsTransformer implements DataTransformerInterface
{
    private DigitalAssetTwigTagsConverter $digitalAssetTwigTagsConverter;

    public function __construct(DigitalAssetTwigTagsConverter $digitalAssetTwigTagsConverter)
    {
        $this->digitalAssetTwigTagsConverter = $digitalAssetTwigTagsConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        if (!$value) {
            return $value;
        }

        try {
            return $this->digitalAssetTwigTagsConverter->convertToUrls($value);
        } catch (\Throwable $e) {
            throw new TransformationFailedException('Failed to convert TWIG tags to URLs.', $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if ('' === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        try {
            return $this->digitalAssetTwigTagsConverter->convertToTwigTags($value);
        } catch (\Throwable $e) {
            throw new TransformationFailedException('Failed to convert URLs to TWIG tags.', $e->getCode(), $e);
        }
    }
}
