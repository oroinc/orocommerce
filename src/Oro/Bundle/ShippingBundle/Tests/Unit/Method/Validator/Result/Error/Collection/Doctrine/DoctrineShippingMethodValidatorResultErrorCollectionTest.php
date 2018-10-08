<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Validator\Result\Error\Collection\Doctrine;

use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Collection;

class DoctrineShippingMethodValidatorResultErrorCollectionTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateCommonBuilder()
    {
        $factory = new Collection\Doctrine\DoctrineShippingMethodValidatorResultErrorCollection();

        static::assertInstanceOf(
            Collection\Builder\Common\Doctrine\DoctrineCommonShippingMethodValidatorResultErrorCollectionBuilder::class,
            $factory->createCommonBuilder()
        );
    }
}
