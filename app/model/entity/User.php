<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Tulinkry\Model\Doctrine\Entity;


/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User extends Entity\User
{
    /**
     * @ORM\Column ( name="`right`", type = "string", length = 255 )
     */
    protected $right = 'user';

    /**
     * @ORM\Column ( name="`name`", type = "string", length = 255, nullable = true )
     */
    protected $name = NULL;


    /**
     * @ORM\OneToMany( targetEntity="Rating", mappedBy="user", cascade={ "all" } )
     * @ORM\JoinColumn ( name="rating_id", referencedColumnName="rating_id" )
     * @ORM\OrderBy({"date" = "DESC"})
     */
    protected $ratings;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct ();
        $this->ratings = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add rating
     *
     * @param \Entity\Rating $rating
     *
     * @return User
     */
    public function addRating(\Entity\Rating $rating)
    {
        $this->ratings[] = $rating;
        $rating->setUser($this);

        return $this;
    }

    /**
     * Remove rating
     *
     * @param \Entity\Rating $rating
     */
    public function removeRating(\Entity\Rating $rating)
    {
        $this->ratings->removeElement($rating);
        $rating->setUser(null);
    }

    /**
     * Get ratings
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRatings()
    {
        return $this->ratings;
    }

    /**
     * Set right
     *
     * @param string $right
     *
     * @return User
     */
    public function setRight($right)
    {
        $this->right = $right;

        return $this;
    }

    /**
     * Get right
     *
     * @return string
     */
    public function getRight()
    {
        return $this->right;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return User
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

    public function hasRated ( Pub $p )
    {
        foreach ( $this -> getRatings () as $rating )
            if ( $rating -> pub === $p )
                return true;
        return false;
    }

}
