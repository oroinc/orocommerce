<?php

namespace Oro\Bundle\AccountBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\NavigationBundle\Entity\AbstractPinbarTab;

/**
 * Pinbar Tab Entity
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\AccountBundle\Entity\Repository\PinbarTabRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="orob2b_acc_nav_item_pinbar")
 */
class PinbarTab extends AbstractPinbarTab
{
    /**
     * @var NavigationItem $item
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\AccountBundle\Entity\NavigationItem", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $item;
}
