<?php

namespace Oro\Bundle\CMSBundle\Form\EventSubscriber;

use Oro\Bundle\CMSBundle\Form\DataTransformer\DigitalAssetTwigTagsTransformer;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Converts digital asset URLs to TWIG tags and vice-versa on PRE_SET_DATA and PRE_SUBMIT form events.
 * Form data transformer is not used intentionally because it is impossible to pass extra context taken from
 * a form to a transformer.
 */
class DigitalAssetTwigTagsEventSubscriber implements EventSubscriberInterface
{
    private DigitalAssetTwigTagsConverter $digitalAssetTwigTagsConverter;

    public function __construct(DigitalAssetTwigTagsConverter $digitalAssetTwigTagsConverter)
    {
        $this->digitalAssetTwigTagsConverter = $digitalAssetTwigTagsConverter;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'onPreSetData',
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
        ];
    }

    public function onPreSetData(FormEvent $event): void
    {
        if (!$event->getData()) {
            return;
        }

        $event->setData($this->createTransformer($event->getForm())->transform($event->getData()));
    }

    public function onPreSubmit(FormEvent $event): void
    {
        if (!$event->getData()) {
            return;
        }

        $event->setData($this->createTransformer($event->getForm())->reverseTransform($event->getData()));
    }

    public function createTransformer(FormInterface $form): DigitalAssetTwigTagsTransformer
    {
        return new DigitalAssetTwigTagsTransformer($this->digitalAssetTwigTagsConverter, $this->getContext($form));
    }

    private function getContext(FormInterface $form): array
    {
        $parentForm = $form->getParent();
        if (!$parentForm || !$parentForm->getConfig()->getDataClass()) {
            $parentForm = $form->getRoot();
        }

        return [
            'entityClass' => (string)$parentForm->getConfig()->getDataClass(),
            'entityId' => $parentForm->getData()?->getId(),
            'fieldName' => $form->getName(),
        ];
    }
}
