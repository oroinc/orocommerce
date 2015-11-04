<?php

namespace OroB2B\Bundle\ProductBundle\ComponentProcessor;

use Doctrine\ORM\Query;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ProductBundle\Entity\Manager\ProductManager;

class ComponentProcessorFilter
{
    /** @var  ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $dataClass;

    /** @var  ProductManager */
    protected $productManager;

    public function __construct(
        ProductManager $productManager,
        ManagerRegistry $registry
    ) {
        $this->productManager = $productManager;
        $this->registry = $registry;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    public function filterData(array $data, array $dataParameters)
    {
        if (empty($data['entity_items_data'])) {
            return [];
        }
        $skus = array_map(
            function ($product) {
                return $product['productSku'];
            },
            $data['entity_items_data']
        );

        $queryBuilder = $this->getRepository()->getFilterSkuQueryBuilder($skus);
        $queryBuilder = $this->productManager->restrictQueryBuilderByProductVisibility($queryBuilder, $dataParameters);

        $filteredSkues = $queryBuilder->getQuery()->getResult(Query::HYDRATE_ARRAY);
        var_dump($filteredSkues);
        exit();

        return $data;
    }

    /**
     * @return ProductRepository
     */
    protected function getRepository()
    {
        return $this->registry->getManagerForClass($this->dataClass)->getRepository($this->dataClass);
    }
}
