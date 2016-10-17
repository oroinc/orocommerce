<?php

namespace Oro\Bundle\VisibilityBundle\Form\EventListener;

use Oro\Bundle\AccountBundle\Entity\AccountAwareInterface;
use Oro\Bundle\AccountBundle\Entity\AccountGroupAwareInterface;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Form\Type\EntityVisibilityType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

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

        $this->setFormAllData($form);
        $this->setFormAccountGroupData($form);
        $this->setFormAccountData($form);
    }

    /**
     * @param FormInterface $form
     */
    protected function setFormAllData(FormInterface $form)
    {
        $visibility = $this->findFormFieldData($form, EntityVisibilityType::ALL_FIELD);

        if ($visibility instanceof VisibilityInterface) {
            $data = $visibility->getVisibility();
        } else {
            $data = call_user_func(
                [$form->getConfig()->getOption(EntityVisibilityType::ALL_CLASS), 'getDefault'],
                $form->getData()
            );
        }
        $form->get(EntityVisibilityType::ALL_FIELD)->setData($data);
    }

    /**
     * @param FormInterface $form
     */
    protected function setFormAccountGroupData(FormInterface $form)
    {
        $visibilities = $this->findFormFieldData($form, EntityVisibilityType::ACCOUNT_GROUP_FIELD);

        $data = array_map(
            function ($visibility) {
                /** @var VisibilityInterface|AccountGroupAwareInterface $visibility */
                /** @noinspection PhpUndefinedMethodInspection - field added through entity extend */
                $accountGroup = $visibility->getScope()->getAccountGroup();

                return [
                    'entity' => $accountGroup,
                    'data' => [
                        'visibility' => $visibility->getVisibility(),
                    ],
                ];
            },
            $visibilities
        );

        $form->get(EntityVisibilityType::ACCOUNT_GROUP_FIELD)->setData($data);
    }

    /**
     * @param FormInterface $form
     */
    protected function setFormAccountData(FormInterface $form)
    {
        $visibilities = $this->findFormFieldData($form, EntityVisibilityType::ACCOUNT_FIELD);

        $data = array_map(
            function ($visibility) {
                /** @var VisibilityInterface|AccountAwareInterface $visibility */
                /** @noinspection PhpUndefinedMethodInspection - field added through entity extend */
                $account = $visibility->getScope()->getAccount();

                return [
                    'entity' => $account,
                    'data' => [
                        'visibility' => $visibility->getVisibility(),
                    ],
                ];
            },
            $visibilities
        );

        $form->get(EntityVisibilityType::ACCOUNT_FIELD)->setData($data);
    }
}
