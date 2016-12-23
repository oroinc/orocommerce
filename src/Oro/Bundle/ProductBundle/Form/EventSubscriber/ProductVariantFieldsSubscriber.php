<?php

namespace Oro\Bundle\ProductBundle\Form\EventSubscriber;

use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ProductVariantFieldsSubscriber implements EventSubscriberInterface
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var CustomFieldProvider
     */
    private $customFieldProvider;

    /**
     * @var string
     */
    private $productClass;

    /**
     * @param null|string $type
     * @param CustomFieldProvider $customFieldProvider
     * @param string $productClass
     * @param array $options
     */
    public function __construct($type, CustomFieldProvider $customFieldProvider, $productClass, array $options = [])
    {
        $this->type = $type;
        $this->options = $options;
        $this->customFieldProvider = $customFieldProvider;
        $this->productClass = $productClass;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();

        $eventData = $event->getData();
        if (null === $eventData) {
            $eventData = array();
        }
        if (!is_array($eventData) && !($eventData instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($eventData, 'array or \ArrayAccess');
        }

        foreach ($form as $name => $child) {
            $form->remove($name);
        }

        $allFields = $this->getCustomVariantFields();

        $data = [];
        $fieldsToAdd = [];
        foreach ($allFields as $field) {
            $priority = array_search($field['name'], $eventData);
            $selected = $priority !== false;

            $data[] = [
                'id' => $field['name'],
                'priority' => $selected ? $priority : 9999,
                'is_default' => $selected,
            ];

            end($data);
            $fieldsToAdd[] = [
                'priority' => $selected ? $priority : 9999,
                'label' => $field['label'],
            ];
        }

        uasort($fieldsToAdd, function ($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        foreach ($fieldsToAdd as $key => $field) {
            $form->add($key, $this->type, array_replace(array(
                'property_path' => '['.$key.']',
                'label' => $field['label'],
            ), $this->options));
        }

        $event->setData($data);
    }

    /**
     * @return array
     */
    protected function getCustomVariantFields()
    {
        $customFields = $this->customFieldProvider->getEntityCustomFields($this->productClass);

        // Show only boolean and enum as allowed
        $customVariantFields = array_filter($customFields, function ($field) {
            return in_array($field['type'], ['boolean', 'enum'], true);
        });

        // Skip serialized fields. Should be improved in BB-6526
        $customVariantFields = array_filter($customVariantFields, function ($field) {
            return !$field['is_serialized'];
        });

        return $customVariantFields;
    }
}
