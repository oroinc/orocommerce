<?php

namespace Oro\Bundle\PricingBundle\SystemConfig;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\PricingBundle\Entity\PriceList;

class PriceListConfigConverter
{
    const MERGE_KEY = 'mergeAllowed';
    const PRIORITY_KEY = 'priority';
    const PRICE_LIST_KEY = 'priceList';

    /** @var RegistryInterface */
    protected $doctrine;

    /** @var string */
    protected $priceListClassName;

    /** @var string */
    protected $managerForPriceList;

    /**
     * PriceListSystemConfigSubscriber constructor.
     * @param RegistryInterface $doctrine
     * @param string $priceListClassName
     */
    public function __construct(RegistryInterface $doctrine, $priceListClassName)
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
                    self::PRIORITY_KEY => $config->getPriority(),
                    self::MERGE_KEY => $config->isMergeAllowed(),
                ];
            },
            $configs
        );

        return $result;
    }

    /**
     * @param array $configs
     * @return array
     */
    public function convertFromSaved(array $configs)
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
                function ($a, $b) {
                    /** @var PriceListConfig $a */
                    /** @var PriceListConfig $b */
                    return ($a->getPriority() > $b->getPriority()) ? -1 : 1;
                }
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
                    ->setPriority($config[self::PRIORITY_KEY])
                    ->setMergeAllowed($config[self::MERGE_KEY]);

                return $configModel;
            }
        }

        $message = 'Price list record with id %s not found, while reading default price list system configuration.';
        throw new \InvalidArgumentException(sprintf($message, $config[self::PRICE_LIST_KEY]));
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    protected function getManagerForPriceList()
    {
        if (!$this->managerForPriceList) {
            $this->managerForPriceList = $this->doctrine->getManagerForClass($this->priceListClassName);
        }

        return $this->managerForPriceList;
    }
}
