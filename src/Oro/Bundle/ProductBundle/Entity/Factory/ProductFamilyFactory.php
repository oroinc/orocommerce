<?php

namespace Oro\Bundle\ProductBundle\Entity\Factory;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Provide functionality to create Product Families
 */
class ProductFamilyFactory
{
    const DEFAULT_FAMILY_CODE = 'default_family';

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param TokenAccessorInterface $tokenAccessor
     * @param TranslatorInterface $translator
     */
    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        TranslatorInterface $translator
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->translator = $translator;
    }

    /**
     * @param Organization $organization
     * @param User|null $owner
     * @return AttributeFamily
     */
    public function createDefaultFamily(Organization $organization, User $owner = null)
    {
        $attributeFamily = new AttributeFamily();
        $attributeFamily->setCode(self::DEFAULT_FAMILY_CODE);
        $attributeFamily->setEntityClass(Product::class);
        $owner = $owner ?: $this->tokenAccessor->getUser();
        if ($owner) {
            $attributeFamily->setOwner($owner);
        }
        $attributeFamily->setDefaultLabel(
            $this->translator->trans('oro.entityconfig.attribute.entity.attributefamily.default.label')
        );
        $attributeFamily->setOrganization($organization);

        return $attributeFamily;
    }
}
