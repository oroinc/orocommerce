<?php

namespace Oro\Bundle\ProductBundle\Entity\Factory;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Provide functionality to create Product Families
 */
class ProductFamilyFactory
{
    const DEFAULT_FAMILY_CODE = 'default_family';

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param Organization $organization
     * @return AttributeFamily
     */
    public function createDefaultFamily(Organization $organization)
    {
        $attributeFamily = new AttributeFamily();
        $attributeFamily->setCode(self::DEFAULT_FAMILY_CODE);
        $attributeFamily->setEntityClass(Product::class);
        $attributeFamily->setOwner($organization);
        $attributeFamily->setDefaultLabel(
            $this->translator->trans('oro.entityconfig.attribute.entity.attributefamily.default.label')
        );

        return $attributeFamily;
    }
}
