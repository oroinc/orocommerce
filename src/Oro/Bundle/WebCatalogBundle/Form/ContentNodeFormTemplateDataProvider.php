<?php

namespace Oro\Bundle\WebCatalogBundle\Form;

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
    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    public function getData($entity, FormInterface $form, Request $request)
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

        if (!$form->isSubmitted() || $form->isValid()) {
            return $data;
        }

        $expandedContentVariantForms = [];
        /** @var \IteratorAggregate $contentVariantsForm */
        $contentVariantsForm = $data['form']->offsetGet('contentVariants');
        $iterator = $contentVariantsForm->getIterator();
        /** @var FormView $contentVariantForm */
        foreach ($iterator as $contentVariantForm) {
            /** @var ContentVariant $contentVariant */
            $contentVariant = $contentVariantForm->vars['value'];
            if ($contentVariant->isExpanded()) {
                $expandedContentVariantForms[] = $contentVariantForm;
            }
        }
        $data['expandedContentVariantForms'] = $expandedContentVariantForms;

        return $data;
    }
}
