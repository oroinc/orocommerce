<?php

namespace Oro\Bundle\PaymentTermBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroPaymentTermBundle_Entity_PaymentTerm;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermSelectType;

/**
 * Implements Payment Term payment method
 *
 * @mixin OroPaymentTermBundle_Entity_PaymentTerm
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_payment_term')]
#[Config(
    routeName: 'oro_payment_term_index',
    routeView: 'oro_payment_term_view',
    routeUpdate: 'oro_payment_term_update',
    defaultValues: [
        'entity' => ['icon' => 'fa-usd'],
        'dataaudit' => ['auditable' => true],
        'security' => ['type' => 'ACL', 'group_name' => ''],
        'form' => ['form_type' => PaymentTermSelectType::class, 'grid_name' => 'payment-terms-select-grid']
    ]
)]
class PaymentTerm implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $id = null;

    #[ORM\Column(name: 'label', type: Types::STRING)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['identity' => true]])]
    protected ?string $label = null;

    /**
     * @return string
     */
    #[\Override]
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
