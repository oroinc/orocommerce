<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Class that used to get product description used to set schema.org microdata depending on changed configuration
 */
class SchemaOrgProductDescriptionCommonProvider implements SchemaOrgProductDescriptionProviderInterface
{
    private HtmlTagHelper $tagHelper;

    private PropertyAccessorInterface $propertyAccessor;

    private LocalizationHelper $localizationHelper;

    private string $field;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        HtmlTagHelper $tagHelper,
        LocalizationHelper $localizationHelper,
        string $field
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->tagHelper = $tagHelper;
        $this->localizationHelper = $localizationHelper;
        $this->field = $field;
    }

    public function getDescription(
        Product $product,
        ?Localization $localization = null,
        ?object $scopeIdentifier = null
    ): string {
        $fieldValue = $this->propertyAccessor->getValue($product, $this->field);
        if ($fieldValue instanceof Collection) {
            if (!$fieldValue->count()
                || ($fieldValue->count() && $fieldValue[0] instanceof AbstractLocalizedFallbackValue)) {
                $description = $this->localizationHelper->getLocalizedValue($fieldValue, $localization);
            } else {
                throw new \LogicException(
                    sprintf(
                        'Value type of the field %s is not supported: %s. '
                        . 'Supported value types are: scalar or successors of %s.',
                        $this->field,
                        get_debug_type($fieldValue),
                        AbstractLocalizedFallbackValue::class
                    )
                );
            }
        } else {
            $description = $fieldValue;
        }

        return $this->tagHelper->stripTags((string)$description);
    }
}
