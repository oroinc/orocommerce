<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\VisitorReplacePlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Provider\PlaceholderFieldsProvider;

class PlaceholderFieldsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PlaceholderFieldsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $provider;

    /**
     * @var VisitorReplacePlaceholder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $visitorReplacePlaceholder;

    /**
     * @var AbstractSearchMappingProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mappingProvider;

    protected function setUp()
    {
        $this->visitorReplacePlaceholder = $this->getMockBuilder(VisitorReplacePlaceholder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mappingProvider = $this->getMockBuilder(AbstractSearchMappingProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new PlaceholderFieldsProvider($this->visitorReplacePlaceholder, $this->mappingProvider);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot find name field for Oro\Bundle\TestFrameworkBundle\Entity\TestProduct class
     */
    public function testGetPlaceholderFieldNameWhenNoFieldInConfigExists()
    {
        $this->mappingProvider
            ->expects($this->once())
            ->method('getEntityMapParameter')
            ->with(TestProduct::class, 'fields')
            ->willReturn([]);

        $this->visitorReplacePlaceholder
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

        $this->visitorReplacePlaceholder
            ->expects($this->once())
            ->method('replace')
            ->with('name_LOCALIZATION_ID', $placeholders);

        $this->provider->getPlaceholderFieldName(TestProduct::class, 'name', $placeholders);
    }
}
