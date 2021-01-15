<?php
declare(strict_types = 1);

namespace Oro\Bundle\ProductBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event used to add additional processing to autocomplete data
 */
class ProcessAutocompleteDataEvent extends Event
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
