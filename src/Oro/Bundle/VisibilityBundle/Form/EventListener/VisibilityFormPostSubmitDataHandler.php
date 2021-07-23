<?php

namespace Oro\Bundle\VisibilityBundle\Form\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Form\Type\EntityVisibilityType;
use Symfony\Component\Form\FormInterface;

/**
 * Provides a method to save visibility form data
 */
class VisibilityFormPostSubmitDataHandler
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var VisibilityFormFieldDataProvider
     */
    protected $formFieldDataProvider;

    public function __construct(ManagerRegistry $registry, VisibilityFormFieldDataProvider $formFieldDataProvider)
    {
        $this->registry = $registry;
        $this->formFieldDataProvider = $formFieldDataProvider;
    }

    /**
     * @param FormInterface $visibilityForm
     * @param Product|Category $targetEntity
     */
    public function saveForm(FormInterface $visibilityForm, $targetEntity)
    {
        if (!$visibilityForm->isSubmitted()
            || !$visibilityForm->isValid()
            || !is_object($targetEntity)
            || !$targetEntity->getId()
        ) {
            return;
        }

        $this->saveFormAllData($visibilityForm);
        $this->saveFormCustomerGroupData($visibilityForm);
        $this->saveFormCustomerData($visibilityForm);
    }

    protected function saveFormAllData(FormInterface $form)
    {
        $targetEntity = $form->getData();
        if (!$form->has(EntityVisibilityType::ALL_FIELD)) {
            return;
        }
        $visibility = $form->get(EntityVisibilityType::ALL_FIELD)->getData();

        if (!$visibility) {
            return;
        }

        $visibilityEntity = $this->formFieldDataProvider
            ->findFormFieldData($form, EntityVisibilityType::ALL_FIELD);

        if (!$visibilityEntity) {
            $visibilityEntity = $this->formFieldDataProvider
                ->createFormFieldData($form, EntityVisibilityType::ALL_FIELD);
        }

        $this->saveVisibility($targetEntity, $visibilityEntity, $visibility);
    }

    protected function saveFormCustomerGroupData(FormInterface $form)
    {
        $this->saveFormFieldData($form, EntityVisibilityType::ACCOUNT_GROUP_FIELD);
    }

    protected function saveFormCustomerData(FormInterface $form)
    {
        $this->saveFormFieldData($form, EntityVisibilityType::ACCOUNT_FIELD);
    }

    /**
     * @param FormInterface $form
     * @param string $field
     */
    protected function saveFormFieldData(FormInterface $form, $field)
    {
        $targetEntity = $form->getData();
        if (!$form->has($field)) {
            return;
        }
        $visibilitiesData = $form->get($field)->getData();
        $visibilitiesEntity = $this->formFieldDataProvider
            ->findFormFieldData($form, $field);

        foreach ($visibilitiesData as $visibilityData) {
            $visibility = $visibilityData['data']['visibility'];

            if (!$visibility) {
                continue;
            }

            /** @var Customer|CustomerGroup $visibilityToEntity */
            $visibilityToEntity = $visibilityData['entity'];

            if (isset($visibilitiesEntity[$visibilityToEntity->getId()])) {
                $visibilityEntity = $visibilitiesEntity[$visibilityToEntity->getId()];
            } else {
                $visibilityEntity = $this->formFieldDataProvider
                    ->createFormFieldData($form, $field, $visibilityToEntity);
            }

            $this->saveVisibility($targetEntity, $visibilityEntity, $visibility);
        }
    }

    /**
     * @param Object $targetEntity
     * @param VisibilityInterface $visibilityEntity
     * @param string $visibility
     */
    protected function saveVisibility(
        $targetEntity,
        VisibilityInterface $visibilityEntity,
        $visibility
    ) {
        // manual handling of visibility entities must be performed here to avoid triggering of extra processes
        $em = $this->registry->getManagerForClass(ClassUtils::getClass($targetEntity));
        $visibilityEntity->setVisibility($visibility);
        if ($visibility !== $visibilityEntity->getDefault($targetEntity)) {
            $em->persist($visibilityEntity);
        } elseif ($visibilityEntity->getVisibility()) {
            $em->remove($visibilityEntity);
        }
    }
}
