<?php

namespace Model;

use Entity;

class GalleryModel
{

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

	
}