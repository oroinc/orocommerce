<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Provider\PlaceholderProvider;

class PlaceholderProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PlaceholderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $placeholder;

    /** @var AbstractSearchMappingProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $mappingProvider;

    /** @var PlaceholderProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->placeholder = $this->createMock(PlaceholderInterface::class);
        $this->mappingProvider = $this->createMock(AbstractSearchMappingProvider::class);

        $this->provider = new PlaceholderProvider($this->placeholder, $this->mappingProvider);
    }

    public function testGetPlaceholderFieldNameWhenFieldNotExists()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot find name field for Oro\Bundle\TestFrameworkBundle\Entity\TestProduct class'
        );

        $this->mappingProvider->expects($this->once())
            ->method('getEntityMapParameter')
            ->with(TestProduct::class, 'fields')
            ->willReturn([]);

        $this->placeholder->expects($this->never())
            ->method('replace');

        $this->provider->getPlaceholderFieldName(TestProduct::class, 'name', ['LOCALIZATION_ID' => 1]);
    }

    public function testGetPlaceholderFieldNameWhenFieldExists()
    {
        $name = 'names_LOCALIZATION_ID';
        $this->mappingProvider->expects($this->once())
            ->method('getEntityMapParameter')
            ->with(TestProduct::class, 'fields')
            ->willReturn([
                [
                    'name' => $name
                ]
            ]);

        $placeholders = ['LOCALIZATION_ID' => 1];

        $this->placeholder->expects($this->once())
            ->method('replace')
            ->with($name, $placeholders);

        $this->provider->getPlaceholderFieldName(TestProduct::class, $name, $placeholders);
    }

    public function testGetPlaceholderEntityAlias()
    {
        $this->mappingProvider->expects($this->once())
            ->method('getEntityAlias')
            ->with(TestProduct::class)
            ->willReturn('alias_WEBSITE_ID');

        $placeholders = ['WEBSITE_ID' => 1];

        $this->placeholder->expects($this->once())
            ->method('replace')
            ->with('alias_WEBSITE_ID', $placeholders);

        $this->provider->getPlaceholderEntityAlias(TestProduct::class, $placeholders);
    }
}
