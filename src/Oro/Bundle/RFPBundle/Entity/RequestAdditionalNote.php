<?php

namespace Oro\Bundle\RFPBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Table(name="oro_rfp_request_add_note")
 * @ORM\Entity
 * @Config
 */
class RequestAdditionalNote implements DatesAwareInterface
{
    use DatesAwareTrait;

    const TYPE_CUSTOMER_NOTE = 'customer_note';
    const TYPE_SELLER_NOTE = 'seller_note';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Request
     *
     * @ORM\ManyToOne(targetEntity="Request", inversedBy="requestAdditionalNotes")
     * @ORM\JoinColumn(name="request_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $request;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=100)
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="author", type="string", length=100)
     */
    protected $author;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    protected $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="text")
     */
    protected $text;

    /**
     * @return array
     */
    public function getAllowedTypes()
    {
        return [self::TYPE_CUSTOMER_NOTE, self::TYPE_SELLER_NOTE];
    }

    /**
     * @param string $type
     * @return bool
     */
    public function isTypeAllowed($type)
    {
        return in_array($type, $this->getAllowedTypes(), true);
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param string $type
     * @return $this
     * @throws \LogicException
     */
    public function setType($type)
    {
        if (!$this->isTypeAllowed($type)) {
            throw new \LogicException(sprintf('Type "%s" is not allowed', $type));
        }

        $this->type = $type;

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
     * @param string $author
     * @return $this
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param int $userId
     * @return $this
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }
}
