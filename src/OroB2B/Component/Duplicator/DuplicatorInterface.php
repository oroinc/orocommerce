<?php

namespace OroB2B\Component\Duplicator;

interface DuplicatorInterface
{
    /**
     * @param object $object
     * @param array $settings
     * @return mixed
     */
    public function duplicate($object, array $settings = []);
}
