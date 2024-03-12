<?php

namespace Oro\Bundle\PaymentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroPaymentBundle_Entity_PaymentMethodConfig;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentMethodConfigRepository;

/**
 * Store payment method config in database.
 *
 * @mixin OroPaymentBundle_Entity_PaymentMethodConfig
 */
#[ORM\Entity(repositoryClass: PaymentMethodConfigRepository::class)]
#[ORM\Table(name: 'oro_payment_method_config')]
#[Config]
class PaymentMethodConfig implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PaymentMethodsConfigsRule::class, inversedBy: 'methodConfigs')]
    #[ORM\JoinColumn(name: 'configs_rule_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?PaymentMethodsConfigsRule $methodsConfigsRule = null;

    #[ORM\Column(name: 'method', type: Types::STRING, length: 255, nullable: false)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 10]])]
    protected ?string $type = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'options', type: Types::ARRAY, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 20]])]
    protected $options = [];

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return PaymentMethodsConfigsRule
     */
    public function getMethodsConfigsRule()
    {
        return $this->methodsConfigsRule;
    }

    /**
     * @param PaymentMethodsConfigsRule $methodsConfigsRule
     * @return $this
     */
    public function setMethodsConfigsRule($methodsConfigsRule)
    {
        $this->methodsConfigsRule = $methodsConfigsRule;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }
}
