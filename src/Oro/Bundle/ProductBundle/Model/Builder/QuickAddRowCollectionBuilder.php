<?php

namespace Oro\Bundle\ProductBundle\Model\Builder;

use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Common\Type;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Reader\ReaderInterface;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
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
     * @var ProductManager
     */
    protected $productManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param EntityRepository $productRepository
     * @param ProductManager $productManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        EntityRepository $productRepository,
        ProductManager $productManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->productRepository = $productRepository;
        $this->productManager = $productManager;
        $this->eventDispatcher = $eventDispatcher;
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
        $collection = new QuickAddRowCollection();
        $collection->setEventDispatcher($this->eventDispatcher);

        $reader = $this->createReaderForFile($file);
        $reader->open($file->getRealPath());
        $collectionBySkus = $this->buildCollectionBySkuFromFile($reader);

        foreach ($collectionBySkus as $sku => $row) {
            $collection->add(new QuickAddRow($row['lineNumber'], $sku, $row['quantity']));
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
        $collection->setEventDispatcher($this->eventDispatcher);
        $lineNumber = 1;
        $collectionBySkus = [];

        $text = trim($text);
        if ($text) {
            foreach (explode(PHP_EOL, $text) as $line) {
                $data = preg_split('/(\t|\,|\ )+/', $line);
                $sku = trim($data[0]);
                $quantity = isset($data[1]) ? (float)trim($data[1]) : null;
                if (isset($collectionBySkus[$sku])) {
                    $collectionBySkus[$sku]['quantity'] += $quantity;
                } else {
                    $collectionBySkus[$sku] = [
                        'quantity' => $quantity,
                        'lineNumber' => $lineNumber++,
                    ];
                }
            }
        }
        foreach ($collectionBySkus as $sku => $row) {
            $collection->add(new QuickAddRow($row['lineNumber'], $sku, $row['quantity']));
        }

        $this->mapProductsAndValidate($collection);

        return $collection;
    }

    /**
     * @param ReaderInterface $reader
     * @return array
     */
    public function buildCollectionBySkuFromFile($reader)
    {
        $collectionBySkus = [];
        $lineNumber = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                if (0 === $lineNumber || empty($row[0])) {
                    $lineNumber++;
                    continue;
                }
                $lineNumber++;
                $sku = isset($row[0]) ? trim($row[0]) : null;
                $quantity = isset($row[1]) ? (float)trim($row[1]) : null;
                if (isset($collectionBySkus[$sku])) {
                    $collectionBySkus[$sku]['quantity'] += $quantity;
                } else {
                    $collectionBySkus[$sku] = [
                        'quantity' => $quantity,
                        'lineNumber' => $lineNumber,
                    ];
                }
            }
        }

        return $collectionBySkus;
    }

    /**
     * @param string[] $skus
     * @return Product[]
     */
    private function getRestrictedProductsBySkus(array $skus)
    {
        $qb = $this->productRepository->getProductWithNamesBySkuQueryBuilder($skus);
        $restricted = $this->productManager->restrictQueryBuilder($qb, []);

        // Configurable products require additional option selection is not implemented yet
        // Thus we need to hide configurable products from the product drop-downs
        // @TODO remove after configurable products require additional option selection implementation
        $restricted->andWhere($restricted->expr()->neq('product.type', ':configurable_type'))
            ->setParameter('configurable_type', Product::TYPE_CONFIGURABLE);

        $query = $restricted->getQuery();

        /** @var Product[] $products */
        $products = $query->execute();
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
        $products = $this->getRestrictedProductsBySkus($collection->getSkus());
        $collection->mapProducts($products)->validate();
    }
}
