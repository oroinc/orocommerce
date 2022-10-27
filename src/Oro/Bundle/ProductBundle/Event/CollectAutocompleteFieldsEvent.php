<?php
declare(strict_types = 1);

namespace Oro\Bundle\ProductBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event used to collect list of fields requested from search index during the autocomplete request
 */
class CollectAutocompleteFieldsEvent extends Event
{
    protected array $fields;

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function addField(string $fieldName): void
    {
        $this->fields[] = $fieldName;
    }
}
