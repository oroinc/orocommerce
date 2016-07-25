<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;

use Prophecy\Argument;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;

use OroB2B\Bundle\ProductBundle\Entity\ProductImage;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductImageCollection;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductImageCollectionValidator;

class ProductImageCollectionValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductImageCollectionValidator
     */
    protected $validator;

    /**
     * @var ImageTypeProvider
     */
    protected $imageTypeProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ExecutionContextInterface
     */
    protected $context;

    /**
     * @var ProductImageCollection
     */
    protected $constraint;

    public function setUp()
    {
        $this->translator = $this->prophesize('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->trans('Main')->willReturn('Main');

        $this->imageTypeProvider = $this->prophesize('Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider');
        $this->imageTypeProvider->getImageTypes()->willReturn(new ArrayCollection([
            new ThemeImageType('main', 'Main', [], 1),
            new ThemeImageType('listing', 'Listing', [], 2)
        ]));

        $this->constraint = new ProductImageCollection();

        $this->context = $this->prophesize('Symfony\Component\Validator\Context\ExecutionContextInterface');

        $this->validator = new ProductImageCollectionValidator(
            $this->imageTypeProvider->reveal(),
            $this->translator->reveal()
        );
        $this->validator->initialize($this->context->reveal());
    }

    public function testValidateValidCollection()
    {
        $collection = new ArrayCollection([
            $this->prepareProductImage(['main']),
        ]);

        $this->context->buildViolation(Argument::cetera())->shouldNotBeCalled();

        $this->validator->validate($collection, $this->constraint);
    }

    public function testValidateInvalidCollection()
    {
        $collection = new ArrayCollection([
            $this->prepareProductImage(['main']),
            $this->prepareProductImage(['main'])
        ]);

        $builder = $this->prophesize('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');

        $this->context->buildViolation(
            $this->constraint->message,
            [
                '%type%' => 'Main',
                '%maxNumber%' => 1
            ]
        )->willReturn($builder->reveal());

        $this->validator->validate($collection, $this->constraint);
    }

    /**
     * @param array $types
     * @return ProductImage
     */
    private function prepareProductImage($types)
    {
        $productImage = new ProductImage();
        foreach ($types as $type) {
            $productImage->addType($type);
        }

        return $productImage;
    }
}
