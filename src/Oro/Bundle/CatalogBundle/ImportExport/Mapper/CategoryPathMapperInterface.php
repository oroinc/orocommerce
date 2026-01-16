<?php

declare(strict_types=1);

namespace Oro\Bundle\CatalogBundle\ImportExport\Mapper;

/**
 * Interface for converting between category title arrays and category path strings.
 *
 * @see CategoryPathMapper for the default implementation.
 */
interface CategoryPathMapperInterface
{
    /**
     * Splits a path into individual titles, unescaping escaped delimiters inside titles.
     *
     * @return string[]
     */
    public function pathStringToTitles(string $path): array;

    /**
     * Joins titles into a path, escaping delimiters inside titles.
     *
     * @param string[] $titles
     */
    public function titlesToPathString(array $titles): string;
}
