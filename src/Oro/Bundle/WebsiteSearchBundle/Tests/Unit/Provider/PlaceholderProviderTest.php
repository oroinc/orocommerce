<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Provider\PlaceholderProvider;

class PlaceholderProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PlaceholderProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $provider;

    /**
     * @var PlaceholderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $placeholder;

    /**
     * @var AbstractSearchMappingProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mappingProvider;

    protected function setUp()
    {
        $this->placeholder = $this->getMockBuilder(PlaceholderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mappingProvider = $this->getMockBuilder(AbstractSearchMappingProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new PlaceholderProvider($this->placeholder, $this->mappingProvider);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot find name field for Oro\Bundle\TestFrameworkBundle\Entity\TestProduct class
     */
    public function testGetPlaceholderFieldNameWhenFieldNotExists()
    {
        $this->mappingProvider
            ->expects($this->once())
            ->method('getEntityMapParameter')
            ->with(TestProduct::class, 'fields')
            ->willReturn([]);

        $this->placeholder
            ->expects($this->never())
            ->method('replace');

        $this->provider->getPlaceholderFieldName(TestProduct::class, 'name', ['LOCALIZATION_ID' => 1]);
    }

    public function testGetPlaceholderFieldNameWhenFieldExists()
    {
        $this->mappingProvider
            ->expects($this->once())
            ->method('getEntityMapParameter')
            ->with(TestProduct::class, 'fields')
            ->willReturn([
                'name' => [
                    'name' => 'name_LOCALIZATION_ID'
                ]
            ]);

        $placeholders = ['LOCALIZATION_ID' => 1];

        $this->placeholder
            ->expects($this->once())
            ->method('replace')
            ->with('name_LOCALIZATION_ID', $placeholders);

        $this->provider->getPlaceholderFieldName(TestProduct::class, 'name', $placeholders);
    }

    public function testGetPlaceholderEntityAlias()
    {
        $this->mappingProvider
            ->expects($this->once())
            ->method('getEntityAlias')
            ->with(TestProduct::class)
            ->willReturn('alias_WEBSITE_ID');

        $placeholders = ['WEBSITE_ID' => 1];

        $this->placeholder
            ->expects($this->once())
            ->method('replace')
            ->with('alias_WEBSITE_ID', $placeholders);

        $this->provider->getPlaceholderEntityAlias(TestProduct::class, $placeholders);
    }
}
