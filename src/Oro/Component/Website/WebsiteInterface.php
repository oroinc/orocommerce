<?php

namespace Oro\Component\Website;

/**
 * Defines the contract for website entities.
 *
 * A website represents a distinct web presence within the system, with its own identity,
 * name, and default status. Websites are used to organize and manage multi-site configurations.
 */
interface WebsiteInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return bool
     */
    public function isDefault();
}
