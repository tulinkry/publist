<?php

namespace Model;

use Tulinkry;

interface IBeerLinkPlugin
{
	const OFFLINE = 0;
	const ONLINE = 1;

	public function by ( $criteria );
}

class PivniciPlugin implements IBeerLinkPlugin
{
	const SOURCE = "Pivníci.cz";
	const BASE_URL = "http://www.pivnici.cz";
	const SEARCH_URL = "http://www.pivnici.cz/hledat/piva/dle-data/?hledat[text]=%s";
	const MODE = self::ONLINE;

	public function by ( $by )
	{
		if(self::MODE === IBeerLinkPlugin::ONLINE){
			$url = sprintf ( self::SEARCH_URL, $by );
			if ( ( $res = @file_get_contents ( $url ) ) === FALSE ) {
				throw new \Exception ( __CLASS__ . ":by(): failed" );
			}
		} else {
			$res = file_get_contents(__DIR__."/save.txt");
		}


		$res = str_replace( "<", "&lt;", $res);
		$res = str_replace( ">", "&gt;", $res);

		$beg = "&lt;div\s+class=['\"]item['\"]&gt;";
		$end = "&lt;div\s+class=['\"]theEnd['\"]&gt;&lt;\/div&gt;\s*&lt;\/div&gt;";

		$beer = explode ("&lt;div class=\"group\"&gt;", $res );
		if(! isset($beer[1])){
			return [];
		}
		$beer = explode ("&lt;div class=\"lightBreak\"&gt;&lt;/div&gt;", $beer[1] );
		
		if(! isset($beer[0])){
			return [];
		}

		$beer = $beer [ 0 ];

		
		$out = [];
		$matches = explode ( "class=\"item\"&gt;", $beer );
		for ( $i = 1; $i < count($matches); $i ++ ) {
			$match = explode ( "&lt;div class=\"theEnd\"&gt;&lt;", $matches[$i] );
			$beer = $match[0];

			$b = new \StdClass;

			preg_match_all("/&lt;h2&gt;&lt;a href=\"(.*?)\"&gt;(.*?)&lt;\/a&gt;&lt;\/h2&gt;/", $beer, $beer_parts);
			$beer_parts2 = explode ( "Stupňovitost", $beer );
			if (count($beer_parts2)>=2){
				preg_match_all("/([0-9]+)(,&lt;span class=\"mini\"&gt;([0-9]+)&lt;\/span&gt;)?°/", $beer_parts2[1], $beer_parts2);
				
				$b -> degree = $beer_parts2[1][0] ? $beer_parts2[1][0] : 0;
				//$b -> degree += $beer_parts2[3][0] ? $beer_parts2[3][0] / 10 : 0;
				if(!$b->degree)
					unset($b->degree);
			}


			$b -> link = self::BASE_URL . $beer_parts[1][0];
			$b -> name = $beer_parts[2][0];
			$b -> source = self::SOURCE;

			$out [] = $b;
		}

		return $out;
	}

};


class BeerLinksModel
{

	private $plugins = array ();
	/** @var Tulinkry\Services\ParameterService */
	private $parametres;

	public function __construct ( Tulinkry\Services\ParameterService $parametres )
	{
		$this -> parametres = $parametres;


		if(isset($parametres->params['beer']) && isset($parametres->params['beer']['plugins'])){
			$this->plugins = array ();
			foreach ($parametres->params['beer']['plugins'] as $pluginClass)
				$className = $pluginClass[0] === "\\" ? $pluginClass : "\\" . $pluginClass;
				$this->plugins[] = new $className;
		} else {
			$this->plugins = array (
				new PivniciPlugin,
			);
		}
	}

	public function by ( $by )
	{
		$out = [];
		foreach ( $this->plugins as $plugin ) {
			try{
				$out2 = array_merge( $out, $plugin->by($by) );
				$out = $out2;
			} catch ( \Exception $e ) {}

		}
		return $out;
	}
}


