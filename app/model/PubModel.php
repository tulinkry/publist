<?php

namespace Model;

use Tulinkry\Model\Doctrine\BaseModel;
use Entity;
use Nette\Utils\Image;
use Tulinkry\Utils\Strings;

class PubModel extends BaseModel
{
	const BEER_WEIGHT = 1;
	const WINE_WEIGHT = 1;
	const FOOD_WEIGHT = 1;
	const TOALETS_WEIGHT = 1;
	const INTERIER_WEIGHT = 1;
	const EXTERIER_WEIGHT = 1;
	const SERVICE_WEIGHT = 1;
	const OVERALL_WEIGHT = 1;

	const BEER_PRICE_WEIGHT = 1;
	const WINE_PRICE_WEIGHT = 1;
	const FOOD_PRICE_WEIGHT = 1;

	const BEER_MIN = 1;
	const BEER_PRICE_MIN = 10;
	const WINE_MIN = 1;
	const WINE_PRICE_MIN = 10;
	const FOOD_MIN = 1;
	const FOOD_PRICE_MIN = 1;
	const TOALETS_MIN = 1;
	const INTERIER_MIN = 1;
	const EXTERIER_MIN = 1;
	const SERVICE_MIN = 1;
	const OVERALL_MIN = 1;

	const BEER_MAX = 10;
	const BEER_PRICE_MAX = 100;
	const WINE_MAX = 10;
	const WINE_PRICE_MAX = 500;
	const FOOD_MAX = 10;
	const FOOD_PRICE_MAX = 10;
	const TOALETS_MAX = 10;
	const INTERIER_MAX = 10;
	const EXTERIER_MAX = 10;
	const SERVICE_MAX = 10;
	const OVERALL_MAX = 10;

	const GALLERY_PATH = "images/pubs";


	/**
	 * @return array of Nette\Utils\Image
	 */
	public static function getImages ( Entity\Pub $pub = NULL )
	{
		if ( ! $pub )
			return array ();

		$dirname = WWW_DIR . "/" . self::GALLERY_PATH . "/" . $pub -> id;
		$thumbnailDir = $dirname . "/thumbnails";
		if ( ! is_dir ( $dirname ) )
			return array ();

		$files = [];
		if ( $handle = opendir( $dirname ) ) 
		{
		    while ( false !== ( $entry = readdir($handle) ) ) 
		    {
		        if ($entry != "." && $entry != ".." && is_file(self::GALLERY_PATH . "/" . $pub -> id . "/" . $entry)) 
		        {
		        	$thb =  self::GALLERY_PATH . "/" . $pub -> id . "/thumbnails/" . $entry;
		        	$pic = self::GALLERY_PATH . "/" . $pub -> id . "/" . $entry;
		            $files [ $entry ] = new \StdClass;
		            $files [ $entry ] -> path = $pic;
		            $files [ $entry ] -> thumbnail = $thb;
		            $files [ $entry ] -> lastUpdated = filemtime ( $dirname . "/" . $entry );
		        }
		    }
		    closedir( $handle );
		}
		
		return $files;
	}

	/**
	 * saves one image into filesystem
	 */
	public static function saveImage ( Image $img, Entity\Pub $pub = NULL )
	{
		if ( ! $pub || ! $img )
			return false;

		$dirname = WWW_DIR . "/" . self::GALLERY_PATH . "/" . $pub -> id;
		$thumbnailDir = $dirname . "/thumbnails";
		if ( ! file_exists( $dirname ) )
			mkdir ( $dirname, 0777, true );

		if ( ! file_exists( $thumbnailDir ) )
			mkdir ( $thumbnailDir, 0777, true );
		
		do {
			$name = Strings::random(10) . ".jpg";
		} while (file_exists($dirname . "/" . $name));

		$return_value = $img -> save ( $dirname . "/" . $name, 80, Image::JPEG );

		return $return_value && $img -> resize ( '300px', null ) -> save ( $thumbnailDir . "/" . $name, 80, Image::JPEG );
	}

