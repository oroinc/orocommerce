<?php
declare(strict_types = 1);

namespace Oro\Bundle\ProductBundle\Event;

use Oro\Bundle\SearchBundle\Query\Result;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event used to add additional processing to autocomplete data
 */
class ProcessAutocompleteDataEvent extends Event
{
    public function __construct(protected array $data, protected string $queryString, protected Result $result)
    {
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getQueryString(): string
    {
        return $this->queryString;
    }

    /**
     * @return Result
     */
    public function getResult(): Result
    {
        return $this->result;
    }

    /**
     * @param Result $result
     */
    public function setResult(Result $result): void
    {
        $this->result = $result;
    }
}
