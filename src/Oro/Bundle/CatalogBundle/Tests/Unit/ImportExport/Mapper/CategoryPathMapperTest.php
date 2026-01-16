<?php

declare(strict_types=1);

namespace Oro\Bundle\CatalogBundle\Tests\Unit\ImportExport\Mapper;

use Oro\Bundle\CatalogBundle\ImportExport\Mapper\CategoryPathMapper;

class CategoryPathMapperTest extends \PHPUnit\Framework\TestCase
{
    protected CategoryPathMapper $mapper;

    #[\Override]
    public function setUp(): void
    {
        $this->mapper = new CategoryPathMapper();
    }

    /**
     * @dataProvider conversionDataProvider
     */
    public function testPathToTitlesAndTitlesToPathAreInverse(array $titles, string $path): void
    {
        // Test path -> titles -> path

        $convertedTitles = $this->mapper->pathStringToTitles($path);
        $this->assertSame($convertedTitles, $titles);

        $inversePath = $this->mapper->titlesToPathString($convertedTitles);
        $this->assertSame($path, $inversePath);

        // Test titles -> path -> titles

        $convertedPath = $this->mapper->titlesToPathString($titles);
        $this->assertSame($convertedPath, $path);

        $inverseTitles = $this->mapper->pathStringToTitles($convertedPath);
        $this->assertSame($titles, $inverseTitles);
    }

    public function conversionDataProvider(): array
    {
        return [
            'single title' => [
                'titles' => ['All Products'],
                'path' => 'All Products',
            ],
            'multiple titles' => [
                'titles' => ['All Products', 'Medical', 'Medical Apparel', 'Footwear'],
                'path' => 'All Products / Medical / Medical Apparel / Footwear',
            ],
            'title with escaped delimiter' => [
                'titles' => ['All Products', 'Clinical / Surgical'],
                'path' => 'All Products / Clinical // Surgical',
            ],
            'multiple titles with escaped delimiters' => [
                'titles' => ['All Products', 'Clinical / Surgical', 'Medical / Dental'],
                'path' => 'All Products / Clinical // Surgical / Medical // Dental',
            ],
            'title with multiple escaped delimiters' => [
                'titles' => ['All Products', 'A / B / C'],
                'path' => 'All Products / A // B // C',
            ],
            'empty path' => [
                'titles' => [''],
                'path' => '',
            ],
            'title with slash without spaces' => [
                'titles' => ['All Products', 'Medical/Surgical'],
                'path' => 'All Products / Medical/Surgical',
            ],
        ];
    }
}