	/**
	 * deletes image from filesystem
	 * @return boolean for success or failure
	 */
	public static function deleteImage ( $filename, Entity\Pub $pub = NULL )
	{
		if ( ! $pub )
			return false;

		$dirname = WWW_DIR . "/" . self::GALLERY_PATH . "/" . $pub -> id;
		$thumbnailDir = $dirname . "/thumbnails";
		
		$ret = false;

		if ( file_exists( $dirname . "/" . $filename ) )
			$ret = @unlink ( $dirname . "/" . $filename );

		if ( file_exists( $thumbnailDir . "/" . $filename ) )
			$ret = $ret && @unlink ( $thumbnailDir . "/" . $filename );

		return $ret;
	}

	/**
	 * deletes all images associated to particular $pub
	 * @return boolean
	 */
	public static function deleteImages ( Entity\Pub $pub = NULL )
	{
		$images = self::getImages ( $pub );
		$ret = true;
		foreach ( $images as $name => $image )
		{
			$ret2 = self::deleteImage ( $name, $pub );
			$ret = $ret && $ret2;
		}
		return $ret;
	}


	/**
	 * rotates image and saves it back to the same file
	 * @return boolean
	 */
	public static function rotateImage ( $filename, Entity\Pub $pub = NULL, $angle = 90 )
	{
		if ( ! $pub )
			return false;

		$dirname = WWW_DIR . "/" . self::GALLERY_PATH . "/" . $pub -> id;
		$thumbnailDir = $dirname . "/thumbnails";

		$img = Image::fromFile ( $dirname . "/" . $filename );
		$img -> rotate ( -$angle, 0 );
		$ret = $img -> save ( $dirname . "/" . $filename );

		$img = Image::fromFile ( $thumbnailDir . "/" . $filename );
		$img -> rotate ( -$angle, 0 );
		$ret = $ret && $img -> save ( $thumbnailDir . "/" . $filename );
		
		return $ret;
	}



	public static function rating ( Entity\Pub $pub )
	{		
		$cnt = count ( $pub -> getRatings () );
		if ( $cnt <= 0 )
			return 0;

		$sum = 0;
		foreach ( $pub -> getRatings () as $rating )
			$sum += self::singleRating ( $rating );

		return $sum / $cnt;
	}

	public static function singleRating ( Entity\Rating $rating )
	{
		$ratingLoc = 0;
		$ratingMax = 0;

		if ( ( $ret = self::singleBeerRating ( $rating ) ) !== NULL )
		{
			$ratingLoc += $ret;
			$ratingMax += self::BEER_MAX * self::BEER_WEIGHT;
		}
		if ( ( $ret = self::singleWineRating ( $rating ) ) !== NULL )
		{
			$ratingLoc += $ret;
			$ratingMax += self::WINE_MAX * self::WINE_WEIGHT;
		}
		if ( ( $ret = self::singleFoodRating ( $rating ) ) !== NULL )
		{
			$ratingLoc += $ret;
			$ratingMax += self::FOOD_MAX * self::FOOD_WEIGHT;
		}
		if ( ( $ret = self::singleToaletsRating ( $rating ) ) !== NULL )
		{
			$ratingLoc += $ret;
			$ratingMax += self::TOALETS_MAX * self::TOALETS_WEIGHT;
		}
		if ( ( $ret = self::singleInterierRating ( $rating ) ) !== NULL )
		{
			$ratingLoc += $ret;
			$ratingMax += self::INTERIER_MAX * self::INTERIER_WEIGHT;
		}
		if ( ( $ret = self::singleExterierRating ( $rating ) ) !== NULL )
		{
			$ratingLoc += $ret;
			$ratingMax += self::EXTERIER_MAX * self::EXTERIER_WEIGHT;
		}
		if ( ( $ret = self::singleServiceRating ( $rating ) ) !== NULL )
		{
			$ratingLoc += $ret;
			$ratingMax += self::SERVICE_MAX * self::SERVICE_WEIGHT;
		}
		if ( ( $ret = self::singleOverallRating ( $rating ) ) !== NULL )
		{
			$ratingLoc += $ret;
			$ratingMax += self::OVERALL_MAX * self::OVERALL_WEIGHT;
		}
		if ( ( $ret = self::singleFoodPriceRating ( $rating ) ) !== NULL )
		{
			$ratingLoc += $ret;
			$ratingMax += self::FOOD_PRICE_MAX * self::FOOD_PRICE_WEIGHT;
		}

		return $ratingLoc / $ratingMax;
	}
	
