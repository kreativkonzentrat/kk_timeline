<?php

/**
 * Class KkTimelineHelper
 */
class KkTimelineHelper
{
	var $oPlugin;
	var $customerGroup;
	var $languageId;
	var $maxChars;

	/**
	 * @param $obj
	 */
	public function __construct ($obj) {
		$this->oPlugin    = $obj;
		$this->maxChars   = $obj->oPluginEinstellungAssoc_arr['kk_timeline_max_chars'];
		$this->languageId = $_SESSION['kSprachISO'];
	}

	/**
	 * @return bool
	 */
	public function assignPhpQuery () {
		$globalExist = false; //if there is no selector in our DOM, we don't need to init
		$selector    = $this->oPlugin->oPluginEinstellungAssoc_arr['kk_timeline_selector'];
		$function    = $this->oPlugin->oPluginEinstellungAssoc_arr['kk_timeline_php_query_function'];
		$size        = sizeof(pq($selector));
		$existsInDom = ($size > 0) ? true : false;
		if ($existsInDom == true && $selector && $selector != '') {
			$globalExist = true;
			$mainData    = '<div id="kk-timeline"></div>';
			pq($selector)->$function($mainData);
		}
		return $globalExist;
	}

	/**
	 * @param int $num - number of news entries
	 * @return array
	 */
	private function getNews ($num = 10) {
		$cSQL = '';
		if ($num > 0 && is_numeric($num)) {
			$cSQL = " LIMIT " . $num;
		}
		$oNews_arr = $GLOBALS['DB']->executeQuery(
			"SELECT tnews.kNews, tnews.kSprache, tnews.cKundengruppe, tnews.cBetreff, tnews.cText, tnews.cVorschauText, tnews.nAktiv, tnews.dGueltigVon, tseo.cSeo,
															count(tnewskommentar.kNewsKommentar) as nNewsKommentarAnzahl, DATE_FORMAT(tnews.dGueltigVon, '%d.%m.%Y  %H:%i') as dErstellt_de, DATE_FORMAT(tnews.dGueltigVon, '%d.%m.%Y  %H:%i') as dGueltigVon_de
															FROM tnews
															LEFT JOIN tnewskommentar ON tnewskommentar.kNews = tnews.kNews
																AND tnewskommentar.nAktiv = 1
															LEFT JOIN tseo ON tseo.cKey = 'kNews'
																AND tseo.kKey = tnews.kNews
																AND tseo.kSprache = " . $_SESSION['kSprache'] . "
												WHERE tnews.kSprache=" . $_SESSION['kSprache'] . "
													AND tnews.nAktiv=1
													AND tnews.dGueltigVon <= now()
													AND (tnews.cKundengruppe LIKE '%;-1;%' OR tnews.cKundengruppe LIKE '%;" . $_SESSION['Kundengruppe']->kKundengruppe . ";%')
												GROUP BY tnews.kNews
												ORDER BY tnews.dGueltigVon DESC" . $cSQL, 2
		);

		$news = array();
		if (count($oNews_arr) > 0) {
			if (is_array($oNews_arr) && count($oNews_arr) > 0) {
				foreach ($oNews_arr as $i => $oNews) {
					$news[$i]            = new stdClass();
					$news[$i]->text      = ($oNews_arr[$i]->cVorschauText != '' && $oNews_arr[$i]->cVorschauText != null) ? utf8_encode($this->shortenText($oNews_arr[$i]->cVorschauText, $this->maxChars)) : utf8_encode($this->shortenText(parseNewsText($oNews_arr[$i]->cText), $this->maxChars) . "<a href='" . $oNews_arr[$i]->cURL . "'>" . $GLOBALS['oSprache']->gibWert('moreLink', 'news') . "</a>");
					$news[$i]->startDate = utf8_encode(substr(str_replace('-', ',', $oNews_arr[$i]->dGueltigVon), 0, 10));
					$news[$i]->headline  = utf8_encode($oNews_arr[$i]->cBetreff);
					$news[$i]->asset     = array(
						'media'   => $oNews_arr[$i]->cThumbUrl,
						'credit'  => $this->oPlugin->oPluginEinstellungAssoc_arr['kk_timeline_copyright'],
						'caption' => ''
					);
				}
			}
		}

		return $news;
	}

	/**
	 * create json output for timeline
	 *
	 * @return int|mixed|string
	 */
	public function generateJson () {
		$limitNews       = $this->oPlugin->oPluginEinstellungAssoc_arr['kk_timeline_limit_news'];
		$limitProducts   = $this->oPlugin->oPluginEinstellungAssoc_arr['kk_timeline_limit_products'];
		$initialText     = $this->oPlugin->oPluginSprachvariableAssoc_arr['kk_timeline_init_text'];
		$initialHeadline = $this->oPlugin->oPluginSprachvariableAssoc_arr['kk_timeline_init_headline'];
		$initialImage    = $this->oPlugin->oPluginSprachvariableAssoc_arr['kk_timeline_init_image'];
		$copyright       = $this->oPlugin->oPluginEinstellungAssoc_arr['kk_timeline_copyright'];

		if ($this->oPlugin->oPluginEinstellungAssoc_arr['kk_timeline_show_news'] == 'Y') {
			$news = $this->getNews($limitNews);
		} else {
			$news = array();
		}
		if ($this->oPlugin->oPluginEinstellungAssoc_arr['kk_timeline_show_products'] == 'Y') {
			$products = $this->getProducts($limitProducts);
		} else {
			$products = array();
		}
		$entries = array();
		foreach ($news as $newsEntry) {
			$entries[] = $newsEntry;
		}
		foreach ($products as $product) {
			$entries[] = $product;
		}
		$res = array(
			'timeline' => array(
				'headline'  => $initialHeadline,
				'type'      => 'default',
				'text'      => $initialText,
				'startDate' => date('Y,m,d'),
				'asset'     => array(
					'media'   => $initialImage,
					'credit'  => $copyright,
					'caption' => ''
				),
				'date'      => $entries
			)
		);
		return json_encode($res);
	}

