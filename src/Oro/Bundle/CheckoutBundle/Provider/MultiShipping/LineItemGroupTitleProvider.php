<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping\GroupLineItemsByConfiguredFields;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides titles for certain line items group using field path and line item data.
 */
class LineItemGroupTitleProvider
{
    private const OTHER_ITEMS_TITLE = 'oro.checkout.line_items_grouping.other_items_group.title';
    protected const TITLE_PATH_MAPPING = [
        'product.id' => 'product.sku',
    ];

    private PropertyAccessorInterface $propertyAccessor;
    private EntityNameResolver $entityNameResolver;
    private TranslatorInterface $translator;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        EntityNameResolver $entityNameResolver,
        TranslatorInterface $translator
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->entityNameResolver = $entityNameResolver;
        $this->translator = $translator;
    }

    public function getTitle(string $path, CheckoutLineItem $lineItem): string
    {
        if ($path === GroupLineItemsByConfiguredFields::OTHER_ITEMS_KEY) {
            return $this->translator->trans(self::OTHER_ITEMS_TITLE);
        }

        // Extract value path
        $paths = explode(':', $path);

        $propertyPath = $paths[0];
        $this->applyPathMapping($propertyPath);

        $value = $this->propertyAccessor->getValue($lineItem, $propertyPath);

        if (is_object($value)) {
            return $this->entityNameResolver->getName($value);
        }

        return (string)$value;
    }

    protected function applyPathMapping(string &$propertyPath)
    {
        if (array_key_exists($propertyPath, static::TITLE_PATH_MAPPING)) {
            $propertyPath = static::TITLE_PATH_MAPPING[$propertyPath];
        }
    }
}
