<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\DataTransformer;

use Symfony\Component\HttpFoundation\File\File;

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
        $this->transformer->transform(null);
    }

    /**
     * @dataProvider reverseTransformDataProvider
     *
     * @param File $file
     */
    public function testTransformsFile(File $file)
    {
        $this->assertValidCollection($this->transformer->reverseTransform($file));
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
    {
        return [
            'csv' => [new File(__DIR__ . '/files/quick-order.csv')],
            'ods' => [new File(__DIR__ . '/files/quick-order.ods')],
            'xlsx' => [new File(__DIR__ . '/files/quick-order.xlsx')]
        ];
    }
}
