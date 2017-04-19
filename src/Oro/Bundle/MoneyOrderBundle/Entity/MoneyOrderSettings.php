<?php

namespace Oro\Bundle\MoneyOrderBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\MoneyOrderBundle\Entity\Repository\MoneyOrderSettingsRepository")
 */
class MoneyOrderSettings extends Transport
{
    /**
     * @var string
     *
     * @ORM\Column(name="money_order_pay_to", type="string", length=255, nullable=true)
     */
    private $payTo;

    /**
     * @var string
     *
     * @ORM\Column(name="money_order_send_to", type="text", nullable=true)
     */
    private $sendTo;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_money_order_trans_label",
     *      joinColumns={
     *          @ORM\JoinColumn(name="transport_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    private $labels;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_money_order_short_label",
     *      joinColumns={
     *          @ORM\JoinColumn(name="transport_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    private $shortLabels;

    /**
     * @var ParameterBag
     */
    private $settings;

    public function __construct()
    {
        $this->labels = new ArrayCollection();
        $this->shortLabels = new ArrayCollection();
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * @param LocalizedFallbackValue $label
     *
     * @return MoneyOrderSettings
     */
    public function addLabel(LocalizedFallbackValue $label)
    {
        if (!$this->labels->contains($label)) {
            $this->labels->add($label);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $label
     *
     * @return MoneyOrderSettings
     */
    public function removeLabel(LocalizedFallbackValue $label)
    {
        if ($this->labels->contains($label)) {
            $this->labels->removeElement($label);
        }

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getShortLabels()
    {
        return $this->shortLabels;
    }

    /**
     * @param LocalizedFallbackValue $label
     *
     * @return MoneyOrderSettings
     */
    public function addShortLabel(LocalizedFallbackValue $label)
    {
        if (!$this->shortLabels->contains($label)) {
            $this->shortLabels->add($label);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $label
     *
     * @return MoneyOrderSettings
     */
    public function removeShortLabel(LocalizedFallbackValue $label)
    {
        if ($this->shortLabels->contains($label)) {
            $this->shortLabels->removeElement($label);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getPayTo()
    {
        return $this->payTo;
    }

    /**
     * @param string $payTo
     *
     * @return MoneyOrderSettings
     */
    public function setPayTo($payTo)
    {
        $this->payTo = $payTo;

        return $this;
    }

    /**
     * @return string
     */
    public function getSendTo()
    {
        return $this->sendTo;
    }

    /**
     * @param string $sendTo
     *
     * @return MoneyOrderSettings
     */
    public function setSendTo($sendTo)
    {
        $this->sendTo = $sendTo;

        return $this;
    }

    /**
     * @return ParameterBag
     */
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag(
                [
                    'pay_to' => $this->getPayTo(),
                    'send_to' => $this->getSendTo(),
                    'labels' => $this->getLabels()->toArray(),
                ]
            );
        }

        return $this->settings;
    }
}
