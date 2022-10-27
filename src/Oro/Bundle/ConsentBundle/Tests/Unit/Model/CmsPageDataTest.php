<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Model;

use Oro\Bundle\ConsentBundle\Model\CmsPageData;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CmsPageDataTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $cmsPageData = new CmsPageData();

        $properties = [
            ['id', 13],
            ['url', 'url.com'],
        ];
        $this->assertPropertyAccessors($cmsPageData, $properties);
    }

    public function testJsonSerialize()
    {
        $cmsPageData = new CmsPageData();
        $cmsPageData->setId(13);
        $cmsPageData->setUrl('url.com');

        $this->assertEquals(
            ['id' => 13, 'url' => 'url.com'],
            $cmsPageData->jsonSerialize()
        );
    }
}
