<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\MappedSuperclass
 */
class BasePriceList implements DatesAwareInterface
{
    use DatesAwareTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $name;

    /**
     * @var BasePriceListCurrency[]|Collection
     */
    protected $currencies;

    public function __construct()
    {
        $this->currencies = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->name;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param array|string[] $currencies
     * @return $this
     */
    public function setCurrencies(array $currencies)
    {
        $knownCurrencies = $this->getCurrencies();
        $removedCurrencies = array_diff($knownCurrencies, $currencies);
        $addedCurrencies = array_diff($currencies, $knownCurrencies);

        foreach ($removedCurrencies as $currency) {
            $this->removeCurrencyByCode($currency);
        }

        foreach ($addedCurrencies as $currency) {
            $this->addCurrencyByCode($currency);
        }

        return $this;
    }

    /**
     * Get currencies
     *
     * @return array|string[]
     */
    public function getCurrencies()
    {
        $currencies = $this->currencies
            ->map(
                function (BasePriceListCurrency $priceListCurrency) {
                    return $priceListCurrency->getCurrency();
                }
            )
            ->toArray();

        sort($currencies);

        return $currencies;
    }

    /**
     * @param string $currency
     *
     * @return $this
     */
    public function addCurrencyByCode($currency)
    {
        if (!$this->hasCurrencyCode($currency)) {
            $priceListCurrency = $this->createPriceListCurrency();
            $priceListCurrency->setPriceList($this);
            $priceListCurrency->setCurrency($currency);
            $this->currencies->add($priceListCurrency);
        }

        return $this;
    }

    /**
     * @param string $currency
     * @return bool
     */
    public function hasCurrencyCode($currency)
    {
        return (bool)$this->getPriceListCurrencyByCode($currency);
    }

    /**
     * @param string $currency
     *
     * @return $this
     */
    public function removeCurrencyByCode($currency)
    {
        $priceListCurrency = $this->getPriceListCurrencyByCode($currency);
        if ($priceListCurrency) {
            $this->currencies->removeElement($priceListCurrency);
        }

        return $this;
    }

    /**
     * @param string $currency
     * @return BasePriceListCurrency
     */
    public function getPriceListCurrencyByCode($currency)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('currency', $currency));

        return $this->currencies->matching($criteria)->first();
    }

    /**
     * @return BasePriceListCurrency
     */
    protected function createPriceListCurrency()
    {
        return new BasePriceListCurrency();
    }
}
