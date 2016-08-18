<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Resolver;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteSearchPlaceholderRegistry;
use Oro\Bundle\WebsiteSearchBundle\Resolver\PlaceholderResolver;

class PlaceholderResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebsiteSearchPlaceholderRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var PlaceholderResolver
     */
    private $placeholderResolver;

    protected function setUp()
    {
        $this->registry = $this
            ->getMockBuilder('Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteSearchPlaceholderRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->placeholderResolver = new PlaceholderResolver($this->registry, []);
    }

    public function testReplaceInFrom()
    {
        $query = new Query();
        $query->from([
            'oro_first_TEST_ID',
            'oro_second',
            'oro_third_NAME_ID'
        ]);

        $this->registry->expects($this->once())
            ->method('getPlaceholders')
            ->willReturn([
                'TEST_ID' => '1',
                'NAME_ID' => '2'
            ]);

        $result = $this->placeholderResolver->replace($query, []);

        $this->assertInstanceOf('Oro\Bundle\SearchBundle\Query\Query', $result);
        $this->assertEquals(
            [
                'oro_first_1',
                'oro_second',
                'oro_third_2'
            ],
            $result->getFrom()
        );
    }

    public function testReplaceInFromForOnePlaceholder()
    {
        $query = new Query();
        $query->from([
            'oro_first_TEST_ID',
            'oro_second',
            'oro_third_NAME_ID'
        ]);

        $this->registry->expects($this->once())
            ->method('getPlaceholders')
            ->willReturn([
                'TEST_ID' => '1'
            ]);

        $result = $this->placeholderResolver->replace($query, []);

        $this->assertInstanceOf('Oro\Bundle\SearchBundle\Query\Query', $result);
        $this->assertEquals(
            [
                'oro_first_1',
                'oro_second',
                'oro_third_NAME_ID'
            ],
            $result->getFrom()
        );
    }
}
