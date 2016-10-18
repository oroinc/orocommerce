<?php

namespace Oro\Bundle\VisibilityBundle\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Form\Type\EntityVisibilityType;
use Symfony\Component\Form\FormInterface;

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

    /**
     * @param ManagerRegistry $registry
     * @param VisibilityFormFieldDataProvider $formFieldDataProvider
     */
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
        if (!$visibilityForm->isValid() || !is_object($targetEntity) || !$targetEntity->getId()) {
            return;
        }

        $this->saveFormAllData($visibilityForm);
        $this->saveFormAccountGroupData($visibilityForm);
        $this->saveFormAccountData($visibilityForm);
    }

    /**
     * @param FormInterface $form
     */
    protected function saveFormAllData(FormInterface $form)
    {
        $targetEntity = $form->getData();
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

    /**
     * @param FormInterface $form
     */
    protected function saveFormAccountGroupData(FormInterface $form)
    {
        $this->saveFormFieldData($form, EntityVisibilityType::ACCOUNT_GROUP_FIELD);
    }

    /**
     * @param FormInterface $form
     */
    protected function saveFormAccountData(FormInterface $form)
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
        $visibilitiesData = $form->get($field)->getData();
        $visibilitiesEntity = $this->formFieldDataProvider
            ->findFormFieldData($form, $field);

        foreach ($visibilitiesData as $visibilityData) {
            $visibility = $visibilityData['data']['visibility'];

            if (!$visibility) {
                continue;
            }

            /** @var Account|AccountGroup $visibilityToEntity */
            $visibilityToEntity = $visibilityData['entity'];

            // todo skip saving if visibility wasn't changed BB-4506
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