	public static function commodityRating ( $commodity, Entity\Pub $pub )
	{
		if ( $commodity == "" || $pub == null || count ( $pub -> getRatings () ) <= 0 )
			return 0;
		$sum = 0;
		$cnt = 0;
		$methodName = 'self::' . $commodity . 'Rating';
		foreach ( $pub -> getRatings () as $rating )
		{
			if ( ( $res = forward_static_call( $methodName, $rating ) ) === NULL )
				continue;
			$sum += $res;
			$cnt ++;
		}
		if ( ! $cnt )
			return NULL;

		return $sum / $cnt;
	}


	public static function beerRating ( Entity\Pub $pub )
	{
		if ( ( $ret = self::commodityRating ( "singleBeer", $pub ) ) === NULL )
			return NULL;
		return  $ret / ( self::BEER_MAX * self::BEER_WEIGHT );
	}

	public static function wineRating ( Entity\Pub $pub )
	{
		if ( ( $ret = self::commodityRating ( "singleWine", $pub ) ) === NULL )
			return NULL;
		return $ret / ( self::WINE_MAX * self::WINE_WEIGHT );
	}

	public static function foodRating ( Entity\Pub $pub )
	{
		if ( ( $ret = self::commodityRating ( "singleFood", $pub ) ) === NULL )
			return NULL;
		return $ret / ( self::FOOD_MAX * self::FOOD_WEIGHT );
	}
	public static function toaletsRating ( Entity\Pub $pub )
	{
		if ( ( $ret = self::commodityRating ( "singleToalets", $pub ) ) === NULL )
			return NULL;
		return $ret / ( self::TOALETS_MAX * self::TOALETS_WEIGHT );
	}

	public static function interierRating ( Entity\Pub $pub )
	{
		if ( ( $ret = self::commodityRating ( "singleInterier", $pub ) ) === NULL )
			return NULL;
		return $ret / ( self::INTERIER_MAX * self::INTERIER_WEIGHT );
	}

	public static function exterierRating ( Entity\Pub $pub )
	{
		if ( ( $ret = self::commodityRating ( "singleExterier", $pub ) ) === NULL )
			return NULL;
		return $ret / ( self::EXTERIER_MAX * self::EXTERIER_WEIGHT );
	}


	public static function serviceRating ( Entity\Pub $pub )
	{
		if ( ( $ret = self::commodityRating ( "singleService", $pub ) ) === NULL )
			return NULL;
		return $ret / ( self::SERVICE_MAX * self::SERVICE_WEIGHT );
	}

	public static function overallRating ( Entity\Pub $pub )
	{
		if ( ( $ret = self::commodityRating ( "singleOverall", $pub ) ) === NULL )
			return NULL;
		return $ret / ( self::OVERALL_MAX * self::OVERALL_WEIGHT );
	}

	public static function foodPriceRating ( Entity\Pub $pub )
	{
		if ( ( $ret = self::commodityRating ( "singleFoodPrice", $pub ) ) === NULL )
			return NULL;
		return $ret / ( self::FOOD_PRICE_MAX * self::FOOD_PRICE_WEIGHT );
	}


	public static function beerPriceRating ( Entity\Pub $pub )
	{
		return NULL;
		return self::commodityRating ( "singleBeerPrice", $pub );
	}

	public static function winePriceRating ( Entity\Pub $pub )
	{
		return self::commodityRating ( "singleWinePrice", $pub );
	}


