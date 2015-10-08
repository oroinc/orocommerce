<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitRemovedSelectionType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductUnitRemovedSelectionType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductUnitSelectionType;

class ProductUnitRemovedSelectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductUnitRemovedSelectionType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->translator
            ->expects(static::any())
            ->method('trans')
            ->willReturnCallback(function ($id, array $params) {
                return isset($params['{title}']) ? $id . ':' . $params['{title}'] : $id;
            })
        ;
        $productUnitLabelFormatter = new ProductUnitLabelFormatter($this->translator);
        $this->formType = new ProductUnitRemovedSelectionType($productUnitLabelFormatter, $this->translator);
        $this->formType->setEntityClass('OroB2B\Bundle\ProductBundle\Entity\ProductUnit');

        parent::setUp();
    }

    public function testGetName()
    {
        static::assertEquals(ProductUnitRemovedSelectionType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        static::assertEquals(ProductUnitSelectionType::NAME, $this->formType->getParent());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $productUnitSelectionType = new StubProductUnitSelectionType([1], ProductUnitSelectionType::NAME);
        $productUnitRemovedSelectionType = new StubProductUnitRemovedSelectionType();
        $entityType = new EntityType(['1']);

        return [
            new PreloadedExtension(
                [
                    $productUnitSelectionType->getName() => $productUnitSelectionType,
                    $productUnitRemovedSelectionType->getName() => $productUnitRemovedSelectionType,
                    $entityType->getName() => $entityType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
