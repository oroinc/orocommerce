<?php

namespace Oro\Bundle\ShippingBundle\Form\EventSubscriber;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodTypeConfigType;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Handles form events to synchronize shipping method type configurations in a collection form.
 */
class MethodTypeConfigCollectionSubscriber implements EventSubscriberInterface
{
    /**
     * @var ShippingMethodProviderInterface
     */
    protected $shippingMethodProvider;

    public function __construct(ShippingMethodProviderInterface $shippingMethodProvider)
    {
        $this->shippingMethodProvider = $shippingMethodProvider;
    }

    /**
     * @return array
     */
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SET_DATA => 'postSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }

    public function postSetData(FormEvent $event): void
    {
        /** @var FormInterface|Form[] $form */
        $form = $event->getForm();
        /** @var Collection|ShippingMethodTypeConfig[] $data */
        $data = $event->getData();

        if (!$data) {
            return;
        }

        $methodConfig = $form->getParent()->getData();
        $method = $this->shippingMethodProvider->getShippingMethod($methodConfig->getMethod());

        $renderedTypes = [];
        foreach ($data as $index => $typeConfig) {
            $type = $method->getType($typeConfig->getType());
            if ($type) {
                $this->createTypeForm($form, $index, $method, $type);
                $renderedTypes[] = $type->getIdentifier();
            } else {
                $this->removeTypeForm($form, $index);
            }
        }

        $index = count($data);
        foreach ($method->getTypes() as $type) {
            if (null !== $type && !in_array($type->getIdentifier(), $renderedTypes, true)) {
                $this->createTypeForm($form, $index, $method, $type);
                $entity = new ShippingMethodTypeConfig();
                $entity->setType($type->getIdentifier())
                    ->setMethodConfig($methodConfig);
                $data->set($index, $entity);
                $index++;
            }
        }
    }

    public function preSubmit(FormEvent $event): void
    {
        /** @var array $submittedData */
        $submittedData = $event->getData();
        $form = $event->getForm();

        if (!$event->getData()) {
            return;
        }

        $methodIdentifier = $form->getParent()->get('method')->getData();
        $method = $this->shippingMethodProvider->getShippingMethod($methodIdentifier);

        foreach ($submittedData as $index => $methodTypeData) {
            $type = $method->getType($methodTypeData['type']);
            if ($type) {
                $this->createTypeForm($form, $index, $method, $type);
            }
        }
    }

    /**
     * @param FormInterface $form
     * @param string $index
     * @param ShippingMethodInterface $method
     * @param ShippingMethodTypeInterface $type
     */
    protected function createTypeForm(
        FormInterface $form,
        $index,
        ShippingMethodInterface $method,
        ShippingMethodTypeInterface $type
    ): void {
        $form->add($index, ShippingMethodTypeConfigType::class, [
            'options_type' => $type->getOptionsConfigurationFormType(),
            'auto_initialize' => false,
            'label' => $type->getLabel(),
            'is_grouped' => $method->isGrouped(),
        ]);
    }

    /**
     * @param FormInterface $form
     * @param string $index
     */
    protected function removeTypeForm(
        FormInterface $form,
        $index
    ): void {
        $form->remove($index);
    }
}
