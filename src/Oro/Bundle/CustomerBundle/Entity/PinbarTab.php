<?php

namespace Oro\Bundle\CustomerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\NavigationBundle\Entity\AbstractPinbarTab;

/**
 * Pinbar Tab Entity
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\CustomerBundle\Entity\Repository\PinbarTabRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="oro_acc_nav_item_pinbar")
 */
class PinbarTab extends AbstractPinbarTab
{
    /**
     * @var NavigationItem $item
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\NavigationItem", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $item;
}
