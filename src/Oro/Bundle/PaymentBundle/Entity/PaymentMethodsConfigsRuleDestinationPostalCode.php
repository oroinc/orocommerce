<?php

namespace Oro\Bundle\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Store payment method config rule destination post code in database.
 *
 * @ORM\Entity
 * @ORM\Table(name="oro_payment_mtdscfgsrl_dst_pc")
 * @ORM\HasLifecycleCallbacks
 * @Config(
 *     mode="hidden",
 * )
 */
class PaymentMethodsConfigsRuleDestinationPostalCode implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "identity"=true,
     *              "order"=10
     *          }
     *      }
     * )
     */
    protected $name;

    /**
     * @var PaymentMethodsConfigsRuleDestination
     *
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestination",
     *     inversedBy="postalCodes"
     * )
     * @ORM\JoinColumn(name="destination_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $destination;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return PaymentMethodsConfigsRuleDestination
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @param PaymentMethodsConfigsRuleDestination $destination
     * @return $this
     */
    public function setDestination(PaymentMethodsConfigsRuleDestination $destination)
    {
        $this->destination = $destination;

        return $this;
    }

    /**
     * @return mixed
     */
    public function __toString()
    {
        return (string)$this->getName();
    }
}
