<?php

namespace OroB2B\Bundle\PaymentBundle\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaypalPasswordSubscriber implements EventSubscriberInterface
{
    const PASSWORD_PLACEHOLDER = '**********';

    /**
     * @var FormInterface
     */
    protected $parent;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $event->setData(self::PASSWORD_PLACEHOLDER);
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        if ($data === self::PASSWORD_PLACEHOLDER) {
            $this->parent = $form->getParent();
            $this->parent->remove($form->getName());
        }
    }
}
