<?php

namespace Oro\Bundle\ActionBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\ActionBundle\Model\ActionContext;

/**
 * This listener removes attributes from context if they are not present in form in PRE_SET_DATA event
 * and returns all values including submitted values back to original context in SUBMIT event.
 *
 * This logic is used to avoid validation of attributes that are not in current form.
 */
class RequiredAttributesListener implements EventSubscriberInterface
{
    /** @var ActionContext */
    protected $context;

    /** @var array */
    protected $attributeNames;

    /**
     * @param array $attributeNames
     */
    public function initialize(array $attributeNames)
    {
        $this->attributeNames = $attributeNames;
    }

    /**
     * Extract only required attributes for form and create new context based on them
     *
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        /** @var ActionContext $data */
        $data = $event->getData();
        if ($data instanceof ActionContext) {
            $this->context = $data;

            $event->setData(new ActionContext($data->getValues($this->attributeNames)));
        }
    }

    /**
     * Copy submitted data to existing context
     *
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event)
    {
        /** @var ActionContext $data */
        $data = $event->getData();
        if ($this->context && $data instanceof ActionContext) {
            foreach ($data->getValues() as $name => $value) {
                $this->context->$name = $value;
            }

            $event->setData($this->context);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'onPreSetData',
            FormEvents::SUBMIT => 'onSubmit'
        ];
    }
}
