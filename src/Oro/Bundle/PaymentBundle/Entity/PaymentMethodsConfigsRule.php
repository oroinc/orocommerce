<?php

namespace Oro\Bundle\PaymentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Store payment method config rule in database.
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\PaymentBundle\Entity\Repository\PaymentMethodsConfigsRuleRepository")
 * @ORM\Table(name="oro_payment_mtds_cfgs_rl")
 * @Config(
 *      routeName="oro_payment_methods_configs_rule_index",
 *      routeView="oro_payment_methods_configs_rule_view",
 *      routeCreate="oro_payment_methods_configs_rule_create",
 *      routeUpdate="oro_payment_methods_configs_rule_update",
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class PaymentMethodsConfigsRule implements
    RuleOwnerInterface,
    OrganizationAwareInterface,
    ExtendEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
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
     * @var Collection|PaymentMethodConfig[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig",
     *     mappedBy="methodsConfigsRule",
     *     cascade={"ALL"},
     *     fetch="EAGER",
     *     orphanRemoval=true
     * )
     */
    protected $methodConfigs;

    /**
     * @var RuleInterface
     *
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\RuleBundle\Entity\Rule",
     *     cascade={"persist", "remove"}
     * )
     * @ORM\JoinColumn(name="rule_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $rule;

    /**
     * @var Collection|PaymentMethodsConfigsRuleDestination[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestination",
     *     mappedBy="methodsConfigsRule",
     *     cascade={"ALL"},
     *     fetch="EAGER",
     *     orphanRemoval=true
     * )
     */
    protected $destinations;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=10
     *          }
     *      }
     *  )
     */
    protected $currency;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * @var Website[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinTable(
     *      name="oro_payment_mtds_rule_website",
     *      joinColumns={
     *          @ORM\JoinColumn(name="oro_payment_mtds_cfgs_rl_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="website_id", referencedColumnName="id", onDelete="CASCADE")
     *      }
     * )
     */
    protected $websites;

    public function __construct()
    {
        $this->methodConfigs = new ArrayCollection();
        $this->destinations = new ArrayCollection();
        $this->websites = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param RuleInterface $rule
     *
     * @return $this
     */
    public function setRule(RuleInterface $rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Collection|PaymentMethodConfig[]
     */
    public function getMethodConfigs()
    {
        return $this->methodConfigs;
    }

    /**
     * @param PaymentMethodConfig $methodConfig
     *
     * @return bool
     */
    public function hasMethodConfig(PaymentMethodConfig $methodConfig)
    {
        return $this->methodConfigs->contains($methodConfig);
    }

    /**
     * @param PaymentMethodConfig $methodConfig
     *
     * @return $this
     */
    public function addMethodConfig(PaymentMethodConfig $methodConfig)
    {
        if (!$this->hasMethodConfig($methodConfig)) {
            $this->methodConfigs[] = $methodConfig;
            $methodConfig->setMethodsConfigsRule($this);
        }

        return $this;
    }

    /**
     * @param PaymentMethodConfig $methodConfig
     *
     * @return $this
     */
    public function removeMethodConfig(PaymentMethodConfig $methodConfig)
    {
        if ($this->hasMethodConfig($methodConfig)) {
            $this->methodConfigs->removeElement($methodConfig);
        }

        return $this;
    }

    /**
     * @return Collection|PaymentMethodsConfigsRuleDestination[]
     */
    public function getDestinations()
    {
        return $this->destinations;
    }

    /**
     * @param PaymentMethodsConfigsRuleDestination $destination
     *
     * @return $this
     */
    public function addDestination(PaymentMethodsConfigsRuleDestination $destination)
    {
        if (!$this->destinations->contains($destination)) {
            $this->destinations->add($destination);
            $destination->setMethodsConfigsRule($this);
        }

        return $this;
    }

    /**
     * @param PaymentMethodsConfigsRuleDestination $destination
     *
     * @return $this
     */
    public function removeDestination(PaymentMethodsConfigsRuleDestination $destination)
    {
        if ($this->destinations->contains($destination)) {
            $this->destinations->removeElement($destination);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return OrganizationInterface
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param OrganizationInterface $organization
     *
     * @return $this
     */
    public function setOrganization(OrganizationInterface $organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @param Website $website
     *
     * @return $this
     */
    public function addWebsite(Website $website)
    {
        if (!$this->websites->contains($website)) {
            $this->websites->add($website);
        }

        return $this;
    }

    /**
     * @param Website $website
     *
     * @return $this
     */
    public function removeWebsite(Website $website)
    {
        if ($this->websites->contains($website)) {
            $this->websites->removeElement($website);
        }

        return $this;
    }

    /**
     * @return Collection|Website[]
     */
    public function getWebsites()
    {
        return $this->websites;
    }
}
