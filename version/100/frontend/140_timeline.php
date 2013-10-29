<?php
if (!class_exists('KkTimelineHelper')) {
	require_once($oPlugin->cFrontendPfad . 'inc/class.KkTimelineHelper.php');
}

$kkTimelineHelper = new KkTimelineHelper($oPlugin);
$existsInDom      = $kkTimelineHelper->assignPhpQuery();
if ($existsInDom === true) {
	$lang = $_SESSION['cISOSprache'];
	if ($lang === 'eng') {
		$lang = 'en';
	} else {
		$lang = 'de';
	}
	if (file_exists($oPlugin->cFrontendPfad . 'css/custom.css')) {
		pq('head')->append('<link type="text/css" rel="stylesheet" href="' . $oPlugin->cFrontendPfadURL . 'css/custom.css" />');
	}
	$startAtEnd   = ($oPlugin->oPluginEinstellungAssoc_arr['kk_timeline_begin_at'] === '0') ? false : true;
	$startAtSlide = (is_numeric($oPlugin->oPluginEinstellungAssoc_arr['kk_timeline_begin_at_slide'])) ? $oPlugin->oPluginEinstellungAssoc_arr['kk_timeline_begin_at_slide'] : 0;
	pq('head')->append('<script type="text/javascript" src="' . $oPlugin->cFrontendPfadURL . 'js/storyjs-embed.js"></script>');
	pq('head')->append(
		'<script type="text/javascript">
			var timelineJson;
			jQuery(function ($) {
				if ($("#kk-timeline").length > 0) {
					timelineJson = ' . $kkTimelineHelper->generateJson() . ';
				createStoryJS({
					type:       "timeline",
					width:      "100%",
					start_at_slide: ' . $startAtSlide . ',
	                start_at_end: "' . $startAtEnd . '",
					height:     "600",
					source:     timelineJson,
					lang: "' . $lang . '",
					embed_id:   "kk-timeline"
				});
			}
		});
	</script>'
	);

	//Hack links to images
//		pq('head')->append('<script type="text/javascript">
//		window.setTimeout(function () {
//				$("#kk-timeline .slider-item").each(function (idx, elem){
//					var link = $(elem).find("a");
//					if (link && link[0]) {
//						link = link[0].href;
//						var image = $(elem).find("img");
//						if (image && image[0]){
//								image.wrap(\'<a href="\' + link + \'"></a>\');
//							}
//					}
//				});
//				}, 3000);
//		</script>');
}