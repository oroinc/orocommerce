<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Model;

use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;

class LineItemOptionModelTest extends \PHPUnit_Framework_TestCase
{
    public function testProperties()
    {
        $optionModel = new LineItemOptionModel();
        $optionModel
            ->setName('Name')
            ->setDescription('Description')
            ->setCost(5.23)
            ->setQty(2);

        $this->assertEquals('Name', $optionModel->getName());
        $this->assertEquals('Description', $optionModel->getDescription());
        $this->assertEquals(5.23, $optionModel->getCost());
        $this->assertEquals(2, $optionModel->getQty());
    }

    public function testTruncate()
    {
        $optionModel = new LineItemOptionModel();

        $name = str_repeat('long_name', 10);
        $description = str_repeat('long_description', 10);

        $this->assertGreaterThan(36, strlen($name));
        $this->assertGreaterThan(35, strlen($description));

        $optionModel->setName($name);
        $optionModel->setDescription($description);

        $this->assertEquals(36, strlen($optionModel->getName()));
        $this->assertEquals(35, strlen($optionModel->getDescription()));
    }
}
