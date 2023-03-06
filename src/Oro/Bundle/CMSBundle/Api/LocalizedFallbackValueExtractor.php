<?php

namespace Oro\Bundle\CMSBundle\Api;

use Oro\Bundle\LocaleBundle\Api\LocalizedFallbackValueExtractorInterface;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Adds extraction of API suitable value for WYSIWYG value.
 */
class LocalizedFallbackValueExtractor implements LocalizedFallbackValueExtractorInterface
{
    private const WYSIWYG_FIELD_VALUE = 'wysiwyg';
    private const WYSIWYG_FIELD_STYLE = 'wysiwygStyle';

    private LocalizedFallbackValueExtractorInterface $innerValueExtractor;
    private WYSIWYGValueRenderer $wysiwygValueRenderer;
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        LocalizedFallbackValueExtractorInterface $innerValueExtractor,
        WYSIWYGValueRenderer $wysiwygValueRenderer,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->innerValueExtractor = $innerValueExtractor;
        $this->wysiwygValueRenderer = $wysiwygValueRenderer;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritDoc}
     */
    public function extractValue(AbstractLocalizedFallbackValue $value): ?string
    {
        if ($this->propertyAccessor->isReadable($value, self::WYSIWYG_FIELD_VALUE)
            && $this->propertyAccessor->isReadable($value, self::WYSIWYG_FIELD_STYLE)
        ) {
            $wysiwygValue = $this->propertyAccessor->getValue($value, self::WYSIWYG_FIELD_VALUE);
            $wysiwygStyle = $this->propertyAccessor->getValue($value, self::WYSIWYG_FIELD_STYLE);
            if ($wysiwygValue || $wysiwygStyle) {
                return $this->wysiwygValueRenderer->render($wysiwygValue, $wysiwygStyle);
            }
        }

        return $this->innerValueExtractor->extractValue($value);
    }
}
