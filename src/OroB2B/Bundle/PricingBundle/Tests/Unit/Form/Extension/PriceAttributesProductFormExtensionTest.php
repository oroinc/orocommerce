<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\PricingBundle\Form\Extension\PriceAttributesProductFormExtension;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub\ProductTypeStub;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class PriceAttributesProductFormExtensionTest extends FormIntegrationTestCase
{
    /**
     * @var PriceAttributesProductFormExtension
     */
    protected $productAttributeFormExtension;

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->registry = $this->getMock(RegistryInterface::class);
        $this->productAttributeFormExtension = new PriceAttributesProductFormExtension($this->registry);

        parent::setUp();
    }

    public function testGetExtendedType()
    {
        $this->productAttributeFormExtension->getExtendedType();
        $this->assertSame(ProductType::NAME, $this->productAttributeFormExtension->getExtendedType());
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $extensions = [
            new PreloadedExtension(
                [
                    ProductType::NAME => new ProductTypeStub()
                ],
                [
                    ProductType::NAME => [
                        $this->productAttributeFormExtension
                    ]
                ]
            )
        ];

        return $extensions;
    }

    public function testSubmit()
    {
        $em = $this->getMock(ObjectManager::class);

        $repository = $this->getMock(ObjectRepository::class);
        $repository->expects($this->once())->method('findBy')->willReturn([]);
        $em->expects($this->once())->method('getRepository')->willReturn($repository);
        $this->registry->expects($this->once())->method('getManagerForClass')->willReturn($em);

        $form = $this->factory->create(ProductType::NAME, new Product(), []);

        $form->submit([]);
        $this->assertTrue($form->isValid());
    }
}
