<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Pub
 *
 * @ORM\Table(name="pubs")
 * @ORM\Entity
 */
class Pub extends \Tulinkry\Model\Doctrine\Entity\BaseEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="pub_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="whole_name", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $whole_name;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="long_name", type="blob", precision=0, scale=0, nullable=false, unique=false)
     */
    private $long_name;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="website", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $website;
    

    /**
     * @var string
     *
     * @ORM\Column(name="opening_hours", type="blob", precision=0, scale=0, nullable=true, unique=false)
     */
    private $opening_hours;


    /**
     * @var datetime
     * @ORM\Column ( type = "datetime" )
     */
    private $inserted;


    /**
     * @var datetime
     * @ORM\Column ( type = "datetime" )
     */
    private $updated;    

    /**
     * @var float
     *
     * @ORM\Column(name="mark", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $mark = NULL;

    /**
     * @ORM\Column (name="markVoted", type="integer")
     */
    private $markVoted = 0;


    /**
     * @var float
     *
     * @ORM\Column(name="beerMark", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $beerMark = NULL;

    /**
     * @ORM\Column (name="beerMarkVoted", type="integer")
     */
    private $beerMarkVoted = 0;

    /**
     * @var float
     *
     * Column(name="beerPrice", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $beerPrice = NULL;

    /**
     * @ORM\Column (name="beerPriceVoted", type="integer")
     */
    private $beerPriceVoted = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="wineMark", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $wineMark = NULL;

    /**
     * @ORM\Column (name="wineMarkVoted", type="integer")
     */
    private $wineMarkVoted = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="winePrice", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $winePrice = NULL;

    /**
     * @ORM\Column (name="winePriceVoted", type="integer")
     */
    private $winePriceVoted = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="foodMark", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $foodMark = NULL;

    /**
     * @ORM\Column (name="foodMarkVoted", type="integer")
     */
    private $foodMarkVoted = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="foodPrice", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $foodPrice = NULL;

    /**
     * @ORM\Column (name="foodPriceVoted", type="integer")
     */
    private $foodPriceVoted = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="toaletsMark", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $toaletsMark = NULL;

    /**
     * @var float
     *
     * @ORM\Column(name="interierMark", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $interierMark = NULL;    

    /**
     * @var float
     *
     * @ORM\Column(name="exterierMark", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $exterierMark = NULL;    

    /**
     * @var float
     *
     * @ORM\Column(name="serviceMark", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $serviceMark = NULL;

    /**
     * @var float
     *
     * @ORM\Column(name="overallMark", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $overallMark = NULL;

    /**
     * @var string
     *
     * @ORM\Column(name="location", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $location;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $address;

    /**
     * @var float
     *
     * @ORM\Column(name="latitude", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $latitude;

    /**
     * @var float
     *
     * @ORM\Column(name="longitude", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $longitude;

    /**
     * @var boolean
     *
     * @ORM\Column(name="hidden", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $hidden = false;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\Rating", mappedBy="pub", cascade={"all"})
     * @ORM\OrderBy ({"date" = "DESC"})
     */
    private $ratings;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\Description", mappedBy="pub", cascade={"all"})
     * @ORM\OrderBy ({"version" = "DESC"})
     */
    private $descriptions;

    /**
     * @ORM\ManyToOne ( targetEntity = "User" )
     * @ORM\JoinColumn ( name="user_id", referencedColumnName = "user_id" )
     */
    private $user;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ratings = new \Doctrine\Common\Collections\ArrayCollection();
        $this->descriptions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->updated = new \Tulinkry\DateTime;
        $this->inserted = new \Tulinkry\DateTime;
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
     * Set name
     *
     * @param string $name
     *
     * @return Pub
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
     * Set longName
     *
     * @param string $longName
     *
     * @return Pub
     */
    public function setLongName($longName)
    {
        return $this->setLong_name($longName);
    }

    /**
     * Get longName
     *
     * @return string
     */
    public function getLongName()
    {
        return $this->getLong_name();
    }

    /**
     * Set longName
     *
     * @param string $longName
     *
     * @return Pub
     */
    public function setLong_name($longName)
    {
        $this->long_name = $longName;

        return $this;
    }

    /**
     * Get longName
     *
     * @return string
     */
    public function getLong_name()
    {
        if ( ! $this->long_name )
            return "";
        if ( is_string ( $this->long_name ) )
            return $this->long_name;
        return $this->long_name = stream_get_contents ( $this->long_name );
    }

    /**
     * Set openingHours
     *
     * @param string $openingHours
     *
     * @return Pub
     */
    public function setOpeningHours($openingHours)
    {
        return $this->setOpening_hours($openingHours);
    }

    /**
     * Get openingHours
     *
     * @return string
     */
    public function getOpeningHours()
    {
        return $this->getOpening_hours();
    }

    /**
     * Set openingHours
     *
     * @param string $openingHours
     *
     * @return Pub
     */
    public function setOpening_hours($openingHours)
    {
        $this->opening_hours = $openingHours;

        return $this;
    }

    /**
     * Get openingHours
     *
     * @return string
     */
    public function getOpening_hours()
    {
        if ( ! $this->opening_hours )
            return "";
        if ( is_string ( $this->opening_hours ) )
            return $this->opening_hours;
        return $this->opening_hours = stream_get_contents ( $this->opening_hours );
    }


    /**
     * Set mark
     *
     * @param float $mark
     *
     * @return Pub
     */
    public function setMark($mark)
    {
        $this->mark = $mark;

        return $this;
    }

    /**
     * Get mark
     *
     * @return float
     */
    public function getMark()
    {
        return $this->mark;
    }




    /**
     * Set location
     *
     * @param string $location
     *
     * @return Pub
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return Pub
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set latitude
     *
     * @param float $latitude
     *
     * @return Pub
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude
     *
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude
     *
     * @param float $longitude
     *
     * @return Pub
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude
     *
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set hidden
     *
     * @param boolean $hidden
     *
     * @return Pub
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Get hidden
     *
     * @return boolean
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Add rating
     *
     * @param \Entity\Rating $rating
     *
     * @return Pub
     */
    public function addRating(\Entity\Rating $rating)
    {
        $this->ratings[] = $rating;
        $rating->setPub($this);
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
        $rating->setPub(null);
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
     * Set beerMark
     *
     * @param float $beerMark
     *
     * @return Pub
     */
    public function setBeerMark($beerMark)
    {
        $this->beerMark = $beerMark;

        return $this;
    }

    /**
     * Set wineMark
     *
     * @param float $wineMark
     *
     * @return Pub
     */
    public function setWineMark($wineMark)
    {
        $this->wineMark = $wineMark;

        return $this;
    }

    /**
     * Set foodMark
     *
     * @param float $foodMark
     *
     * @return Pub
     */
    public function setFoodMark($foodMark)
    {
        $this->foodMark = $foodMark;

        return $this;
    }

    /**
     * Set toaletsMark
     *
     * @param float $toaletsMark
     *
     * @return Pub
     */
    public function setToaletsMark($toaletsMark)
    {
        $this->toaletsMark = $toaletsMark;

        return $this;
    }

    /**
     * Set serviceMark
     *
     * @param float $serviceMark
     *
     * @return Pub
     */
    public function setServiceMark($serviceMark)
    {
        $this->serviceMark = $serviceMark;

        return $this;
    }

    /**
     * Set overallMark
     *
     * @param float $overallMark
     *
     * @return Pub
     */
    public function setOverallMark($overallMark)
    {
        $this->overallMark = $overallMark;

        return $this;
    }

    /**
     * Get beerMark
     *
     * @return float
     */
    public function getBeerMark()
    {
        return $this->beerMark;
    }

    /**
     * Get wineMark
     *
     * @return float
     */
    public function getWineMark()
    {
        return $this->wineMark;
    }

    /**
     * Get foodMark
     *
     * @return float
     */
    public function getFoodMark()
    {
        return $this->foodMark;
    }

    /**
     * Get toaletsMark
     *
     * @return float
     */
    public function getToaletsMark()
    {
        return $this->toaletsMark;
    }

    /**
     * Get serviceMark
     *
     * @return float
     */
    public function getServiceMark()
    {
        return $this->serviceMark;
    }

    /**
     * Get overallMark
     *
     * @return float
     */
    public function getOverallMark()
    {
        return $this->overallMark;
    }

    public function recompute ()
    {  
        $this -> beerMark = \Model\PubModel::beerRating ( $this );
        //$this -> beerPrice = \Model\PubModel::beerPriceRating ( $this );
        $this -> wineMark = \Model\PubModel::wineRating ( $this );
        $this -> winePrice = \Model\PubModel::winePriceRating ( $this );
        $this -> foodMark = \Model\PubModel::foodRating ( $this );
        $this -> foodPrice = \Model\PubModel::foodPriceRating ( $this );
        $this -> toaletsMark = \Model\PubModel::toaletsRating ( $this );
        $this -> interierMark = \Model\PubModel::interierRating ( $this );
        $this -> exterierMark = \Model\PubModel::exterierRating ( $this );
        $this -> serviceMark = \Model\PubModel::serviceRating ( $this );
        $this -> overallMark = \Model\PubModel::overallRating ( $this );
        $this -> mark = \Model\PubModel::rating ( $this );

        $this -> beerMarkVoted = $this -> beerPriceVoted = $this -> wineMarkVoted = $this -> winePriceVoted = 0;
        $this -> foodMarkVoted = $this -> foodPriceVoted =  $this -> markVoted = 0;

        foreach ( $this -> getRatings () as $rating )
        {
            // neexistuje $this -> beerMarkVoted += $rating -> beerCriteria === NULL ? 0 : 1;
            // neexistuje $this -> beerPriceVoted += $rating -> beerPrice === NULL ? 0 : 1;
            $this -> wineMarkVoted += $rating -> wineCriteria === NULL ? 0 : 1;
            $this -> winePriceVoted += $rating -> winePrice === NULL ? 0 : 1;
            $this -> foodMarkVoted += $rating -> foodCriteria === NULL ? 0 : 1;
            $this -> foodPriceVoted += $rating -> foodPriceCriteria === NULL ? 0 : 1;
            $this -> markVoted += 1;
        }
    }

    /**
     * Set beerPrice
     *
     * @param float $beerPrice
     *
     * @return Pub
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
        if($this->beerPrice)
            return $this->beerPrice;

        $price = null;
        $counts = [];
        foreach ( $this -> getRatings () as $rating )
        {
            if(!$rating->calculated)
                continue;
            $price = $price ? $price : $rating -> getBeerPrice ();
            foreach ( $rating -> getBeerPrice () as $beer => $p )
            {
                if ( array_key_exists ( $beer, $price ) && $p->count > 0 )
                {
                    $price [ $beer ] -> price = $price [ $beer ] -> price * $price [ $beer ] -> count;
                    $price [ $beer ] -> price += $p -> price;
                    $price [ $beer ] -> count += $p -> count;
                    $price [ $beer ] -> price = $price [ $beer ] -> price / $price [ $beer ] -> count;
                }
                else 
                {
                    $price [ $beer ] = $p;
                }

            }
        }

        if ($price) {
            foreach ( $price as $k => $p )
                $price [ $k ] -> price = $price [ $k ] -> count > 0 ? $price [ $k ] -> price : NULL;
        }


        return $this->beerPrice = $price;
    }

    /**
     * Set winePrice
     *
     * @param float $winePrice
     *
     * @return Pub
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
     * Set foodPrice
     *
     * @param float $foodPrice
     *
     * @return Pub
     */
    public function setFoodPrice($foodPrice)
    {
        $this->foodPrice = $foodPrice;

        return $this;
    }

    /**
     * Get foodPrice
     *
     * @return float
     */
    public function getFoodPrice()
    {
        return $this->foodPrice;
    }

    /**
     * Set beerMarkVoted
     *
     * @param integer $beerMarkVoted
     *
     * @return Pub
     */
    public function setBeerMarkVoted($beerMarkVoted)
    {
        $this->beerMarkVoted = $beerMarkVoted;

        return $this;
    }

    /**
     * Get beerMarkVoted
     *
     * @return integer
     */
    public function getBeerMarkVoted()
    {
        return $this->beerMarkVoted;
    }

    /**
     * Set beerPriceVoted
     *
     * @param integer $beerPriceVoted
     *
     * @return Pub
     */
    public function setBeerPriceVoted($beerPriceVoted)
    {
        $this->beerPriceVoted = $beerPriceVoted;

        return $this;
    }

    /**
     * Get beerPriceVoted
     *
     * @return integer
     */
    public function getBeerPriceVoted()
    {
        return $this->beerPriceVoted;
    }

    /**
     * Set wineMarkVoted
     *
     * @param integer $wineMarkVoted
     *
     * @return Pub
     */
    public function setWineMarkVoted($wineMarkVoted)
    {
        $this->wineMarkVoted = $wineMarkVoted;

        return $this;
    }

    /**
     * Get wineMarkVoted
     *
     * @return integer
     */
    public function getWineMarkVoted()
    {
        return $this->wineMarkVoted;
    }

    /**
     * Set winePriceVoted
     *
     * @param integer $winePriceVoted
     *
     * @return Pub
     */
    public function setWinePriceVoted($winePriceVoted)
    {
        $this->winePriceVoted = $winePriceVoted;

        return $this;
    }

    /**
     * Get winePriceVoted
     *
     * @return integer
     */
    public function getWinePriceVoted()
    {
        return $this->winePriceVoted;
    }

    /**
     * Set foodMarkVoted
     *
     * @param integer $foodMarkVoted
     *
     * @return Pub
     */
    public function setFoodMarkVoted($foodMarkVoted)
    {
        $this->foodMarkVoted = $foodMarkVoted;

        return $this;
    }

    /**
     * Get foodMarkVoted
     *
     * @return integer
     */
    public function getFoodMarkVoted()
    {
        return $this->foodMarkVoted;
    }

    /**
     * Set foodPriceVoted
     *
     * @param integer $foodPriceVoted
     *
     * @return Pub
     */
    public function setFoodPriceVoted($foodPriceVoted)
    {
        $this->foodPriceVoted = $foodPriceVoted;

        return $this;
    }

    /**
     * Get foodPriceVoted
     *
     * @return integer
     */
    public function getFoodPriceVoted()
    {
        return $this->foodPriceVoted;
    }

    /**
     * Set markVoted
     *
     * @param integer $markVoted
     *
     * @return Pub
     */
    public function setMarkVoted($markVoted)
    {
        $this->markVoted = $markVoted;

        return $this;
    }

    /**
     * Get markVoted
     *
     * @return integer
     */
    public function getMarkVoted()
    {
        return $this->markVoted;
    }

    /**
     * Set interierMark
     *
     * @param float $interierMark
     *
     * @return Pub
     */
    public function setInterierMark($interierMark)
    {
        $this->interierMark = $interierMark;

        return $this;
    }

    /**
     * Get interierMark
     *
     * @return float
     */
    public function getInterierMark()
    {
        return $this->interierMark;
    }

    /**
     * Set exterierMark
     *
     * @param float $exterierMark
     *
     * @return Pub
     */
    public function setExterierMark($exterierMark)
    {
        $this->exterierMark = $exterierMark;

        return $this;
    }

    /**
     * Get exterierMark
     *
     * @return float
     */
    public function getExterierMark()
    {
        return $this->exterierMark;
    }


    /**
     * return distance in metres
     * @return float
     */
    public function distance ( $lat, $lng = null )
    {
        if ( is_object ( $lat ) && method_exists ( $lat, "getLatitude" ) && method_exists ( $lat, "getLongitude" )  )
        {
            $point_latitude = $lat -> latitude;
            $point_longitude = $lat -> longitude;
        } else if ( is_array ( $lat ) && $lat === null ) {
            list($point_latitude, $point_longitude) = $lat;
        } else {
            $point_latitude = $lat;
            $point_longitude = $lng;
        }

        return acos(
            cos(deg2rad($this->latitude))*cos(deg2rad($this->longitude))*cos(deg2rad($point_latitude))*cos(deg2rad($point_longitude))
            + cos(deg2rad($this->latitude))*sin(deg2rad($this->longitude))*cos(deg2rad($point_latitude))*sin(deg2rad($point_longitude))
            + sin(deg2rad($this->latitude))*sin(deg2rad($point_latitude))
        ) * 6372.795 * 1000;        
    }

    /**
     * Set inserted
     *
     * @param \DateTime $inserted
     *
     * @return Pub
     */
    public function setInserted($inserted)
    {
        $this->inserted = $inserted;

        return $this;
    }

    /**
     * Get inserted
     *
     * @return \DateTime
     */
    public function getInserted()
    {
        return $this->inserted;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return Pub
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Pub
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set website
     *
     * @param string $website
     *
     * @return Pub
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Get website
     *
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }


    /**
     * Set wholeName
     *
     * @param string $wholeName
     *
     * @return Pub
     */
    public function setWholeName($wholeName)
    {
        return $this->setWhole_name($wholeName);
    }

    /**
     * Get wholeName
     *
     * @return string
     */
    public function getWholeName()
    {
        return $this->getWhole_name();
    }

    /**
     * Set wholeName
     *
     * @param string $wholeName
     *
     * @return Pub
     */
    public function setWhole_name($wholeName)
    {
        $this->whole_name = $wholeName;

        return $this;
    }

    /**
     * Get wholeName
     *
     * @return string
     */
    public function getWhole_name()
    {
        return $this->whole_name;
    }



    /**
     * Set user
     *
     * @param \Entity\User $user
     *
     * @return Pub
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
     * Add description
     *
     * @param \Entity\Description $description
     *
     * @return Pub
     */
    public function addDescription(\Entity\Description $description)
    {
        $description->pub = $this;
        $this->descriptions = new \Doctrine\Common\Collections\ArrayCollection(
            array_merge( array($description), $this->descriptions->toArray() ) 
        );
        return $this;
    }

    /**
     * Remove description
     *
     * @param \Entity\Description $description
     */
    public function removeDescription(\Entity\Description $description)
    {
        $this->descriptions->removeElement($description);
        $description->pub = null;
    }

    /**
     * Get descriptions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDescriptions()
    {
        return $this->descriptions;
    }

    /**
     * Get last possible description
     *
     * @return \Entity\Description
     */
    public function getLastDescription ()
    {
        return $this->descriptions->count() ? $this->descriptions[0] : NULL;
    }
}