	public static function singleBeerRating ( Entity\Rating $rating )
	{
		if ( count ( $rating -> beers ) <= 0 )
			return NULL;
		$sum = 0;
		$cnt = 0;
		foreach ( $rating -> beers as $beer_rating_ref )
		{
			if( $beer_rating_ref -> beerCriteria === NULL )
				continue;
			$sum += $beer_rating_ref -> beerCriteria;
			$cnt ++;
		}

		if ( ! $cnt )
			return NULL;

		return ( $sum / $cnt ) * self::BEER_WEIGHT;
		

		if ( $rating -> beerCriteria === NULL )
			return NULL;
		return ( $rating -> beerCriteria * self::BEER_WEIGHT );
	}

	public static function singleWineRating ( Entity\Rating $rating )
	{
		if ( $rating -> wineCriteria === NULL )
			return NULL;
		return ( $rating -> wineCriteria * self::WINE_WEIGHT );
	}

	public static function singleFoodRating ( Entity\Rating $rating )
	{
		if ( $rating -> foodCriteria === NULL )
			return NULL;
		return ( $rating -> foodCriteria * self::FOOD_WEIGHT );
	}

	public static function singleToaletsRating ( Entity\Rating $rating )
	{
		if ( $rating -> toaletsCriteria === NULL )
			return NULL;
		return ( $rating -> toaletsCriteria * self::TOALETS_WEIGHT );
	}

	public static function singleInterierRating ( Entity\Rating $rating )
	{
		if ( $rating -> interierCriteria === NULL )
			return NULL;
		return ( $rating -> interierCriteria * self::INTERIER_WEIGHT );
	}

	public static function singleExterierRating ( Entity\Rating $rating )
	{
		if ( $rating -> exterierCriteria === NULL )
			return NULL;
		return ( $rating -> exterierCriteria * self::EXTERIER_WEIGHT );
	}


	public static function singleServiceRating ( Entity\Rating $rating )
	{
		if ( $rating -> serviceCriteria === NULL )
			return NULL;
		return ( $rating -> serviceCriteria * self::SERVICE_WEIGHT );
	}

	public static function singleOverallRating ( Entity\Rating $rating )
	{
		if ( $rating -> overallCriteria === NULL )
			return NULL;
		return ( $rating -> overallCriteria * self::OVERALL_WEIGHT );
	}

	public static function singleFoodPriceRating ( Entity\Rating $rating )
	{
		if ( $rating -> foodPriceCriteria === NULL )
			return NULL;
		return ( $rating -> foodPriceCriteria * self::FOOD_PRICE_WEIGHT );
	}

	public static function singleBeerPriceRating ( Entity\Rating $rating )
	{
		if ( $rating -> beerPrice === NULL )
			return NULL;
		return $rating -> beerPrice;
	}

	public static function singleWinePriceRating ( Entity\Rating $rating )
	{
		if ( $rating -> winePrice === NULL )
			return NULL;
		return $rating -> winePrice;
	}

	public function sort ( $limit, $offset, $order, $by, $hidden = false )
	{
		static $allowed_order = array (
			"mark",
		    "name",
		    "location",
		    "beerMark",
  			"beerPrice",
    		"wineMark",
  			"winePrice",
    		"foodMark",
    		"toaletsMark",
    		"interierMark",
    		"exterierMark",
   			"serviceMark",
   			"overallMark",
   			"foodPrice",
   			"updated",
   			"inserted"
			);

		static $allowed_by = array ( "ASC", "DESC" );

		if ( ! in_array ( $order, $allowed_order ) )
			throw new \InvalidArgumentException ( "sort(): order is not a valid column" );

		if ( ! in_array ( $by, $allowed_by ) )
			throw new \InvalidArgumentException ( "sort(): by is not a valid option for sorting [ASC, DESC]" );


		$ascending = $by == "ASC" ? true : false;

		if ( $ascending )
		{
			$by = $by == "ASC" ? "DESC" : "ASC";
			$cmd = "SELECT p, -p.$order AS HIDDEN inversed FROM Entity\Pub p";
			$cmd .= " WHERE p.hidden = ?1";
			$cmd .= " ORDER BY inversed $by";
			$query = $this -> em -> createQuery (
				$cmd
			);
		} 
		else 
		{
			$cmd = "SELECT p FROM Entity\Pub p";
			$cmd .= " WHERE p.hidden = ?1";
			$cmd .= " ORDER BY p.$order $by";
		}

		$query = $this -> em -> createQuery (
			$cmd
		);

		$query -> setParameter ( 1, $hidden );

		$query -> setFirstResult ( $offset );
		$query -> setMaxResults ( $limit );

		return $query -> getResult ();
	}


