<?php

namespace Oro\Component\SEO\Tools\Exception;

/**
 * Thrown when an error occurs during sitemap file writing operations.
 *
 * This exception is raised when the sitemap dumper encounters issues while writing
 * sitemap files to the filesystem, such as permission errors or disk space issues.
 */
class SitemapFileWriterException extends \Exception
{
}
