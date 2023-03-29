<?php

namespace Oro\Bundle\ProductBundle\Model\Builder;

use Box\Spout\Common\Entity\Row;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Common\Type;
use Box\Spout\Reader\Common\Creator\ReaderFactory;
use Box\Spout\Reader\ReaderInterface;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Creates QuickAddRowCollection based on either request, file or text.
 */
class QuickAddRowCollectionBuilder
{
    private QuickAddRowInputParser $quickAddRowInputParser;
    private QuickAddRowProductMapperInterface $quickAddRowProductMapper;

    public function __construct(
        QuickAddRowInputParser $quickAddRowInputParser,
        QuickAddRowProductMapperInterface $quickAddRowProductMapper
    ) {
        $this->quickAddRowInputParser = $quickAddRowInputParser;
        $this->quickAddRowProductMapper = $quickAddRowProductMapper;
    }

    public function buildFromArray(array $products): QuickAddRowCollection
    {
        $collection = new QuickAddRowCollection();
        if ($products) {
            foreach ($products as $index => $product) {
                $collection->add(
                    $this->quickAddRowInputParser->createFromArray($product, $product[QuickAddRow::INDEX] ?? $index)
                );
            }

            $this->quickAddRowProductMapper->mapProducts($collection);
        }

        return $collection;
    }

    /**
     * @throws UnsupportedTypeException if the given file type is not supported
     */
    public function buildFromFile(UploadedFile $file): QuickAddRowCollection
    {
        $collection = new QuickAddRowCollection();

        $reader = $this->createReaderForFile($file);
        $reader->open($file->getRealPath());
        try {
            $lineNumber = 0;
            foreach ($reader->getSheetIterator() as $sheet) {
                /** @var Row $row */
                foreach ($sheet->getRowIterator() as $row) {
                    $row = $row->toArray();
                    if (0 === $lineNumber) {
                        $lineNumber++;
                        continue;
                    }
                    $collection->add($this->quickAddRowInputParser->createFromFileLine($row, $lineNumber++));
                }
            }
        } finally {
            $reader->close();
        }

        if (!$collection->isEmpty()) {
            $this->quickAddRowProductMapper->mapProducts($collection);
        }

        return $collection;
    }

    public function buildFromCopyPasteText(string $text): QuickAddRowCollection
    {
        $collection = new QuickAddRowCollection();

        $text = trim($text);
        if ($text) {
            $lineNumber = 1;
            $delimiter = null;
            foreach (explode(PHP_EOL, $text) as $line) {
                $line = trim($line);
                if (null === $delimiter) {
                    $delimiter = $this->detectDelimiter($line);
                }
                $data = preg_split($this->getSplitPattern($delimiter), $line);
                $collection->add($this->quickAddRowInputParser->createFromCopyPasteTextLine($data, $lineNumber++));
            }
        }

        if (!$collection->isEmpty()) {
            $this->quickAddRowProductMapper->mapProducts($collection);
        }

        return $collection;
    }

    private function detectDelimiter(string $line): string
    {
        foreach (["\t", ';', ' ', ','] as $delimiter) {
            $data = preg_split($this->getSplitPattern($delimiter), $line, 2);
            if ($data[0] !== $line) {
                break;
            }
        }

        return $delimiter;
    }

    private function getSplitPattern(string $delimiter): string
    {
        return '/' . preg_quote($delimiter, '/') . '(?=([^\"]*\"[^\"]*\")*[^\"]*$)/';
    }

    /**
     * @throws UnsupportedTypeException if the given file type is not supported
     */
    private function createReaderForFile(UploadedFile $file): ReaderInterface
    {
        switch ($file->getClientOriginalExtension()) {
            case 'csv':
                return ReaderFactory::createFromType(Type::CSV);
            case 'ods':
                return ReaderFactory::createFromType(Type::ODS);
            case 'xlsx':
                return ReaderFactory::createFromType(Type::XLSX);
        }

        throw new UnsupportedTypeException();
    }
}
