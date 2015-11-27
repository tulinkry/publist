<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Pub
 *
 * @ORM\Table(name="pub_descriptions", uniqueConstraints={@ORM\UniqueConstraint(name="description_unique", columns={"description_id", "version"})},)
 * @ORM\Entity
 */
class Description extends \Tulinkry\Model\Doctrine\Entity\BaseEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="description_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $version;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="blob", nullable=true, unique=false)
     */
    private $text;

    /**
     * @ORM\ManyToOne ( targetEntity = "Pub", inversedBy="descriptions" )
     * @ORM\JoinColumn ( name="pub_id", referencedColumnName = "pub_id" )
     */
    private $pub;

    /**
     * @ORM\ManyToOne ( targetEntity = "User" )
     * @ORM\JoinColumn ( name="user_id", referencedColumnName = "user_id" )
     */
    private $user;



    /**
     * Constructor
     */
    public function __construct()
    {}

    /**
     * Set text
     *
     * @param string $text
     *
     * @return Description
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText()
    {
        if ( ! $this->text )
            return "";
        if ( is_string ( $this->text ) )
            return $this->text;
        return $this->text = stream_get_contents ( $this->text );
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
     * Set version
     *
     * @param integer $version
     *
     * @return Description
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set user
     *
     * @param \Entity\User $user
     *
     * @return Description
     */
    public function setUser(\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set pub
     *
     * @param \Entity\Pub $pub
     *
     * @return Description
     */
    public function setPub(\Entity\Pub $pub = null)
    {
        $this->pub = $pub;

        return $this;
    }

    /**
     * Get pub
     *
     * @return \Entity\Pub
     */
    public function getPub()
    {
        return $this->pub;
    }
}
