<?php

namespace OroB2B\Bundle\ProductBundle\Form\DataTransformer;

use Box\Spout\Common\Type;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Reader\ReaderInterface;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
     * @param UploadedFile|null $file
     * @return QuickAddRowCollection|null
     */
    public function reverseTransform($file)
    {
        if (null === $file) {
            return null;
        }

        $lineNumber = 0;
        $collection = new QuickAddRowCollection();

        $reader = $this->createReaderForFile($file);
        $reader->open($file->getRealPath());

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
     * @param UploadedFile $file
     * @return ReaderInterface
     */
    private function createReaderForFile(UploadedFile $file)
    {
        switch ($file->getClientOriginalExtension()) {
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
