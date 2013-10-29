<script type="text/javascript">
	var timelineJson = {ldelim}
		"timeline":
		{ldelim}
			"headline": "{if isset($kk_box_setting.start_headline)}{$kk_box_setting.start_headline}{else}Hello World!{/if}",
			"type": "default",
			"text": "{if isset($kk_box_setting.start_text)}{$kk_box_setting.start_text}{else}Ändern Sie diesen Text im Backend.{/if}",
			"startDate": "{if isset($kk_box_setting.start_date)}{$kk_box_setting.start_date}{else}2013,03,01{/if}",
			"date": [
			        {foreach item = "entry" from = $kk_box_setting.slides name="items"}
				    {ldelim}
					    text: {$entry.text|@json_encode},
					    asset: {ldelim}
						    "media": "{$entry.media}",
					        "credit": "{$entry.credit}",
					        "caption": "{$entry.caption}"
					         {rdelim},
					    headline: "{$entry.headline}",
					    startDate: "{$entry.startDate}"
			        {rdelim}
				        {if !$smarty.foreach.items.last},{/if}
			        {/foreach}
					{if $kk_box_setting.timeline_integratenews eq true}
					,
					{foreach item = "entry" from = $oNews_arr name="items"}
						{ldelim}
							text: {"`$entry->cVorschauText` `$entry->cMehrURL`"|@json_encode },
							asset: {ldelim}
								"media": "{$entry->cThumbUrl}",
								"credit": "Kreativkonzentrat.de",
								"caption": ""
							{rdelim},
							headline: "{$entry->cBetreff}",
							startDate: "{$entry->dErstellt|replace:"-":","|substr:0:10}"
						{rdelim}{if !$smarty.foreach.items.last},{/if}
					{/foreach}
					{/if}
			]
		{rdelim}
	{rdelim}
	jQuery(document).ready(function() {ldelim}
		createStoryJS({ldelim}
			type:       'timeline',
			width:      '{if isset($kk_box_setting.timeline_width)}{$kk_box_setting.timeline_width}{else}100%{/if}',
			start_at_slide: {if isset($kk_box_setting.timeline_startslide)}{$kk_box_setting.timeline_startslide}{else}0{/if},
			height:     '{if isset($kk_box_setting.timeline_height)}{$kk_box_setting.timeline_height}{else}600{/if}',
			source:     timelineJson,
			lang: {if $lang eq "ger"}"de"{else}"en"{/if},
			embed_id:   'kk-timeline-runtime-{$kk_box_setting.runtimeId}'
		{rdelim});
	{rdelim});
</script>
<div id="kk-timeline-runtime-{$kk_box_setting.runtimeId}"></div>