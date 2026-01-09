<?php

namespace Oro\Bundle\SaleBundle\Form\EventListener;

use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\EventListener\ResizeFormListener;
use Symfony\Component\Form\FormEvent;

/**
 * Handles resizing of collection forms while preserving data passed to child form types.
 */
class QuoteToOrderResizeFormSubscriber extends ResizeFormListener
{
    private string $entryType;
    private array $entryOptions;

    public function __construct(
        string $type,
        array $options = [],
        bool $allowAdd = false,
        bool $allowDelete = false,
        bool|callable $deleteEmpty = false,
        ?array $prototypeOptions = null,
        bool $keepAsList = false,
    ) {
        parent::__construct($type, $options, $allowAdd, $allowDelete, $deleteEmpty, $prototypeOptions, $keepAsList);
        $this->entryType = $type;
        $this->entryOptions = $options;
    }

    /**
     * Copy-pasted from ResizeFormListener to provide ability to pass data to form
     */
    #[\Override]
    public function preSetData(FormEvent $event): void
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
            $form->add($name, $this->entryType, array_replace([
                'property_path' => '[' . $name . ']',
                'data' => $value,
            ], $this->entryOptions));
        }
    }
}
