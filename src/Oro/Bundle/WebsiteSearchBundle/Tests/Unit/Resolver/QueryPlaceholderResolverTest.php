<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Resolver;

use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteSearchPlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteSearchPlaceholderRegistry;
use Oro\Bundle\WebsiteSearchBundle\Resolver\QueryPlaceholderResolver;

class QueryPlaceholderResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebsiteSearchPlaceholderRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var QueryPlaceholderResolver
     */
    private $placeholderResolver;

    protected function setUp()
    {
        $this->registry = $this
            ->getMockBuilder(WebsiteSearchPlaceholderRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->placeholderResolver = new QueryPlaceholderResolver($this->registry, []);
    }

    public function testReplaceInFrom()
    {
        $query = new Query();
        $fromArray = [
            'oro_first_TEST_ID',
            'oro_second',
            'oro_third_NAME_ID'
        ];
        $query->from($fromArray);
        $placeholder1 = $this->getPlaceholder('TEST_ID', '1');
        $placeholder2 = $this->getPlaceholder('NAME_ID', '2');

        $this->registry->expects($this->once())
            ->method('getPlaceholders')
            ->willReturn([
                'TEST_ID' => $placeholder1,
                'NAME_ID' => $placeholder2
            ]);

        $placeholder1->expects($this->exactly(3))
            ->method('replace')
            ->withConsecutive(
                ['oro_first_TEST_ID', 1],
                ['oro_second', 1],
                ['oro_third_NAME_ID', 1]
            )
            ->willReturnOnConsecutiveCalls('oro_first_1', 'oro_second', 'oro_third_NAME_ID');

        $placeholder2->expects($this->exactly(3))
            ->method('replace')
            ->withConsecutive(
                ['oro_first_1', 2],
                ['oro_second', 2],
                ['oro_third_NAME_ID', 2]
            )
            ->willReturnOnConsecutiveCalls('oro_first_1', 'oro_second', 'oro_third_2');

        $this->placeholderResolver->replace($query);

        $this->assertEquals(
            [
                'oro_first_1',
                'oro_second',
                'oro_third_2'
            ],
            $query->getFrom()
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

        $placeholder1 = $this->getPlaceholder('TEST_ID', '1');

        $this->registry->expects($this->once())
            ->method('getPlaceholders')
            ->willReturn([
                'TEST_ID' => $placeholder1
            ]);

        $placeholder1->expects($this->exactly(3))
            ->method('replace')
            ->withConsecutive(
                ['oro_first_TEST_ID', 1],
                ['oro_second', 1],
                ['oro_third_NAME_ID', 1]
            )
            ->willReturnOnConsecutiveCalls('oro_first_1', 'oro_second', 'oro_third_NAME_ID');

        $this->placeholderResolver->replace($query);

        $this->assertEquals(
            [
                'oro_first_1',
                'oro_second',
                'oro_third_NAME_ID'
            ],
            $query->getFrom()
        );
    }

    public function testReplaceInCriteria()
    {
        $expr = new Comparison("field_name_NAME_ID", "=", "value");
        $criteria = new Criteria();
        $criteria->where($expr);

        $query = new Query();
        $query->setCriteria($criteria);

        $this->registry->expects($this->once())
            ->method('getPlaceholders')
            ->willReturn([
                'TEST_ID' => $this->getPlaceholder('TEST_ID', '1'),
                'NAME_ID' => $this->getPlaceholder('NAME_ID', '2')
            ]);

        $this->placeholderResolver->replace($query);

        $expectedExpr = new Comparison("field_name_2", "=", "value");
        $criteria->where($expectedExpr);

        $this->assertEquals($criteria, $query->getCriteria());
    }

    /**
     * @param string $placeholderName
     * @param string $value
     * @return WebsiteSearchPlaceholderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getPlaceholder($placeholderName, $value)
    {
        $placeholder = $this
            ->getMockBuilder(WebsiteSearchPlaceholderInterface::class)
            ->getMock();

        $placeholder->expects($this->any())
            ->method('getPlaceholder')
            ->willReturn($placeholderName);

        $placeholder->expects($this->any())
            ->method('getValue')
            ->willReturn($value);

        return $placeholder;
    }
}
