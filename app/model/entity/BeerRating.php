<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Pub
 *
 * @ORM\Table(name="rating_beer")
 * @ORM\Entity
 */
class BeerRating extends \Tulinkry\Model\Doctrine\Entity\BaseEntity
{


    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Beer") 
     * @ORM\JoinColumn ( name="beer_id", referencedColumnName = "beer_id" )
     */
    protected $beer;

    /** 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Rating", inversedBy="beers") 
     * @ORM\JoinColumn ( name="rating_id", referencedColumnName = "rating_id" )
     */
    protected $rating;

    /**
     * @var float
     *
     * @ORM\Column(name="beer_criteria", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $beerCriteria = NULL;

    /**
     * @var float
     *
     * @ORM\Column(name="beer_price", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $beerPrice = NULL;


    /**
     * Set beerCriteria
     *
     * @param float $beerCriteria
     *
     * @return BeerRating
     */
    public function setBeerCriteria($beerCriteria)
    {
        $this->beerCriteria = $beerCriteria;

        return $this;
    }

    /**
     * Get beerCriteria
     *
     * @return float
     */
    public function getBeerCriteria()
    {
        return $this->beerCriteria;
    }

    /**
     * Set beerPrice
     *
     * @param float $beerPrice
     *
     * @return BeerRating
     */
    public function setBeerPrice($beerPrice)
    {
        $this->beerPrice = $beerPrice;

        return $this;
    }

    /**
     * Get beerPrice
     *
     * @return float
     */
    public function getBeerPrice()
    {
        return $this->beerPrice;
    }

    /**
     * Set beer
     *
     * @param \Entity\Beer $beer
     *
     * @return BeerRating
     */
    public function setBeer(\Entity\Beer $beer)
    {
        $this->beer = $beer;

        return $this;
    }

    /**
     * Get beer
     *
     * @return \Entity\Beer
     */
    public function getBeer()
    {
        return $this->beer;
    }

    /**
     * Set rating
     *
     * @param \Entity\Rating $rating
     *
     * @return BeerRating
     */
    public function setRating(\Entity\Rating $rating)
    {
        $this->rating = $rating;

        return $this;
    }

    /**
     * Get rating
     *
     * @return \Entity\Rating
     */
    public function getRating()
    {
        return $this->rating;
    }
}
