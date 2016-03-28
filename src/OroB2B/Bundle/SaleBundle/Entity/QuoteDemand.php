<?php

namespace OroB2B\Bundle\SaleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\AccountAwareInterface;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 *
 * @ORM\Table(name="orob2b_quote_demand")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\SaleBundle\Entity\Repository\QuoteProductDemandRepository")
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-list-alt"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *      }
 * )
 */
class QuoteDemand implements CheckoutSourceEntityInterface
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Quote
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\SaleBundle\Entity\Quote")
     * @ORM\JoinColumn(name="quote_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $quote;

    /**
     *
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\SaleBundle\Entity\QuoteProductDemand",
     *     mappedBy="quoteDemand", cascade={"all"})
     */
    protected $demandOffers;

    public function __construct()
    {
        $this->demandOffers = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Quote
     */
    public function getQuote()
    {
        return $this->quote;
    }

    /**
     * @param Quote $quote
     */
    public function setQuote(Quote $quote)
    {
        $this->quote = $quote;
    }

    /**
     * @return QuoteProductDemand[]|Collection
     */
    public function getDemandOffers()
    {
        return $this->demandOffers;
    }

    /**
     * @param QuoteProductDemand $demandOffer
     * @return $this
     */
    public function addDemandOffer(QuoteProductDemand $demandOffer)
    {
        if (!$this->demandOffers->contains($demandOffer)) {
            $this->demandOffers->add($demandOffer);
        }
        return $this;
    }

    /**
     * @param QuoteProductDemand $demandOffer
     * @return $this
     */
    public function removeDemandOffer(QuoteProductDemand $demandOffer)
    {
        if ($this->demandOffers->contains($demandOffer)) {
            $this->demandOffers->remove($demandOffer);
        }
        return $this;
    }
}
