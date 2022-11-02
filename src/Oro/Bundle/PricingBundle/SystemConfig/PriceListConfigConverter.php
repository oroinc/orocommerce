<?php

namespace Oro\Bundle\PricingBundle\SystemConfig;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\PriceList;

/**
 * Transforms array of {@see PriceListConfig} to a scalar array and back
 */
class PriceListConfigConverter
{
    const MERGE_KEY = 'mergeAllowed';
    const SORT_ORDER_KEY = 'sort_order';
    const PRICE_LIST_KEY = 'priceList';

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var string */
    protected $priceListClassName;

    /** @var string */
    protected $managerForPriceList;

    /**
     * @param ManagerRegistry $doctrine
     * @param string $priceListClassName
     */
    public function __construct(ManagerRegistry $doctrine, $priceListClassName)
    {
        $this->doctrine = $doctrine;
        $this->priceListClassName = $priceListClassName;
    }

    /**
     * @param array $configs
     * @return array
     */
    public function convertBeforeSave(array $configs)
    {
        $result = array_map(
            function ($config) {
                /** @var PriceListConfig $config */
                return [
                    self::PRICE_LIST_KEY => $config->getPriceList()->getId(),
                    self::SORT_ORDER_KEY => $config->getSortOrder(),
                    self::MERGE_KEY => $config->isMergeAllowed(),
                ];
            },
            $configs
        );

        return $result;
    }

    /**
     * @param array $configs
     * @return PriceListConfig[]
     */
    public function convertFromSaved(array $configs): array
    {
        $ids = array_map(
            function ($config) {
                return $config[self::PRICE_LIST_KEY];
            },
            $configs
        );
        $result = [];

        if (0 !== count($ids)) {
            $priceLists = $this->getManagerForPriceList()
                ->getRepository('OroPricingBundle:PriceList')
                ->findBy(['id' => $ids]) ?: [];
            foreach ($configs as $config) {
                $result[] = $this->createPriceListConfig($config, $priceLists);
            }

            usort(
                $result,
                static fn (PriceListConfig $a, PriceListConfig $b) => $a->getSortOrder() <=> $b->getSortOrder()
            );
        }

        return $result;
    }

    /**
     * @param array $config
     * @param PriceList[] $priceLists
     * @return PriceListConfig
     */
    protected function createPriceListConfig($config, $priceLists)
    {
        $configModel = new PriceListConfig();

        foreach ($priceLists as $priceList) {
            if ($config[self::PRICE_LIST_KEY] === $priceList->getId()) {
                $configModel->setPriceList($priceList)
                    ->setSortOrder($config[self::SORT_ORDER_KEY])
                    ->setMergeAllowed($config[self::MERGE_KEY]);

                return $configModel;
            }
        }

        $message = 'Price list record with id %s not found, while reading default price list system configuration.';
        throw new \InvalidArgumentException(sprintf($message, $config[self::PRICE_LIST_KEY]));
    }

    /**
     * @return ObjectManager
     */
    protected function getManagerForPriceList()
    {
        if (!$this->managerForPriceList) {
            $this->managerForPriceList = $this->doctrine->getManagerForClass($this->priceListClassName);
        }

        return $this->managerForPriceList;
    }
}
