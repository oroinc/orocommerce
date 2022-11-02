<?php

namespace Oro\Bundle\WebCatalogBundle\Validator\Constraint;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validate uniqueness of slug prototypes created within same parent node.
 */
class UniqueSlugPrototypeValidator extends ConstraintValidator
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof ContentNode) {
            return;
        }

        // Skip root node
        if (!$value->getParentNode()) {
            return;
        }

        // Find non-unique prototype strings
        $intersection = array_intersect(
            $this->getPersistedSlugPrototypes($value->getParentNode(), $value),
            $this->getSlugPrototypeStrings($value->getSlugPrototypes())
        );
        // Remove empty values
        $intersection = array_filter($intersection);

        if ($intersection) {
            foreach ($value->getSlugPrototypes() as $idx => $slugPrototype) {
                $slugPrototypeString = mb_strtolower($slugPrototype->getString());
                if (in_array($slugPrototypeString, $intersection, true)) {
                    $this->context->buildViolation($constraint->message)
                        ->atPath(sprintf('slugPrototypes[%d]', $idx))
                        ->addViolation();
                }
            }
        }
    }

    private function getPersistedSlugPrototypes(ContentNode $parentNode, ContentNode $currentNode): array
    {
        $repository = $this->registry
            ->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class);

        return $repository->getSlugPrototypesByParent(
            $parentNode,
            $currentNode->getId() ? $currentNode : null
        );
    }

    /**
     * @param Collection $slugPrototypes
     * @return array|string[]
     */
    private function getSlugPrototypeStrings(Collection $slugPrototypes): array
    {
        return $slugPrototypes
            ->map(function (LocalizedFallbackValue $slugPrototype) {
                return mb_strtolower($slugPrototype->getString());
            })
            ->toArray();
    }
}
