<?php

namespace OroB2B\Bundle\ProductBundle\Storage;

interface DataStorageInterface
{
    const STORAGE_KEY = 'storage';

    /**
     * @param array $data
     */
    public function set(array $data);

    /**
     * @return array
     */
    public function get();

    public function remove();
}
