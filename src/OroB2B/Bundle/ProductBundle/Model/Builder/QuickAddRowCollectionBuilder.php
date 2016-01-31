<?php

namespace OroB2B\Bundle\ProductBundle\Model\Builder;

use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Common\Type;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Reader\ReaderInterface;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\Model\QuickAddRow;
use OroB2B\Bundle\ProductBundle\Model\QuickAddRowCollection;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

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

        $data = $request->request->get(QuickAddType::NAME);
        if (!isset($data[QuickAddType::PRODUCTS_FIELD_NAME])) {
            return $collection;
        }

        for ($i = 0; $i < count($data[QuickAddType::PRODUCTS_FIELD_NAME]); $i++) {
            if (
                !array_key_exists($i, $data[QuickAddType::PRODUCTS_FIELD_NAME]) ||
                !array_key_exists(ProductDataStorage::PRODUCT_SKU_KEY, $data[QuickAddType::PRODUCTS_FIELD_NAME][$i]) ||
                !array_key_exists(ProductDataStorage::PRODUCT_QUANTITY_KEY, $data[QuickAddType::PRODUCTS_FIELD_NAME][$i])
            ) {
                continue;
            }

            $sku = $data[QuickAddType::PRODUCTS_FIELD_NAME][$i][ProductDataStorage::PRODUCT_SKU_KEY];
            $quantity = $data[QuickAddType::PRODUCTS_FIELD_NAME][$i][ProductDataStorage::PRODUCT_QUANTITY_KEY];

            $collection->add(new QuickAddRow($i, $sku, $quantity));
        }

        $this->setProductsAndValidate($collection);

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

        $this->setProductsAndValidate($collection);

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

        if ($text) {
            foreach (explode(PHP_EOL, $text) as $line) {
                $data = preg_split('/\t|\,/', $line);
                $collection->add(
                    new QuickAddRow(
                        $lineNumber++,
                        trim($data[0]),
                        array_key_exists(1, $data) ? (float)trim($data[1]) : null
                    )
                );
            }
        }

        $this->setProductsAndValidate($collection);

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
    private function setProductsAndValidate(QuickAddRowCollection $collection)
    {
        $collection
            ->setProductsBySku($this->getProductsBySkus($collection->getSkus()))
            ->validate();
    }
}