	public function closest ( $lat, $lng, $limit, $offset, $ascending = true )
	{
		$cmd = 			"SELECT p, (ACOS( 
						    COS ( ?1 * PI() / 180 ) * COS ( ?2 * PI() / 180 ) * COS ( p.latitude * PI() / 180 ) * COS ( p.longitude * PI() / 180 ) +
						    COS ( ?1 * PI() / 180 ) * SIN ( ?2 * PI() / 180 ) * COS ( p.latitude * PI() / 180 ) * SIN ( p.longitude * PI() / 180 ) +
						    SIN ( ?1 * PI() / 180 ) * SIN ( p.latitude * PI() / 180 ) ) * 6372.795 * 1000) AS HIDDEN distance		
 			FROM Entity\Pub p 
 			ORDER BY distance ";
 		$cmd .= $ascending ? "ASC" : "DESC";

		$query = $this -> em -> createQuery (
			$cmd
		);

		$query -> setParameter ( 1, $lat );
		$query -> setParameter ( 2, $lng );

		$query -> setFirstResult ( $offset );
		$query -> setMaxResults ( $limit );

		return $query -> getResult ();

	}

	public function time ( $by, $time )
	{
		$cmd = "SELECT p FROM Entity\Pub p";
		$cmd .= " JOIN p.ratings r";
		$cmd .= " WHERE ";
		foreach ( $by as $key => $val )
			$cmd .= "p.$key = :$key AND ";
		$cmd .= "p.inserted >= ?1";


		$query = $this -> em -> createQuery (
			$cmd
		);

		$query -> setParameter ( 1, $time );

		foreach ( $by as $key => $val )
			$query->setParameter( $key, $val );


		return $query -> getResult ();

	}	

	public function lastRated ( $limit, $offset, $by )
	{
		$cmd = "SELECT p, MAX(r.date) AS HIDDEN max_date, r FROM Entity\Pub p";
		$cmd .= " JOIN p.ratings r";
		$cmd .= " WHERE ";
		foreach ( $by as $key => $val )
			$cmd .= "p.$key = " . (is_bool($val) ? (int) $val : '$var') . " AND ";
		$cmd .= "r.calculated = 1";
		$cmd .= " GROUP BY p.id";
		$cmd .= " ORDER BY max_date DESC";

		/*
		SELECT p.name, max(r.date) FROM pubs p join ratings r on r.pub_id = p.pub_id
		GROUP BY p.pub_id
		ORDER BY max(r.date) DESC
		*/
		$query = $this -> em -> createQuery (
			$cmd
		);

		$query -> setFirstResult ( $offset );
		$query -> setMaxResults ( $limit );


		return $query -> getResult ();

	}	

	public function fetchLimit ( $limit, $offset, $by = array (), $order = array () )
	{
		return $this->limit( $limit, $offset, $by, $order );
		
		/**
		 * this is not working
		 * returns less results than it should;
		 */

		$cmd = "SELECT p, r as HIDDEN FROM Entity\Pub p JOIN p.ratings r ";

		if(count($by)){
			$i = 1;
			$cmd .= "WHERE "; 
			foreach ( $by as $key => $val ) {
				$cmd .= "p.$key = :$key";
				if($i !== count($by))
					$cmd .= " AND ";
				$i ++;
			}
		}
		
		if(count($order)) {
			$cmd .= " ORDER BY ";
			foreach ( $order as $key => $val )
				$cmd .= "p.$key $val, ";
			if($cmd[strlen($cmd)-2] == ',') {
				$cmd = substr( $cmd, 0, strlen($cmd) - 2 );
			}
		}
	
		$query = $this -> em -> createQuery ( $cmd );

		if(count($by)){
			foreach ( $by as $key => $val ){
				$query -> setParameter ( "$key", $val );	
			}
		}

		$query -> setFirstResult ( $offset );
		$query -> setMaxResults ( $limit );

		return $query->getResult();
	}

	public function search ( $by, $fields, $limit = null, $offset = null )
	{
		$cmd = "SELECT p FROM Entity\Pub p";
		$cmd .= " WHERE ";
		$i = 1;
		foreach ( $fields as $key ) {
			$cmd .= "p.$key LIKE ?".$i." AND ";
			$i ++;
		}
		$cmd .= " p.hidden = 0";
		
		if(count($fields)) {
			$cmd .= " ORDER BY ";
			foreach ( $fields as $key )
				$cmd .= "p.$key ASC, ";
			if($cmd[strlen($cmd)-2] == ',') {
				$cmd = substr( $cmd, 0, strlen($cmd) - 2 );
			}
		}

		$query = $this -> em -> createQuery (
			$cmd
		);
		
		$i = 1;
		foreach ( $fields as $key ){
			$query -> setParameter ( $i, '%'.$by.'%' );	
			$i ++;
		}

		if($offset)
			$query -> setFirstResult ( $offset );
		if($limit)
			$query -> setMaxResults ( $limit );


		return $query -> getResult ();
	}

	public function recalculate ( Entity\Pub $pub )
	{
/*
		
		$query = $this -> em -> createQuery (
			"SELECT "
			   . "AVG(r.wine_criteria),"
			   . "AVG(r.wine_price),"
			   . "AVG(r.food_criteria),"
			   . "AVG(r.food_price_criteria),"
			   . "AVG(r.toalets_criteria),"
			   . "AVG(r.service_criteria),"
			   . "AVG(r.overall_criteria),"
			   . "AVG(r.interier_criteria),"
			   . "AVG(r.exterier_criteria),"
			   . "AVG(r.garden)"
		. " FROM pubs p"
		. " JOIN ratings r ON r . pub_id = p . pub_id"
		. " WHERE p = ?1"
		);

		$query -> setParameter ( 1, $pub );
		foreach ( $query -> getResult () as $row )
		{
	        $this -> wineMark = $row [ "wine_criteria" ];
	        $this -> winePrice = $row [ "wine_price" ];
	        $this -> foodMark = $row [ "food_criteria" ];
	        $this -> foodPrice = $row [ "food_price_criteria" ];
	        $this -> toaletsMark = $row [ "toalets_criteria" ];
	        $this -> interierMark = $row [ "interier_criteria" ];
	        $this -> exterierMark = $row [ "exterier_criteria" ];
	        $this -> serviceMark = $row [ "service_criteria" ];
	        $this -> overallMark = $row [ "overall_criteria" ];
	        $this -> mark = self::rating ( $this );			
		}

        //$this -> beerMark = self::beerRating ( $this );
        //$this -> beerPrice = self::beerPriceRating ( $this );
        $this -> wineMark = self::wineRating ( $this );
        $this -> winePrice = self::winePriceRating ( $this );
        $this -> foodMark = self::foodRating ( $this );
        $this -> foodPrice = self::foodPriceRating ( $this );
        $this -> toaletsMark = self::toaletsRating ( $this );
        $this -> interierMark = self::interierRating ( $this );
        $this -> exterierMark = self::exterierRating ( $this );
        $this -> serviceMark = self::serviceRating ( $this );
        $this -> overallMark = self::overallRating ( $this );
        $this -> mark = self::rating ( $this );

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
        }*/
	}
}
