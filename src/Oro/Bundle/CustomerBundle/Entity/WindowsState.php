<?php

namespace Oro\Bundle\CustomerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\WindowsBundle\Entity\AbstractWindowsState;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\CustomerBundle\Entity\Repository\WindowsStateRepository")
 * @ORM\Table(name="oro_acc_windows_state",
 *      indexes={@ORM\Index(name="oro_acc_windows_state_acu_idx", columns={"customer_user_id"})})
 */
class WindowsState extends AbstractWindowsState
{
    /**
     * @var CustomerUserIdentity $user
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerUserIdentity")
     * @ORM\JoinColumn(name="customer_user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $user;

    /**
     * {@inheritdoc}
     */
    public function setUser(UserInterface $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        return $this->user;
    }
}
