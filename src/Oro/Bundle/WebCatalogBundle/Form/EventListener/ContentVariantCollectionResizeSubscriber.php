<?php

namespace Oro\Bundle\WebCatalogBundle\Form\EventListener;

use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ContentVariantCollectionResizeSubscriber implements EventSubscriberInterface
{
    /**
     * @var ContentVariantTypeRegistry
     */
    private $variantTypeRegistry;

    /**
     * @var array
     */
    private $options = [];

    public function __construct(ContentVariantTypeRegistry $variantTypeRegistry, array $options)
    {
        $this->variantTypeRegistry = $variantTypeRegistry;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
            FormEvents::SUBMIT => ['onSubmit', 50],
        ];
    }

    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if (null === $data) {
            $data = [];
        }

        if (!$this->isTraversable($data)) {
            throw new UnexpectedTypeException($data, 'array or (\Traversable and \ArrayAccess)');
        }

        // First remove all rows
        foreach ($form as $name => $child) {
            $form->remove($name);
        }

        // Then add all rows again in the correct order
        foreach ($data as $name => $value) {
            if ($value instanceof ContentVariantInterface) {
                $form->add(
                    $name,
                    $this->variantTypeRegistry->getFormTypeByType($value->getType()),
                    array_replace(['property_path' => '[' . $name . ']'], $this->options)
                );
            }
        }
    }

    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if (!$this->isTraversable($data)) {
            $data = [];
        }

        // Remove all empty rows
        foreach ($form as $name => $child) {
            if (!isset($data[$name])) {
                $form->remove($name);
            }
        }

        // Add all additional rows
        foreach ($data as $name => $value) {
            if (array_key_exists('type', $value) && !$form->has($name)) {
                $form->add(
                    $name,
                    $this->variantTypeRegistry->getFormTypeByType($value['type']),
                    array_replace(['property_path' => '[' . $name . ']'], $this->options)
                );
            }
        }
    }

    public function onSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        // At this point, $data is an array or an array-like object that already contains the
        // new entries, which were added by the data mapper. The data mapper ignores existing
        // entries, so we need to manually unset removed entries in the collection.

        if (null === $data) {
            $data = [];
        }

        if (!$this->isTraversable($data)) {
            throw new UnexpectedTypeException($data, 'array or (\Traversable and \ArrayAccess)');
        }

        foreach ($form as $name => $child) {
            if ($child->isEmpty()) {
                unset($data[$name]);
                $form->remove($name);
            }
        }

        // The data mapper only adds, but does not remove items, so do this
        // here
        $toDelete = [];
        foreach ($data as $name => $child) {
            if (!$form->has($name)) {
                $toDelete[] = $name;
            }
        }

        foreach ($toDelete as $name) {
            unset($data[$name]);
        }

        $event->setData($data);
    }

    /**
     * @param mixed $data
     * @return bool
     */
    protected function isTraversable($data)
    {
        return is_array($data) || $data instanceof \Traversable || $data instanceof \ArrayAccess;
    }
}
