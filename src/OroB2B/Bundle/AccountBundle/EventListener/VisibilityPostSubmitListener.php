<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\SoapBundle\Controller\Api\FormAwareInterface;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountAwareInterface;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroupAwareInterface;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Form\Type\EntityVisibilityType;

class VisibilityPostSubmitListener extends VisibilityAbstractListener
{
    /**
     * @param FormAwareInterface $event
     */
    public function onPostSubmit(FormAwareInterface $event)
    {
        $form = $event->getForm();

        if (!$form->has(EntityVisibilityType::VISIBILITY)) {
            return;
        }
        $visibilityForm = $form->get(EntityVisibilityType::VISIBILITY);

        $targetEntity = $visibilityForm->getData();
        if (!$visibilityForm->isValid() || !is_object($targetEntity) || !$targetEntity->getId()) {
            return;
        }

        $this->saveFormAllData($visibilityForm);
        $this->saveFormAccountGroupData($visibilityForm);
        $this->saveFormAccountData($visibilityForm);

        $this->getEntityManager($targetEntity)->flush();
    }

    /**
     * @param FormInterface $form
     */
    protected function saveFormAllData(FormInterface $form)
    {
        $targetEntity = $form->getData();
        $visibility = $form->get('all')->getData();
        $visibilityEntity = $this->findFormFieldData($form, 'all');

        if (!$visibilityEntity) {
            $visibilityEntity = $this->createFormFieldData($form, 'all');
        }

        $this->saveVisibility($targetEntity, $visibilityEntity, $visibility);
    }

    /**
     * @param FormInterface $form
     */
    protected function saveFormAccountGroupData(FormInterface $form)
    {
        $this->saveFormFieldData($form, 'accountGroup');
    }

    /**
     * @param FormInterface $form
     */
    protected function saveFormAccountData(FormInterface $form)
    {
        $this->saveFormFieldData($form, 'account');
    }

    /**
     * @param FormInterface $form
     * @param string $field
     */
    protected function saveFormFieldData(FormInterface $form, $field)
    {
        $targetEntity = $form->getData();
        $visibilitiesData = $form->get($field)->getData();
        $visibilitiesEntity = $this->findFormFieldData($form, $field);

        foreach ($visibilitiesData as $visibilityData) {
            $visibility = $visibilityData['data']['visibility'];
            /** @var AccountGroup|Account $visibilityToEntity */
            $visibilityToEntity = $visibilityData['entity'];

            if (isset($visibilitiesEntity[$visibilityToEntity->getId()])) {
                $visibilityEntity = $visibilitiesEntity[$visibilityToEntity->getId()];
            } else {
                $visibilityEntity = $this->createFormFieldData($form, $field);
                if ($visibilityEntity instanceof AccountGroupAwareInterface) {
                    $visibilityEntity->setAccountGroup($visibilityToEntity);
                } elseif ($visibilityEntity instanceof AccountAwareInterface) {
                    $visibilityEntity->setAccount($visibilityToEntity);
                }
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
        $em = $this->getEntityManager($targetEntity);
        if ($visibility !== $visibilityEntity->getDefault($targetEntity)) {
            $visibilityEntity->setVisibility($visibility);
            $em->persist($visibilityEntity);
        } elseif ($visibilityEntity->getVisibility()) {
            $em->remove($visibilityEntity);
        }
    }
}
