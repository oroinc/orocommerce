<?php

namespace Oro\Bundle\ProductBundle\Entity\Builder;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeGroupManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provide functionality to create Product Families
 */
class ProductFamilyBuilder
{
    private const DEFAULT_FAMILY_CODE = 'default_family';

    /** @var array */
    private static $groups = [
        [
            'groupLabel' => 'General',
            'groupCode' => 'general',
            'attributes' => [
                'sku',
                'names',
                'descriptions',
                'shortDescriptions',
                'featured',
                'newArrival',
                'brand',
            ],
            'groupVisibility' => true,
        ],
        [
            'groupLabel' => 'Inventory',
            'groupCode' => 'inventory',
            'attributes' => [
                'inventory_status',
            ],
            'groupVisibility' => false,
        ],
        [
            'groupLabel' => 'Images',
            'groupCode' => 'images',
            'attributes' => [
                'images',
            ],
            'groupVisibility' => true,
        ],
        [
            'groupLabel' => 'Product Prices',
            'groupCode' => 'prices',
            'attributes' => [
                'productPriceAttributesPrices',
            ],
            'groupVisibility' => true,
        ],
        [
            'groupLabel' => 'SEO',
            'groupCode' => 'seo',
            'attributes' => [
                'metaKeywords',
                'metaDescriptions',
                'metaTitles',
            ],
            'groupVisibility' => false,
        ]
    ];

    /** @var TranslatorInterface */
    private $translator;

    /** @var AttributeGroupManager */
    private $attributeGroupManager;

    /** @var AttributeFamily */
    private $family;

    public function __construct(TranslatorInterface $translator, AttributeGroupManager $attributeGroupManager)
    {
        $this->translator = $translator;
        $this->attributeGroupManager = $attributeGroupManager;
    }

    /**
     * @param Organization $organization
     * @return $this
     */
    public function createDefaultFamily(Organization $organization): ProductFamilyBuilder
    {
        $this->family = new AttributeFamily();
        $this->family->setCode(self::DEFAULT_FAMILY_CODE);
        $this->family->setEntityClass(Product::class);
        $this->family->setOwner($organization);
        $this->family->setDefaultLabel(
            $this->translator->trans('oro.entityconfig.attribute.entity.attributefamily.default.label')
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function addDefaultAttributeGroups(): ProductFamilyBuilder
    {
        if (!$this->family instanceof AttributeFamily) {
            throw new \LogicException(
                sprintf('Attribute groups can only be added to an instance of %s.', AttributeFamily::class)
            );
        }
        $attributeGroups = $this->attributeGroupManager->createGroupsWithAttributes(Product::class, self::$groups);
        foreach ($attributeGroups as $attributeGroup) {
            $this->family->addAttributeGroup($attributeGroup);
        }

        return $this;
    }

    public function getFamily(): ?AttributeFamily
    {
        return $this->family;
    }
}
