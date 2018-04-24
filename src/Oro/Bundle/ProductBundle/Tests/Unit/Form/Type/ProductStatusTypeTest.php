<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductStatusType;
use Oro\Bundle\ProductBundle\Provider\ProductStatusProvider;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ProductStatusTypeTest extends FormIntegrationTestCase
{
    /** @var  ProductStatusType $productStatusType */
    protected $productStatusType;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProductStatusProvider $productStatusProvider */
    protected $productStatusProvider;

    public function setup()
    {
        $this->productStatusProvider =
            $this->getMockBuilder('Oro\Bundle\ProductBundle\Provider\ProductStatusProvider')
                ->disableOriginalConstructor()
                ->getMock();

        $this->productStatusProvider
            ->method('getAvailableProductStatuses')
            ->willReturn([
                Product::STATUS_DISABLED => 'Disabled',
                Product::STATUS_ENABLED => 'Enabled'
            ]);

        $this->productStatusType = new ProductStatusType($this->productStatusProvider);
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension([$this->productStatusType], [])
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->productStatusType->getParent());
    }

    public function testChoices()
    {
        $form = $this->factory->create(ProductStatusType::class);
        $availableProductStatuses = $this->productStatusProvider->getAvailableProductStatuses();
        $choices = [];

        foreach ($availableProductStatuses as $key => $value) {
            $choices[] = new ChoiceView($key, $key, $value);
        }

        $this->assertEquals(
            $choices,
            $form->createView()->vars['choices']
        );

        $this->assertEquals(
            Product::STATUS_DISABLED,
            $form->getConfig()->getOptions()['preferred_choices']
        );
    }
}
