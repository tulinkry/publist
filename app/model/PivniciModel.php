<?php

namespace Model;



class PivniciModel
{

	const base_url = "http://www.pivnici.cz";
	const search_url = "http://www.pivnici.cz/hledat/piva/dle-data/?hledat[text]=%s";

	static public function by ( $by )
	{
		$url = sprintf ( self::search_url, $by );
		if ( ( $res = @file_get_contents ( $url ) ) === FALSE ) {
			throw new \Exception ( __CLASS__ . ":by(): failed" );
		}

		//$res = file_get_contents(__DIR__."/save.txt");

		$res = str_replace( "<", "&lt;", $res);
		$res = str_replace( ">", "&gt;", $res);

		$beg = "&lt;div\s+class=['\"]item['\"]&gt;";
		$end = "&lt;div\s+class=['\"]theEnd['\"]&gt;&lt;\/div&gt;\s*&lt;\/div&gt;";

		$beer = explode ("&lt;div class=\"group\"&gt;", $res );
		$beer = explode ("&lt;div class=\"lightBreak\"&gt;&lt;/div&gt;", $beer[1] );
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


			$b -> link = self::base_url . $beer_parts[1][0];
			$b -> name = $beer_parts[2][0];

			$out [] = $b;
		}

		return $out;
	}
}