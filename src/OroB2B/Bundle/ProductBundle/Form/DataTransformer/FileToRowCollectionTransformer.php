<?php

namespace OroB2B\Bundle\ProductBundle\Form\DataTransformer;

use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;
use Box\Spout\Reader\ReaderInterface;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\File;

use OroB2B\Bundle\ProductBundle\Model\QuickAddRow;
use OroB2B\Bundle\ProductBundle\Model\QuickAddRowCollection;

class FileToRowCollectionTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return $value;
    }

    /**
     * @param File $file
     * @return QuickAddRowCollection
     */
    public function reverseTransform($file)
    {
        $reader = $this->createReaderForFile($file);
        $reader->open($file->getRealPath());

        $lineNumber = 0;
        $collection = new QuickAddRowCollection();

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                if (0 === $lineNumber || empty($row[0])) {
                    $lineNumber++;
                    continue;
                }
                
                $collection->add(new QuickAddRow(
                    $lineNumber++,
                    isset($row[0]) ? trim($row[0]) : null,
                    isset($row[1]) ? (float) trim($row[1]) : null
                ));
            }
        }

        return $collection;
    }

    /**
     * @param File $file
     * @return ReaderInterface
     */
    private function createReaderForFile(File $file)
    {
        switch ($file->getExtension()) {
            case 'csv':
                return ReaderFactory::create(Type::CSV);
            case 'ods':
                return ReaderFactory::create(Type::ODS);
            case 'xlsx':
                return ReaderFactory::create(Type::XLSX);
        }

        throw new TransformationFailedException();
    }
}
