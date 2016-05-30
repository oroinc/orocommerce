<?php

namespace OroB2B\Bundle\AccountBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountAwareInterface;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroupAwareInterface;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;

class VisibilityPostSetDataListener extends AbstractVisibilityListener
{
    /**
     * @param FormEvent $event
     */
    public function onPostSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $targetEntity = $form->getData();
        if (!is_object($targetEntity) || !$targetEntity->getId()) {
            return;
        }
        if (is_a($targetEntity, 'OroB2B\Bundle\ProductBundle\Entity\Product')
            && $targetEntity->getId()
            && $targetEntity->getPrimaryUnitPrecisionId()) {
            $targetEntity->getPrimaryUnitPrecision();
        }

        $this->setFormAllData($form);
        $this->setFormAccountGroupData($form);
        $this->setFormAccountData($form);
    }

    /**
     * @param FormInterface $form
     */
    protected function setFormAllData(FormInterface $form)
    {
        $visibility = $this->findFormFieldData($form, 'all');

        if ($visibility instanceof VisibilityInterface) {
            $data = $visibility->getVisibility();
        } else {
            $data = call_user_func([$form->getConfig()->getOption('allClass'), 'getDefault'], $form->getData());
        }
        $form->get('all')->setData($data);
    }

    /**
     * @param FormInterface $form
     */
    protected function setFormAccountGroupData(FormInterface $form)
    {
        $visibilities = $this->findFormFieldData($form, 'accountGroup');

        $data = array_map(function ($visibility) {
            /** @var VisibilityInterface|AccountGroupAwareInterface $visibility */
            return [
                'entity' => $visibility->getAccountGroup(),
                'data' => [
                    'visibility' => $visibility->getVisibility(),
                ],
            ];
        }, $visibilities);

        $form->get('accountGroup')->setData($data);
    }

    /**
     * @param FormInterface $form
     */
    protected function setFormAccountData(FormInterface $form)
    {
        $visibilities = $this->findFormFieldData($form, 'account');

        $data = array_map(function ($visibility) {
            /** @var VisibilityInterface|AccountAwareInterface $visibility */
            return [
                'entity' => $visibility->getAccount(),
                'data' => [
                    'visibility' => $visibility->getVisibility(),
                ],
            ];
        }, $visibilities);

        $form->get('account')->setData($data);
    }
}
