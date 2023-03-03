<?php

namespace Oro\Bundle\WebCatalogBundle\Form;

use Oro\Bundle\CatalogBundle\EventListener\SortOrderDialogTriggerFormHandlerEventListener;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the list of expanded content variants if form was submitted with validation errors.
 */
class ContentNodeFormTemplateDataProvider implements FormTemplateDataProviderInterface
{
    public function getData($entity, FormInterface $form, Request $request): array
    {
        if (!$entity instanceof ContentNode) {
            throw new \InvalidArgumentException(
                sprintf('`%s` supports only `%s` instance as form data (entity).', self::class, ContentNode::class)
            );
        }

        $data = [
            'entity' => $entity,
            'form' => $form->createView(),
        ];

        if ($data['form']->offsetExists('contentVariants')) {
            $contentVariantsForm = $data['form']->offsetGet('contentVariants');

            if (!$form->isSubmitted() || $form->isValid()) {
                $this->handleDataWhenNotSubmitted($request, $contentVariantsForm, $data);

                return $data;
            }

            $data['expandedContentVariantForms'] = [];

            /** @var FormView $contentVariantForm */
            foreach ($contentVariantsForm as $contentVariantForm) {
                /** @var ContentVariant $contentVariant */
                $contentVariant = $contentVariantForm->vars['value'];
                if ($contentVariant->isExpanded()) {
                    $data['expandedContentVariantForms'][] = $contentVariantForm;
                }
            }
        }

        return $data;
    }

    private function handleDataWhenNotSubmitted(
        Request $request,
        FormView $contentVariantsForm,
        array &$data
    ): void {
        if ($request->hasSession()) {
            $session = $request->getSession();
            $sortOrderDialogTarget = $session->get(
                SortOrderDialogTriggerFormHandlerEventListener::SORT_ORDER_DIALOG_TARGET,
                ''
            );

            if (!$sortOrderDialogTarget) {
                return;
            }

            $data['expandedContentVariantForms'] = [];
            foreach ($contentVariantsForm as $contentVariantForm) {
                $contentVariantForm->vars['triggerSortOrderDialog'] = false;
                if ($contentVariantForm->vars['full_name'] === $sortOrderDialogTarget) {
                    $contentVariantForm->vars['triggerSortOrderDialog'] = true;
                    $data['expandedContentVariantForms'][] = $contentVariantForm;
                    $session->remove(SortOrderDialogTriggerFormHandlerEventListener::SORT_ORDER_DIALOG_TARGET);

                    break;
                }
            }
        }
    }
}
