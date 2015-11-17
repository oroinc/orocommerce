<?php

namespace OroB2B\Bundle\PricingBundle\SystemConfig;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PriceListConfigConverter implements PriceListConfigConverterInterface
{
    const PRIORITY_KEY = 'priority';
    const PRICE_LIST_KEY = 'priceList';

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var string
     */
    private $priceListClassName;

    /**
     * @var string
     */
    private $managerForPriceList;

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
     * @param PriceListConfigBag $configBag
     * @return array;
     */
    public function convertBeforeSave(PriceListConfigBag $configBag)
    {
        $configs = $configBag->getConfigs()->toArray();
        $result = array_map(function ($config) {
            /** @var PriceListConfig $config */
            return [
                self::PRICE_LIST_KEY => $config->getPriceList()->getId(),
                self::PRIORITY_KEY => $config->getPriority()
            ];
        }, $configs);

        return $result;
    }

    /**
     * @param array $configs
     * @return PriceListConfigBag
     */
    public function convertFromSaved(array $configs)
    {
        $ids = array_map(function ($config) {
            return $config[self::PRICE_LIST_KEY];
        }, $configs);

        $configBag = new PriceListConfigBag();

        if (0 !== count($ids)) {
            $priceLists = $this->getManagerForPriceList()
                ->getRepository('OroB2BPricingBundle:PriceList')
                ->findBy(['id' => $ids]) ?: [];

            foreach ($configs as $config) {
                $priceListConfig = $this->createPriceListConfig($config, $priceLists);
                $configBag->addConfig($priceListConfig);
            }
        }

        return $configBag;
    }

    /**
     * @param $config
     * @param PriceList[] $priceLists
     * @return PriceListConfig
     */
    private function createPriceListConfig($config, $priceLists)
    {
        $configModel = new PriceListConfig();

        foreach ($priceLists as $priceList) {
            if ($config[self::PRICE_LIST_KEY] === $priceList->getId()) {
                $configModel->setPriceList($priceList)
                    ->setPriority($config[self::PRIORITY_KEY]);
                return $configModel;
            }
        }

        $message = 'Price list record with id %s not found, while reading default price list system configuration.';
        throw new \InvalidArgumentException(sprintf($message, $config[self::PRICE_LIST_KEY]));
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    private function getManagerForPriceList()
    {
        if (!$this->managerForPriceList) {
            $manager = $this->doctrine->getManagerForClass($this->priceListClassName);

            if (!$manager) {
                throw new \InvalidArgumentException(
                    sprintf('Entity Manager for class %s doesn\'t exist.', $this->priceListClassName)
                );
            }
            $this->managerForPriceList = $manager;
        }

        return $this->managerForPriceList;
    }
}
