<?php

namespace OroB2B\Bundle\SaleBundle\Form\EventListener;

use Symfony\Component\Form\Extension\Core\EventListener\ResizeFormListener;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class QuoteToOrderResizeFormSubscriber extends ResizeFormListener
{
    /**
     * Copy-pasted from ResizeFormListener to provide ability to pass data to form
     *
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if (null === $data) {
            $data = [];
        }

        if (!is_array($data) && !($data instanceof \Traversable && $data instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($data, 'array or (\Traversable and \ArrayAccess)');
        }

        // First remove all rows
        foreach ($form as $name => $child) {
            $form->remove($name);
        }

        // Then add all rows again in the correct order
        foreach ($data as $name => $value) {
            $form->add($name, $this->type, array_replace([
                'property_path' => '['.$name.']',
                'data' => $value,
            ], $this->options));
        }
    }
}
