<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Exception;

/**
 * Thrown when an unsupported sitemap storage type is requested.
 *
 * This exception is raised when the sitemap storage factory is asked to create a storage instance
 * for a storage type that is not supported or recognized by the system.
 */
class UnsupportedStorageTypeException extends \Exception
{
    /**
     * @param string $type
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct($type, $code = 0, ?\Exception $previous = null)
    {
        parent::__construct(sprintf('Unsupported sitemap storage type %s', $type), $code, $previous);
    }
}
