<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\EventListener\SortOrderDialogTriggerFormHandlerEventListener;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Data provider for category form template
 */
class CategoryFormTemplateDataProvider implements FormTemplateDataProviderInterface
{
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
            if ($request->hasSession()) {
                $session = $request->getSession();
                $sortOrderDialogTarget = $session->get(
                    SortOrderDialogTriggerFormHandlerEventListener::SORT_ORDER_DIALOG_TARGET,
                    ''
                );
                if ($sortOrderDialogTarget === $form->getName()) {
                    $data['triggerSortOrderDialog'] = true;
                    $session->remove(SortOrderDialogTriggerFormHandlerEventListener::SORT_ORDER_DIALOG_TARGET);
                }
            }
        }

        return $data;
    }
}
