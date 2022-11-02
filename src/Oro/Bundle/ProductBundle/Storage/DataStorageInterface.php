<?php

namespace Oro\Bundle\ProductBundle\Storage;

/**
 * Interface for storages holding arbitrary array data.
 */
interface DataStorageInterface
{
    public const STORAGE_KEY = 'storage';

    public function set(array $data): void;

    public function get(): array;

    public function remove(): void;
}
