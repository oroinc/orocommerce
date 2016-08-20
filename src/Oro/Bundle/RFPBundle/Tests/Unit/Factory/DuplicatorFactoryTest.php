<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Factory;

use DeepCopy\Filter\SetNullFilter;
use DeepCopy\Matcher\PropertyNameMatcher;

use Oro\Bundle\RFPBundle\Factory\DuplicatorFactory;
use Oro\Component\Duplicator\Filter\FilterFactory;
use Oro\Component\Duplicator\Matcher\MatcherFactory;

class DuplicatorFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $factory = new DuplicatorFactory();
        $filter = new SetNullFilter();
        $matcher = new PropertyNameMatcher('firstField');
        /** @var FilterFactory|\PHPUnit_Framework_MockObject_MockObject $filterFactory */
        $filterFactory = $this->getMock('Oro\Component\Duplicator\Filter\FilterFactory');
        $filterFactory->expects($this->once())->method('create')->with('setNull', [])->willReturn($filter);

        /** @var MatcherFactory|\PHPUnit_Framework_MockObject_MockObject $matcherFactory */
        $matcherFactory = $this->getMock('Oro\Component\Duplicator\Matcher\MatcherFactory');
        $matcherFactory->expects($this->once())
            ->method('create')
            ->with('propertyName', ['firstField'])
            ->willReturn($matcher);

        $factory->setFilterFactory($filterFactory);
        $factory->setMatcherFactory($matcherFactory);

        $duplicator = $factory->create();

        $firstField = new \stdClass();
        $firstField->title = 'test';

        $object = new \stdClass();
        $object->firstField = $firstField;
        $object->title = 'test title';

        $copyObject = $duplicator->duplicate($object, [
            [['setNull'], ['propertyName', ['firstField']]],
        ]);

        $this->assertNotEquals($copyObject, $object);
        $this->assertSame($copyObject->title, $object->title);
        $this->assertNull($copyObject->firstField);
    }
}
