<?php

namespace Oro\Bundle\WebCatalogBundle\Validator\Constraint;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Component\WebCatalog\ContentVariantEntityProviderInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validate that all Content variant`s attached entities are in the same organization as a Web Catalog
 */
class SameOrganizationValidator extends ConstraintValidator
{
    /**
     * @var ContentVariantTypeRegistry
     */
    private $typeRegistry;

    /**
     * @var EntityOwnerAccessor
     */
    private $entityOwnerAccessor;

    public function __construct(
        ContentVariantTypeRegistry $typeRegistry,
        EntityOwnerAccessor $entityOwnerAccessor
    ) {
        $this->typeRegistry = $typeRegistry;
        $this->entityOwnerAccessor = $entityOwnerAccessor;
    }

    /**
     * @param SameOrganization $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof ContentVariant) {
            return;
        }

        $type = $this->typeRegistry->getContentVariantTypeByContentVariant($value);
        if (!$type instanceof ContentVariantEntityProviderInterface) {
            return;
        }

        $attachedEntity = $type->getAttachedEntity($value);
        $expectedOrganization = $value->getNode()->getWebCatalog()->getOrganization();

        $isValid = true;
        if ($attachedEntity instanceof Collection) {
            foreach ($attachedEntity as $item) {
                if (!$this->validateAttachedEntity($item, $expectedOrganization)) {
                    $isValid = false;
                    break;
                }
            }
        } else {
            $isValid = $this->validateAttachedEntity($attachedEntity, $expectedOrganization);
        }

        if (!$isValid) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }

    private function validateAttachedEntity(
        object $attachedEntity,
        ?OrganizationInterface $expectedOrganization
    ): bool {
        /** @var Organization $organization */
        $organization = $this->entityOwnerAccessor->getOrganization($attachedEntity);
        if (!$organization || !$expectedOrganization) {
            return true;
        }

        return $expectedOrganization && $organization->getId() === $expectedOrganization->getId();
    }
}