	/**
	 * generate object of all products
	 *
	 * @param int $num - the number of products
	 * @return mixed
	 */
	private function getProducts ($num = 10) {
		$cSQL = '';
		if ($num > 0 && is_numeric($num)) {
			$cSQL = " LIMIT " . $num;
		}
		if ($this->languageId == 1) {
			$query    = "SELECT cSeo as url, cName as headline, cKurzBeschreibung as text, dErstellt as startDate, tartikelpict.cPfad
						FROM (tartikel LEFT JOIN tartikelpict ON tartikel.kArtikel = tartikelpict.kArtikel)
						WHERE (tartikelpict.nNr = 1 OR tartikelpict.nNr IS NULL) AND tartikel.kArtikel
							NOT IN (SELECT kArtikel FROM tartikelsichtbarkeit WHERE kKundengruppe = '" . $this->customerGroup . "')
							GROUP BY tartikel.kArtikel
							ORDER BY dErstellt DESC";
			$products = $GLOBALS['DB']->executeQuery($query . $cSQL, 2);
			if (count($products) > 0) {
				foreach ($products as &$product) {
					$product->headline  = utf8_encode($product->headline);
					$product->startDate = str_replace('-', ',', $product->startDate);
					$product->text      = utf8_encode($product->text . "<a href='" . $product->url . "'>" . $GLOBALS['oSprache']->gibWert('moreLink', 'news') . "</a>");
					$product->asset     = array(
						'media'   => ($product->cPfad != '' && $product->cPfad != null) ? 'bilder/produkte/klein/' . $product->cPfad : '',
						'credit'  => $this->oPlugin->oPluginEinstellungAssoc_arr['kk_timeline_copyright'],
						'caption' => ''
					);
					unset($product->url);
					unset($product->cPfad);
				}
			}
		} else {
			$query    = "SELECT tartikel.cSeo as url, tartikelsprache.cSeo as url2, tartikel.cName as headline, tartikelsprache.cName as headline2, tartikel.cKurzBeschreibung as text, tartikelsprache.cKurzBeschreibung as text2, dErstellt as startDate, tartikelpict.cPfad
						FROM ((tartikel LEFT JOIN tartikelpict ON tartikel.kArtikel = tartikelpict.kArtikel) LEFT JOIN tartikelsprache ON tartikel.kArtikel = tartikelsprache.kArtikel AND tartikelsprache.kSprache = '" . $this->languageId . "')
						WHERE (tartikelpict.nNr = 1 OR tartikelpict.nNr IS NULL) AND tartikel.kArtikel
							NOT IN (SELECT kArtikel FROM tartikelsichtbarkeit WHERE kKundengruppe = '" . $this->customerGroup . "')
							GROUP BY tartikel.kArtikel
							ORDER BY dErstellt DESC";
			$products = $GLOBALS['DB']->executeQuery($query . $cSQL, 2);
			if (count($products) > 0) {
				foreach ($products as &$product) {
					$product->headline  = ($product->headline2 != '') ? utf8_encode($product->headline2) : utf8_encode($product->headline);
					$product->startDate = str_replace('-', ',', $product->startDate);
					$product->text      = ($product->text2 != '' && $product->url2 != '') ? utf8_encode($product->text2 . "<a href='" . $product->url2 . "'>" . $GLOBALS['oSprache']->gibWert('moreLink', 'news') . "</a>") : utf8_encode($product->text . "<a href='" . $product->url . "'>" . $GLOBALS['oSprache']->gibWert('moreLink', 'news') . "</a>");
					$product->asset     = array(
						'media'   => ($product->cPfad != '' && $product->cPfad != null) ? 'bilder/produkte/klein/' . $product->cPfad : '',
						'credit'  => $this->oPlugin->oPluginEinstellungAssoc_arr['kk_timeline_copyright'],
						'caption' => ''
					);
					unset($product->url);
					unset($product->cPfad);
				}
			}
		}

		return $products;
	}

	/**
	 * @param $text - the text to shorten
	 * @param $length - the length to shorten to
	 * @param string $placeholder - placeholder text at the end of the string
	 * @return string
	 */
	public function shortenText ($text, $length, $placeholder = '...') {
		if (strlen($text) > $length && $length != 0) {
			$string = substr($text, 0, $length);
			$string = substr($text, 0, strrpos($string, ' ')) . $placeholder;
			return $string;
		} else {
			return $text;
		}
	}
}