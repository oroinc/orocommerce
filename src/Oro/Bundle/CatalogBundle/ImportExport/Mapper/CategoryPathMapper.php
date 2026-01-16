<?php

declare(strict_types=1);

namespace Oro\Bundle\CatalogBundle\ImportExport\Mapper;

/**
 * Convert between category title arrays and category path strings.
 *
 * Unlike {@see \Oro\Bundle\CatalogBundle\ImportExport\Helper\CategoryImportExportHelper}, the methods
 * in this class escape (or expect escaping of) only `" / "` (space, slash, space) delimiters when used inside titles.
 * Slashes that are not surrounded by spaces are not considered delimiters and do not need to be escaped.
 *
 * This class is final, as the built-in escaping/unescaping rules work well for the chosen delimiter
 * but may not work for other delimiters. For example, if the delimiter were changed to `":"` (colon), escaping it as
 * `"::"` (double colon) would not work, as it would also match the delimiter itself and break path parsing.
 */
final class CategoryPathMapper implements CategoryPathMapperInterface
{
    private const DELIMITER = ' / ';
    private const ESCAPED_DELIMITER = ' // ';

    /**
     * Splits a path string into individual titles, unescaping escaped delimiters inside titles.
     *
     * A path string is composed of default category titles, starting from the root of the master catalog
     * (e.g. `"All Products / Medical / Medical Apparel / Footwear"`).
     *
     * The path string uses `" / "` (space, slash, space) as a delimiter; if the delimiter itself is used inside titles,
     * it must be escaped as `" // "` (space, slash, slash, space), for example, `"Clinical // Surgical"`.
     */
    #[\Override]
    public function pathStringToTitles(string $path): array
    {
        return \array_map(
            static fn (string $title) => \str_replace(self::ESCAPED_DELIMITER, self::DELIMITER, $title),
            \explode(self::DELIMITER, $path)
        );
    }

    /**
     * Joins titles into a path string, escaping delimiters inside titles.
     *
     * A path string is composed of default category titles, starting from the root of the master catalog
     * (e.g. `"All Products / Medical / Medical Apparel / Footwear"`).
     *
     * The path string uses `" / "` (space, slash, space) as a delimiter; if the delimiter itself is used inside titles,
     * it will be escaped as `" // "` (space, slash, slash, space), for example, `"Clinical // Surgical"`.
     */
    #[\Override]
    public function titlesToPathString(array $titles): string
    {
        return \implode(self::DELIMITER, \array_map(
            static fn (string $title) => \str_replace(self::DELIMITER, self::ESCAPED_DELIMITER, $title),
            $titles
        ));
    }
}
