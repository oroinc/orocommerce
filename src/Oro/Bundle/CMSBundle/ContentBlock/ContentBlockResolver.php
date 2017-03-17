<?php

namespace Oro\Bundle\CMSBundle\ContentBlock;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\CMSBundle\ContentBlock\Model\ContentBlockView;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class ContentBlockResolver
{
    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(PropertyAccessor $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param ContentBlock $contentBlock
     * @param array $context
     * @return null|ContentBlockView
     */
    public function getContentBlockView(ContentBlock $contentBlock, array $context)
    {
        if (!$this->isContentBlockVisible($contentBlock, $context)) {
            return null;
        }

        $contentVariants = $contentBlock->getContentVariants();
        $mostSuitableContentVariant = $this->getMostSuitableContentVariant($contentVariants, $context);
        if (!$mostSuitableContentVariant) {
            $mostSuitableContentVariant = $this->getDefaultContentVariant($contentVariants);
        }

        return new ContentBlockView(
            $contentBlock->getAlias(),
            $contentBlock->getTitles(),
            $contentBlock->isEnabled(),
            $mostSuitableContentVariant->getContent()
        );
    }

    /**
     * @param ContentBlock $contentBlock
     * @param array        $context
     *
     * @return bool true if ContentBlock enabled and has at least one supported scope
     */
    private function isContentBlockVisible(ContentBlock $contentBlock, array $context)
    {
        if (!$contentBlock->isEnabled()) {
            return false;
        }

        $scopes = $contentBlock->getScopes();
        if ($scopes->isEmpty()) {
            return true;
        }

        foreach ($scopes as $scope) {
            if ($this->isScopeSuitable($scope, $context)) {
                return true; // at least one scope matched by context
            }
        }

        return false;
    }

    /**
     * @param Scope $scope
     * @param array $context
     *
     * @return bool
     */
    private function isScopeSuitable(Scope $scope, array $context)
    {
        foreach ($context as $criteriaPath => $criteriaValue) {
            $value = $this->propertyAccessor->getValue($scope, $criteriaPath);
            if ($value === null) {
                continue;
            }
            if ($value !== $criteriaValue) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Collection|TextContentVariant[] $contentVariants
     * @return TextContentVariant
     * @throws \RuntimeException
     */
    private function getDefaultContentVariant(Collection $contentVariants)
    {
        foreach ($contentVariants as $contentVariant) {
            if ($contentVariant->isDefault()) {
                return $contentVariant;
            }
        }

        throw new \RuntimeException('Default content variant should be defined.');
    }

    /**
     * @param Collection|TextContentVariant[] $variants
     * @param array                           $context
     *
     * @return TextContentVariant|null Variant with most suitable scope (scope that contains more matched criteria),
     * if there is no variants with matched scope returns default variant
     */
    private function getMostSuitableContentVariant(Collection $variants, array $context)
    {
        $variantsByPriority = [];
        foreach ($variants as $variant) {
            $maxScopePriority = 0;
            foreach ($variant->getScopes() as $scope) {
                $scopePriority = $this->getScopePriority($scope, $context);
                if ($maxScopePriority < $scopePriority) {
                    $maxScopePriority = $scopePriority;
                }
            }
            if ($maxScopePriority > 0) { // add only suitable variants to array
                $variantsByPriority[$maxScopePriority] = $variant;
            }
        }
        if (empty($variantsByPriority)) {
            return null;
        }
        krsort($variantsByPriority);

        return reset($variantsByPriority);
    }

    /**
     * @param Scope $scope
     * @param array $context
     * @return int Scope priority, 0 if scope not suitable for context
     */
    private function getScopePriority(Scope $scope, array $context)
    {
        $priority = 0;

        $criteriaWeight = count($context); // first field weight equal to context items count
        foreach ($context as $criteriaPath => $criteriaValue) {
            $value = $this->propertyAccessor->getValue($scope, $criteriaPath);
            if ($value === null) {
                continue; // fields without value doesn't affect priority
            }
            if ($value !== $criteriaValue) {
                return 0; // scope not suitable for context
            }
            $priority += pow(10, $criteriaWeight); // prevent affect of fields with lower weight
            $criteriaWeight--; // define next field weight
        }

        return $priority;
    }
}
