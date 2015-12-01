<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\DataTransformer;

use Symfony\Component\HttpFoundation\File\UploadedFile;

use OroB2B\Bundle\ProductBundle\Form\DataTransformer\FileToRowCollectionTransformer;

class FileToRowCollectionTransformerTest extends RowCollectionTransformerTest
{
    /**
     * @var FileToRowCollectionTransformer
     */
    protected $transformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->transformer = new FileToRowCollectionTransformer();
    }

    /**
     * @dataProvider reverseTransformDataProvider
     *
     * @param UploadedFile $file
     */
    public function testReverseTransformFile(UploadedFile $file)
    {
        $this->assertValidCollection($this->transformer->reverseTransform($file));
    }

    /**
     * @dataProvider reverseTransformDataProvider
     *
     * @param array|null $value
     */
    public function testTransformFile($value)
    {
        $this->assertEquals($value, $this->transformer->transform($value));
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
    {
        return [
            'csv' => [new UploadedFile(__DIR__ . '/files/quick-order.csv', 'quick-order.csv')],
            'ods' => [new UploadedFile(__DIR__ . '/files/quick-order.ods', 'quick-order.ods')],
            'xlsx' => [new UploadedFile(__DIR__ . '/files/quick-order.xlsx', 'quick-order.xlsx')]
        ];
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        return ['array' => [], 'null' => null];
    }
}
