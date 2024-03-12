<?php

namespace Oro\Bundle\PaymentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroPaymentBundle_Entity_PaymentMethodsConfigsRuleDestinationPostalCode;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Store payment method config rule destination post code in database.
 *
 * @mixin OroPaymentBundle_Entity_PaymentMethodsConfigsRuleDestinationPostalCode
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_payment_mtdscfgsrl_dst_pc')]
#[ORM\HasLifecycleCallbacks]
#[Config(mode: 'hidden')]
class PaymentMethodsConfigsRuleDestinationPostalCode implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: false)]
    #[ConfigField(
        defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['identity' => true, 'order' => 10]]
    )]
    protected ?string $name = null;

    #[ORM\ManyToOne(targetEntity: PaymentMethodsConfigsRuleDestination::class, inversedBy: 'postalCodes')]
    #[ORM\JoinColumn(name: 'destination_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?PaymentMethodsConfigsRuleDestination $destination = null;

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
