<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Model;

use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class LineItemOptionModelTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['name', 'Name'],
            ['description', 'Description'],
            ['cost', 5.23],
            ['qty', 2]
        ];

        $this->assertPropertyAccessors(new LineItemOptionModel(), $properties);
    }

    public function testTruncate()
    {
        $optionModel = new LineItemOptionModel();

        $name = str_repeat('n', 37);
        $description = str_repeat('d', 36);

        $optionModel->setName($name);
        $optionModel->setDescription($description);

        $this->assertEquals(36, strlen($optionModel->getName()));
        $this->assertEquals(35, strlen($optionModel->getDescription()));
    }
}
