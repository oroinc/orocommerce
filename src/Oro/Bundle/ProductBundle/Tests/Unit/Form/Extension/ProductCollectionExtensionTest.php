<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ProductBundle\Form\Extension\ProductCollectionExtension;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentVariantCollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductCollectionExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var ProductCollectionExtension */
    private $productCollectionExtension;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->productCollectionExtension = new ProductCollectionExtension($this->translator);
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$this->productCollectionExtension, 'onPostSubmit']);

        $this->productCollectionExtension->buildForm($builder, []);
    }

    /**
     * @dataProvider productCollectionFormsDataProvider
     */
    public function testOnPostSubmitNoValidationError(array $forms)
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('all')
            ->willReturn($forms);

        $this->translator->expects($this->never())
            ->method('trans');

        $event = new FormEvent($form, []);

        $this->productCollectionExtension->onPostSubmit($event);
    }

    public function productCollectionFormsDataProvider(): array
    {
        return [
            'no content variants' => [
                'forms' => []
            ],
            'no productCollectionSegment child form' => [
                'forms' => [
                    $this->createNoProductCollectionSegmentChildForm(),
                    $this->createNoProductCollectionSegmentChildForm()
                ]
            ],
            'no productCollectionSegmentName child form' => [
                'forms' => [
                    $this->createNoProductCollectionSegmentNameChildForm(),
                    $this->createNoProductCollectionSegmentNameChildForm()
                ]
            ],
            'empty segment name in form data' => [
                'forms' => [
                    $this->createProductCollectionForm(''),
                    $this->createProductCollectionForm('')
                ]
            ],
            'unique segment names' => [
                'forms' => [
                    $this->createProductCollectionForm('first unique name'),
                    $this->createProductCollectionForm('second unique name')
                ]
            ],
        ];
    }

    public function testOnPostSubmitValidationError()
    {
        $nameForm = $this->createMock(FormInterface::class);
        $firstProductCollectionForm = $this->createProductCollectionForm(
            'Not unique segment name',
            null,
            (new Segment())->setName('Not unique segment name')
        );

        $secondProductCollectionForm = $this->createProductCollectionForm(
            'Not unique segment name',
            $nameForm,
            (new Segment())->setName('not Unique segment naME')
        );

        $validationMessage = 'There is another segment with a similar name.';
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.product.product_collection.unique_segment_name.message', [], 'validators')
            ->willReturn($validationMessage);

        $expectedFormError = new FormError($validationMessage);

        $nameForm->expects($this->once())
            ->method('addError')
            ->with($expectedFormError);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('all')
            ->willReturn([$firstProductCollectionForm, $secondProductCollectionForm]);

        $this->productCollectionExtension->onPostSubmit(new FormEvent($form, []));
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([ContentVariantCollectionType::class], ProductCollectionExtension::getExtendedTypes());
    }

    private function createProductCollectionForm(
        string $segmentName,
        FormInterface|\PHPUnit\Framework\MockObject\MockObject|null $productCollectionSegmentNameForm = null,
        ?Segment $segment = null
    ): FormInterface {
        if (!$productCollectionSegmentNameForm) {
            $productCollectionSegmentNameForm = $this->createMock(FormInterface::class);
        }

        $productCollectionSegmentNameForm->expects($this->any())
            ->method('getData')
            ->willReturn($segmentName);

        $productCollectionSegmentForm = $this->createMock(FormInterface::class);
        $productCollectionSegmentForm->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['name', true]
            ]);

        $productCollectionSegmentForm->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['name', $productCollectionSegmentNameForm]
            ]);

        $productCollectionSegmentForm->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturn($segment ?? new Segment());

        $productCollectionForm = $this->createMock(FormInterface::class);
        $productCollectionForm->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['productCollectionSegment', true]
            ]);

        $productCollectionForm->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['productCollectionSegment', $productCollectionSegmentForm]
            ]);

        return $productCollectionForm;
    }

    private function createNoProductCollectionSegmentChildForm(): FormInterface
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['productCollectionSegment', false]
            ]);

        return $form;
    }

    private function createNoProductCollectionSegmentNameChildForm(): FormInterface
    {
        $productCollectionSegmentForm = $this->createMock(FormInterface::class);
        $productCollectionSegmentForm->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['name', false]
            ]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['productCollectionSegment', true]
            ]);

        $form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['productCollectionSegment', $productCollectionSegmentForm]
            ]);

        return $form;
    }
}
