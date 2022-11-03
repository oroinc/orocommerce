<?php

namespace Oro\Bundle\ProductBundle\Model\Builder;

use Box\Spout\Common\Entity\Row;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Common\Type;
use Box\Spout\Reader\Common\Creator\ReaderFactory;
use Box\Spout\Reader\ReaderInterface;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Creates QuickAddRowCollection based on either request, file or text.
 */
class QuickAddRowCollectionBuilder
{
    private EntityRepository $productRepository;

    private ProductManager $productManager;

    private QuickAddRowInputParser $quickAddRowInputParser;

    private AclHelper $aclHelper;

    public function __construct(
        EntityRepository $productRepository,
        ProductManager $productManager,
        QuickAddRowInputParser $quickAddRowInputParser,
        AclHelper $aclHelper
    ) {
        $this->productRepository = $productRepository;
        $this->productManager = $productManager;
        $this->quickAddRowInputParser = $quickAddRowInputParser;
        $this->aclHelper = $aclHelper;
    }

    public function buildFromArray(array $products): QuickAddRowCollection
    {
        $collection = new QuickAddRowCollection();
        if ($products) {
            foreach ($products as $index => $product) {
                $collection->add($this->quickAddRowInputParser->createFromArray($product, $index));
            }

            $this->mapProducts($collection);
        }

        return $collection;
    }

    /**
     * @throws UnsupportedTypeException
     */
    public function buildFromFile(UploadedFile $file): QuickAddRowCollection
    {
        $lineNumber = 0;
        $collection = new QuickAddRowCollection();

        $reader = $this->createReaderForFile($file);
        $reader->open($file->getRealPath());

        foreach ($reader->getSheetIterator() as $sheet) {
            /** @var Row $row */
            foreach ($sheet->getRowIterator() as $row) {
                $row = $row->toArray();
                if (0 === $lineNumber) {
                    $lineNumber++;
                    continue;
                }

                $collection->add(
                    $this->quickAddRowInputParser->createFromFileLine($row, $lineNumber++)
                );
            }
        }

        $this->mapProducts($collection);

        return $collection;
    }

    public function buildFromCopyPasteText(string $text): QuickAddRowCollection
    {
        $collection = new QuickAddRowCollection();
        $lineNumber = 1;

        $text = trim($text);
        if ($text) {
            $delimiter = null;
            foreach (explode(PHP_EOL, $text) as $line) {
                $line = trim($line);
                if ($delimiter === null) {
                    $delimiter = $this->detectDelimiter($line);
                }
                $data = preg_split('/' . preg_quote($delimiter, '/') . '+/', $line);
                $collection->add(
                    $this->quickAddRowInputParser->createFromCopyPasteTextLine($data, $lineNumber++)
                );
            }
        }

        $this->mapProducts($collection);

        return $collection;
    }

    private function detectDelimiter(string $line): string
    {
        foreach (["\t", ';', ' ',  ','] as $delimiter) {
            $data = preg_split('/' . preg_quote($delimiter, '/') . '+/', $line, 2);
            if ($data[0] !== $line) {
                break;
            }
        }

        return $delimiter;
    }

    /**
     * @param string[] $skus
     * @return Product[]
     */
    private function getRestrictedProductsBySkus(array $skus): array
    {
        $qb = $this->productRepository->getProductWithNamesBySkuQueryBuilder($skus);
        $restricted = $this->productManager->restrictQueryBuilder($qb, []);

        // Configurable products require additional option selection is not implemented yet
        // Thus we need to hide configurable products from the product drop-downs
        $restricted->andWhere($restricted->expr()->neq('product.type', ':configurable_type'))
            ->setParameter('configurable_type', Product::TYPE_CONFIGURABLE);

        $query = $this->aclHelper->apply($restricted);

        /** @var Product[] $products */
        $products = $query->execute();
        $productsBySku = [];

        foreach ($products as $product) {
            $productsBySku[mb_strtoupper($product->getSku())] = $product;
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
                return ReaderFactory::createFromType(Type::CSV);
            case 'ods':
                return ReaderFactory::createFromType(Type::ODS);
            case 'xlsx':
                return ReaderFactory::createFromType(Type::XLSX);
        }

        throw new UnsupportedTypeException();
    }

    private function mapProducts(QuickAddRowCollection $collection): void
    {
        $skus = $collection->getSkus();
        if ($skus) {
            $products = $this->getRestrictedProductsBySkus($skus);
            $collection->mapProducts($products);
        }
    }
}
