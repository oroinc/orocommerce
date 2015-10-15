<?php

namespace OroB2B\Bundle\AccountBundle\Form\EventListener;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvent;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountAwareInterface;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroupAwareInterface;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;

class VisibilityPostSubmitListener extends VisibilityAbstractListener
{
    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $targetEntity = $form->getData();
        if (!$form->isValid() || !is_object($targetEntity) || !$targetEntity->getId()) {
            return;
        }

        $this->saveFormAllData($form);
        $this->saveFormAccountGroupData($form);
        $this->saveFormAccountData($form);

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
