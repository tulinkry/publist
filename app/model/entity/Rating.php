<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Rating
 *
 * @ORM\Table(name="ratings")
 * @ORM\Entity
 */
class Rating extends \Tulinkry\Model\Doctrine\Entity\BaseEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="rating_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column ( name = "date", type = "datetime" )
     */
    private $date;


    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\BeerRating", mappedBy="rating", cascade={"all"})
     * @ORM\JoinColumn ( name="rating_id", referencedColumnName = "rating_id" )
     */
    private $beers;

    /**
     * @var float
     *
     * @ORM\Column(name="wine_criteria", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $wineCriteria = NULL;

    /**
     * @var float
     *
     * @ORM\Column(name="wine_price", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $winePrice = NULL;

    /**
     * @var float
     *
     * @ORM\Column(name="food_criteria", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $foodCriteria = NULL;

    /**
     * @var float
     *
     * @ORM\Column(name="food_price_criteria", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $foodPriceCriteria = 0.0;

    /**
     * @var float
     *
     * @ORM\Column(name="toalets_criteria", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $toaletsCriteria = 0.0;

    /**
     * @var float
     *
     * @ORM\Column(name="service_criteria", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $serviceCriteria = 0.0;

    /**
     * @var float
     *
     * @ORM\Column(name="overall_criteria", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $overallCriteria = 0.0;

    /**
     * @var float
     *
     * @ORM\Column(name="interier_criteria", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $interierCriteria = 0.0;

    /**
     * @var float
     *
     * @ORM\Column(name="exterier_criteria", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $exterierCriteria = 0.0;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $name;

    /**
     * @var boolean
     * @ORM\Column(name="garden", type="boolean", nullable=true, unique=false)
     */
    private $garden;

    /**
     * @var boolean
     * @ORM\Column(name="calculated", type="boolean", nullable=true, unique=false)
     */
    private $calculated;

    /**
     * @var \Entity\Pub
     *
     * @ORM\ManyToOne(targetEntity="Entity\Pub", inversedBy="ratings")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="pub_id", referencedColumnName="pub_id", nullable=true)
     * })
     */
    private $pub;

    /**
     * @ORM\ManyToOne ( targetEntity = "User", inversedBy = "ratings" )
     * @ORM\JoinColumn ( name="user_id", referencedColumnName = "user_id" )
     */
    private $user;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this -> date = new \Tulinkry\DateTime;
        $this->beers = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set wineCriteria
     *
     * @param float $wineCriteria
     *
     * @return Rating
     */
    public function setWineCriteria($wineCriteria)
    {
        $this->wineCriteria = $wineCriteria;

        return $this;
    }

    /**
     * Get wineCriteria
     *
     * @return float
     */
    public function getWineCriteria()
    {
        return $this->wineCriteria;
    }

    /**
     * Set foodCriteria
     *
     * @param float $foodCriteria
     *
     * @return Rating
     */
    public function setFoodCriteria($foodCriteria)
    {
        $this->foodCriteria = $foodCriteria;

        return $this;
    }

    /**
     * Get foodCriteria
     *
     * @return float
     */
    public function getFoodCriteria()
    {
        return $this->foodCriteria;
    }

    /**
     * Set toaletsCriteria
     *
     * @param float $toaletsCriteria
     *
     * @return Rating
     */
    public function setToaletsCriteria($toaletsCriteria)
    {
        $this->toaletsCriteria = $toaletsCriteria;

        return $this;
    }

    /**
     * Get toaletsCriteria
     *
     * @return float
     */
    public function getToaletsCriteria()
    {
        return $this->toaletsCriteria;
    }

    /**
     * Set serviceCriteria
     *
     * @param float $serviceCriteria
     *
     * @return Rating
     */
    public function setServiceCriteria($serviceCriteria)
    {
        $this->serviceCriteria = $serviceCriteria;

        return $this;
    }

    /**
     * Get serviceCriteria
     *
     * @return float
     */
    public function getServiceCriteria()
    {
        return $this->serviceCriteria;
    }

    /**
     * Set overallCriteria
     *
     * @param float $overallCriteria
     *
     * @return Rating
     */
    public function setOverallCriteria($overallCriteria)
    {
        $this->overallCriteria = $overallCriteria;

        return $this;
    }

    /**
     * Get overallCriteria
     *
     * @return float
     */
    public function getOverallCriteria()
    {
        return $this->overallCriteria;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Rating
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
     * Set pub
     *
     * @param \Entity\Pub $pub
     *
     * @return Rating
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

    /**
     * Set winePrice
     *
     * @param float $winePrice
     *
     * @return Rating
     */
    public function setWinePrice($winePrice)
    {
        $this->winePrice = $winePrice;

        return $this;
    }

    /**
     * Get winePrice
     *
     * @return float
     */
    public function getWinePrice()
    {
        return $this->winePrice;
    }

    /**
     * Set foodPriceCriteria
     *
     * @param float $foodPriceCriteria
     *
     * @return Rating
     */
    public function setFoodPriceCriteria($foodPriceCriteria)
    {
        $this->foodPriceCriteria = $foodPriceCriteria;

        return $this;
    }

    /**
     * Get foodPriceCriteria
     *
     * @return float
     */
    public function getFoodPriceCriteria()
    {
        return $this->foodPriceCriteria;
    }

    /**
     * Set date
     *
     * @param string $date
     *
     * @return Rating
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set user
     *
     * @param \Entity\User $user
     *
     * @return Rating
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
     * Set interierCriteria
     *
     * @param float $interierCriteria
     *
     * @return Rating
     */
    public function setInterierCriteria($interierCriteria)
    {
        $this->interierCriteria = $interierCriteria;

        return $this;
    }

    /**
     * Get interierCriteria
     *
     * @return float
     */
    public function getInterierCriteria()
    {
        return $this->interierCriteria;
    }

    /**
     * Set exterierCriteria
     *
     * @param float $exterierCriteria
     *
     * @return Rating
     */
    public function setExterierCriteria($exterierCriteria)
    {
        $this->exterierCriteria = $exterierCriteria;

        return $this;
    }

    /**
     * Get exterierCriteria
     *
     * @return float
     */
    public function getExterierCriteria()
    {
        return $this->exterierCriteria;
    }

    /**
     * Add beer
     *
     * @param \Entity\BeerRating $beer
     *
     * @return Rating
     */
    public function addBeer(\Entity\BeerRating $beer)
    {
        $this->beers[] = $beer;
        $beer->setRating($this);

        return $this;
    }

    /**
     * Remove beer
     *
     * @param \Entity\BeerRating $beer
     */
    public function removeBeer(\Entity\BeerRating $beer)
    {
        $this->beers->removeElement($beer);
    }

    /**
     * Get beers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBeers()
    {
        return $this->beers;
    }

    /**
     * Set garden
     *
     * @param boolean $garden
     *
     * @return Rating
     */
    public function setGarden($garden)
    {
        $this->garden = $garden;

        return $this;
    }

    /**
     * Get garden
     *
     * @return boolean
     */
    public function getGarden()
    {
        return $this->garden;
    }


    /**
     * Get overallCriteria
     *
     * @return float
     */
    public function getBeerCriteria()
    {
        return \Model\PubModel::singleBeerRating ( $this );
    }

    /**
     * Get beerCriteria
     *
     * @return float
     */
    public function getBeerPrice()
    {
        if(!$this->calculated)
            return [];
        $beer_distinct = [];
        foreach ( $this -> getBeers () as $beer_rating )
        {
            if (isset($beer_distinct [ $beer_rating -> beer -> id ]))
                $p = $beer_distinct [ $beer_rating -> beer -> id ];
            {
                $p = (new \StdClass);
                $p -> count = 0;
                $p -> price = 0;
                $p -> beer = $beer_rating -> beer;
            }    
            if ( $beer_rating->beerPrice !== NULL )
            {
                $p -> price = $p -> price * $p -> count;
                $p -> price += $beer_rating -> beerPrice;
                $p -> count ++;
                $p -> price = $p -> price / $p -> count;
            }

            $beer_distinct [ $beer_rating -> beer -> id ] = $p;
        }

        return $beer_distinct;

    }



    /**
     * Set calculated
     *
     * @param boolean $calculated
     *
     * @return Rating
     */
    public function setCalculated($calculated)
    {
        $this->calculated = $calculated;

        return $this;
    }

    /**
     * Get calculated
     *
     * @return boolean
     */
    public function getCalculated()
    {
        return $this->calculated;
    }
}
