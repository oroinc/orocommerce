<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Utils\SortOrderDialogTargetStorage;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Data provider for category form template
 */
class CategoryFormTemplateDataProvider implements FormTemplateDataProviderInterface
{
    private SortOrderDialogTargetStorage $sortOrderDialogTargetStorage;

    public function __construct(SortOrderDialogTargetStorage $sortOrderDialogTargetStorage)
    {
        $this->sortOrderDialogTargetStorage = $sortOrderDialogTargetStorage;
    }

    /**
     * @param Category $entity
     * @param FormInterface $form
     * @param Request $request
     *
     * @return array
     */
    public function getData($entity, FormInterface $form, Request $request): array
    {
        if (!$entity instanceof Category) {
            throw new \InvalidArgumentException(
                sprintf('`%s` supports only `%s` instance as form data (entity).', self::class, Category::class)
            );
        }

        $data = [
            'entity' => $entity,
            'form' => $form->createView(),
            'categoryId' => $entity->getId(),
            'triggerSortOrderDialog' => false,
        ];

        if (!$form->isSubmitted() || $form->isValid()) {
            if ($this->sortOrderDialogTargetStorage->hasTarget(Category::class, $entity->getId())) {
                $data['triggerSortOrderDialog'] = true;
                $this->sortOrderDialogTargetStorage->removeTarget(Category::class, $entity->getId());
            }
        }

        return $data;
    }
}
