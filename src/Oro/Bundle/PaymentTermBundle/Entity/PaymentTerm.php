<?php

namespace Oro\Bundle\PaymentTermBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Implements Payment Term payment method
 *
 * @ORM\Table(name="oro_payment_term")
 * @ORM\Entity()
 * @Config(
 *      routeName="oro_payment_term_index",
 *      routeView="oro_payment_term_view",
 *      routeUpdate="oro_payment_term_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-usd"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "form"={
 *              "form_type"="Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermSelectType",
 *              "grid_name"="payment-terms-select-grid",
 *          }
 *      }
 * )
 */
class PaymentTerm implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *     defaultValues={
     *         "importexport"={
     *              "excluded"=true
     *         }
     *     }
     * )
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="label", type="string")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "identity"=true
     *         }
     *      }
     * )
     */
    protected $label;

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->label;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set label
     *
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }
}
