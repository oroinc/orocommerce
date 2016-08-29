<?php

namespace Oro\Bundle\ProductBundle\Model\Builder;

use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Common\Type;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Reader\ReaderInterface;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;

class QuickAddRowCollectionBuilder
{
    /**
     * @var EntityRepository|ProductRepository
     */
    protected $productRepository;

    /**
     * @param EntityRepository $productRepository
     */
    public function __construct(EntityRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @param Request $request
     * @return QuickAddRowCollection
     */
    public function buildFromRequest(Request $request)
    {
        $collection = new QuickAddRowCollection();
        $products = $request->request->get(
            QuickAddType::NAME . '[' . QuickAddType::PRODUCTS_FIELD_NAME . ']',
            [],
            true
        );

        if (!is_array($products) || empty($products)) {
            return $collection;
        }

        foreach ($products as $index => $product) {
            if (!array_key_exists(ProductDataStorage::PRODUCT_SKU_KEY, $product) ||
                !array_key_exists(ProductDataStorage::PRODUCT_QUANTITY_KEY, $product)
            ) {
                continue;
            }

            $sku = $product[ProductDataStorage::PRODUCT_SKU_KEY];
            $quantity = $product[ProductDataStorage::PRODUCT_QUANTITY_KEY];

            $collection->add(new QuickAddRow($index, $sku, $quantity));
        }

        $this->mapProductsAndValidate($collection);

        return $collection;
    }

    /**
     * @param UploadedFile $file
     * @return QuickAddRowCollection
     * @throws UnsupportedTypeException
     */
    public function buildFromFile(UploadedFile $file)
    {
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

        $this->mapProductsAndValidate($collection);

        return $collection;
    }

    /**
     * @param string $text
     * @return QuickAddRowCollection
     */
    public function buildFromCopyPasteText($text)
    {
        $collection = new QuickAddRowCollection();
        $lineNumber = 1;

        $text = trim($text);
        if ($text) {
            foreach (explode(PHP_EOL, $text) as $line) {
                $data = preg_split('/(\t|\,|\ )+/', $line);
                $collection->add(
                    new QuickAddRow(
                        $lineNumber++,
                        trim($data[0]),
                        isset($data[1]) ? (float) trim($data[1]) : null
                    )
                );
            }
        }

        $this->mapProductsAndValidate($collection);

        return $collection;
    }


    /**
     * @param string[] $skus
     * @return Product[]
     */
    private function getProductsBySkus(array $skus)
    {
        $products = $this->productRepository->getProductWithNamesBySku($skus);
        $productsBySku = [];
        foreach ($products as $product) {
            $productsBySku[strtoupper($product->getSku())] = $product;
        }

        return $productsBySku;
    }

    /**
     * @param UploadedFile $file
     * @return ReaderInterface
     * @throws UnsupportedTypeException
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

        throw new UnsupportedTypeException();
    }

    /**
     * @param QuickAddRowCollection $collection
     */
    private function mapProductsAndValidate(QuickAddRowCollection $collection)
    {
        $products = $this->getProductsBySkus($collection->getSkus());
        $collection->mapProducts($products)->validate();
    }
}
