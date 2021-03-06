<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Pub
 *
 * @ORM\Table(name="beers", uniqueConstraints={@ORM\UniqueConstraint(name="beer_unique", columns={"name", "degree"})})
 * @ORM\Entity
 */
class Beer extends \Tulinkry\Model\Doctrine\Entity\BaseEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="beer_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="degree", type="integer", nullable=true, unique=false)
     */
    private $degree;

    /**
     * @var string
     *
     * @ORM\Column(name="link", type="string", length=255, nullable=true, unique=false)
     */
    private $link;    

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
     * Set name
     *
     * @param string $name
     *
     * @return Beer
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }



    /**
     * Set degree
     *
     * @param integer $degree
     *
     * @return Beer
     */
    public function setDegree($degree)
    {
        $this->degree = $degree;

        return $this;
    }

    /**
     * Get degree
     *
     * @return integer
     */
    public function getDegree()
    {
        return $this->degree;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        $s = $this->name;
        $s .= $this->degree ? (' ' . $this -> degree . html_entity_decode('&deg;', ENT_NOQUOTES,'UTF-8')) : '';
        return $s;
    }    

    /**
     * Set link
     *
     * @param integer $link
     *
     * @return Beer
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link
     *
     * @return integer
     */
    public function getLink()
    {
        return $this->link;
    }
}
