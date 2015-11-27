<?php

namespace AdminModule\Presenters;

use Nette,
	Model,
	FrontModule\Controls,
	Nette\Application\UI\Multiplier,
	AdminModule\Forms,
	Oli,
	Tulinkry;


/**
 */
class SliderPresenter extends BasePresenter
{
	/** @var array */
	private $images = null;

	private function getImages ()
	{
		if ( $this -> images )
			return $this -> images;

		$files = [];
		$dirname = WWW_DIR . "/" . $this -> parameters -> params [ "sliderSrc" ] . "/" . $this -> parameters -> params [ "slider" ] [ "xs" ];
		if ( $handle = opendir( $dirname ) ) 
		{
		    while ( false !== ( $entry = readdir($handle) ) ) 
		    {
		        if ($entry != "." && $entry != "..") 
		        {
		            $std = new \StdClass;
		            $std -> path = $this -> parameters -> params [ "sliderSrc" ] . "/" . 
		            			   $this -> parameters -> params [ "slider" ] [ "md" ] . "/" . $entry;
		            $std -> thumbnail = $this -> parameters -> params [ "sliderSrc" ] . "/" . 
		            			   		$this -> parameters -> params [ "slider" ] [ "xs" ] . "/" . $entry;
		            $std -> name = $entry;

		            $std -> lastUpdated = filemtime ( $this -> parameters -> params [ "sliderSrc" ] . "/" . 
		            			   $this -> parameters -> params [ "slider" ] [ "md" ] . "/" . $entry );
		            
		            //$std -> thumbnail = base64_encode ( (string) $img );
		        	$files [ $entry ] = $std;
		        }
		    }
		    closedir( $handle );
		}
		return $this -> images = $files;		
	}

	private function limitImages ( $limit, $offset )
	{
		$it = -1;
		$images = [];
		foreach ($this->getImages() as $key => $entity) 
		{
			$it ++;
			if ( $it < $offset || $it >= ( $offset + $limit ) )
				continue;
			$images [ $key ] = $entity;
		}
		return $images;
	}

	private function countImages ()
	{
		return count ( $this -> getImages () );
	}


	public function renderDefault()
	{
		$paginator = $this [ "paginator" ] -> getPaginator ();
		$paginator -> itemCount = $this -> countImages ();
		$this -> template -> images = $this -> limitImages( $paginator -> itemsPerPage, $paginator -> offset );
	    if ( $this->isAjax() )
	        $this->invalidateControl('pics');
	}


	public function handleRotate ( $file_name )
	{
		$that = $this;
		$save = function ( $filename ) use ( $that ) {

			foreach ( [ "md", "lg", "xs", "sm" ] as $size )
			{
				$dirname = WWW_DIR . "/" . $that -> parameters -> params [ "sliderSrc" ] ."/".$that -> parameters -> params [ "slider" ][$size];

				if ( ! file_exists ( $dirname ) )
					return false;

				$img = Nette\Utils\Image::fromFile ( $dirname . "/" . $filename );
				$img -> rotate ( -90, 0 );

				if ( ! $img -> save ( $dirname . "/" . $filename ) )
					return false;

			}
			
			return true;
		};

		if ( ! $save ( $file_name ) )
			$this -> presenter -> flashMessage ( "Neexistující obrázek.", "error" );

		$this -> invalidateControl ( "pics" );

		if ( ! $this -> isAjax () )
			$this -> redirect ( "this" );
	}

	public function handleDelete ( $file_name )
	{

		$that = $this;
		$delete = function ( $filename ) use ( $that ) {
			
			$ret = true;
			foreach ( [ "md", "lg", "xs", "sm" ] as $size )
			{
				$dirname = WWW_DIR . "/" . $that -> parameters -> params [ "sliderSrc" ]."/".$that -> parameters -> params [ "slider" ][$size];
				
				if ( ! file_exists( $dirname . "/" . $filename ) )
				{
					$ret = false;
					continue;
				}

				if ( ! @unlink ( $dirname . "/" . $filename ) )
					$ret = false;
			}
			return $ret;
		};

		if ( ! $delete ( $file_name ) )
			$this -> presenter -> flashMessage ( "Neexistující obrázek.", "error" );
		
		$this -> invalidateControl ( "pics" );
		$this -> invalidateControl ( "paginator" );

		if ( ! $this -> isAjax () )
			$this -> redirect ( "this" );
	}	

	protected function createComponentSliderForm ( $name )
	{
		$form = new Forms\SliderForm ( $this -> parameters -> params );
		return $this [ $name ] = $form;
	}

}