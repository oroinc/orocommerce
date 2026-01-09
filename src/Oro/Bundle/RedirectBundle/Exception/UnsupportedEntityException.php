<?php

namespace Oro\Bundle\RedirectBundle\Exception;

/**
 * Thrown when an entity is not supported by a redirect or slug operation.
 *
 * This exception is raised when attempting to perform slug generation, redirect creation,
 * or other redirect-related operations on an entity that does not implement the required
 * interfaces or is otherwise not supported by the redirect system.
 */
class UnsupportedEntityException extends \Exception
{
}
