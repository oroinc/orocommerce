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

    private array $context;

    /**
     * @param DigitalAssetTwigTagsConverter $digitalAssetTwigTagsConverter
     * @param array{entityClass?: string, entityId?: int, fieldName?: string} $context The context in which the
     *  $data is converted. Example:
     *  [
     *      'entityClass' => 'Oro\Bundle\CMSBundle\Entity\Page',
     *      'entityId' => 42,
     *      'fieldName' => 'content',
     *  ]
     */
    public function __construct(DigitalAssetTwigTagsConverter $digitalAssetTwigTagsConverter, array $context = [])
    {
        $this->digitalAssetTwigTagsConverter = $digitalAssetTwigTagsConverter;
        $this->context = $context;
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
            return $this->digitalAssetTwigTagsConverter->convertToUrls($value, $this->context);
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
            return $this->digitalAssetTwigTagsConverter->convertToTwigTags($value, $this->context);
        } catch (\Throwable $e) {
            throw new TransformationFailedException('Failed to convert URLs to TWIG tags.', $e->getCode(), $e);
        }
    }
}
