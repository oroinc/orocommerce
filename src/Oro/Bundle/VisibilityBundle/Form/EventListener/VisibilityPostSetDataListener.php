<?php

namespace Oro\Bundle\VisibilityBundle\Form\EventListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroupAwareInterface;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Form\Type\EntityVisibilityType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class VisibilityPostSetDataListener
{
    /**
     * @var VisibilityFormFieldDataProvider
     */
    protected $fieldDataProvider;

    public function __construct(VisibilityFormFieldDataProvider $fieldDataProvider)
    {
        $this->fieldDataProvider = $fieldDataProvider;
    }

    public function onPostSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $targetEntity = $form->getData();
        if (!is_object($targetEntity) || !$targetEntity->getId()) {
            return;
        }

        $this->setFormAllData($form);
        $this->setFormCustomerGroupData($form);
        $this->setFormCustomerData($form);
    }

    protected function setFormAllData(FormInterface $form)
    {
        $visibility = $this->fieldDataProvider
            ->findFormFieldData($form, EntityVisibilityType::ALL_FIELD);

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

    protected function setFormCustomerGroupData(FormInterface $form)
    {
        $visibilities = $this->fieldDataProvider
            ->findFormFieldData($form, EntityVisibilityType::ACCOUNT_GROUP_FIELD);

        $data = array_map(
            function ($visibility) {
                /** @var VisibilityInterface|CustomerGroupAwareInterface $visibility */
                /** @noinspection PhpUndefinedMethodInspection - field added through entity extend */
                $customerGroup = $visibility->getScope()->getCustomerGroup();

                return [
                    'entity' => $customerGroup,
                    'data' => [
                        'visibility' => $visibility->getVisibility(),
                    ],
                ];
            },
            $visibilities
        );

        $form->get(EntityVisibilityType::ACCOUNT_GROUP_FIELD)->setData($data);
    }

    protected function setFormCustomerData(FormInterface $form)
    {
        $visibilities = $this->fieldDataProvider
            ->findFormFieldData($form, EntityVisibilityType::ACCOUNT_FIELD);

        $data = array_map(
            function ($visibility) {
                /** @var VisibilityInterface|CustomerAwareInterface $visibility */
                /** @noinspection PhpUndefinedMethodInspection - field added through entity extend */
                $customer = $visibility->getScope()->getCustomer();

                return [
                    'entity' => $customer,
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
