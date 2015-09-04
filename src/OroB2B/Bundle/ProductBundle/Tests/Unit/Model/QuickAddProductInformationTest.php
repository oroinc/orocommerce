<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Model;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Model\QuickAddProductInformation;

class QuickAddProductInformationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QuickAddProductInformation
     */
    protected $model;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->model = new QuickAddProductInformation();
    }

    public function testQuantity()
    {
        $this->assertEmpty($this->model->getQuantity());
        $this->model->setQuantity(10);
        $this->assertEquals(10, $this->model->getQuantity());
    }

    public function testProduct()
    {
        $product = new Product();
        $this->assertEmpty($this->model->getProduct());
        $this->model->setProduct($product);
        $this->assertEquals($product, $this->model->getProduct());
    }
}
