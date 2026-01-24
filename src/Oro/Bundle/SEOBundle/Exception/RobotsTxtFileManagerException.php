<?php

namespace Oro\Bundle\SEOBundle\Exception;

/**
 * Thrown when an error occurs during `robots.txt` file management operations.
 *
 * This exception is raised when the `robots.txt` file manager encounters issues such as file I/O errors,
 * permission problems, or other failures during file creation, modification, or deletion operations.
 */
class RobotsTxtFileManagerException extends \Exception
{
}
