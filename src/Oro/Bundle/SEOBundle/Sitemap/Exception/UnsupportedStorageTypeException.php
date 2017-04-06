<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Exception;

class UnsupportedStorageTypeException extends \Exception
{
    /**
     * @param string $type
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct($type, $code = 0, \Exception $previous = null)
    {
        parent::__construct(sprintf('Unsupported sitemap storage type %s', $type), $code, $previous);
    }
}
