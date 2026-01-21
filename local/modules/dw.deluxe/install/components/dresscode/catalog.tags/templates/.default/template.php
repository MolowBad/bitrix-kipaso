<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?$this->setFrameMode(true);?>
<?if(!empty($arResult["TAGS"])):?>
	<?$index = 1;?>
	<div class="catalogTagItems<?if($arParams["HIDE_TAGS_ON_MOBILE"] == "Y"):?> mobileHidden<?endif;?>">
		<?foreach($arResult["TAGS"] as $tagIndex => $nextTag):?>
			<div class="catalogTagItem<?if($arParams["MAX_VISIBLE_TAGS_DESKTOP"] < $index):?> desktopHidden<?endif;?><?if($arParams["MAX_VISIBLE_TAGS_MOBILE"] < $index):?> mobileHidden<?endif;?>">
				<a href="<?=$nextTag["LINK"]?>" class="catalogTagLink<?if(!empty($nextTag["SELECTED"]) && $nextTag["SELECTED"] == "Y"):?> selected<?endif;?>"><?=$nextTag["NAME"]?><?if(!empty($nextTag["SELECTED"]) && $nextTag["SELECTED"] == "Y"):?><span class="reset">&#10006;</span><?endif;?></a>
			</div>
			<?$index++;?>
		<?endforeach;?>
		<?if(count($arResult["TAGS"]) > $arParams["MAX_VISIBLE_TAGS_MOBILE"] || count($arResult["TAGS"]) > $arParams["MAX_VISIBLE_TAGS_DESKTOP"]):?>
			<div class="catalogTagItem moreButton<?if($arParams["MAX_VISIBLE_TAGS_DESKTOP"] > count($arResult["TAGS"])):?> desktopHidden<?endif;?><?if($arParams["MAX_VISIBLE_TAGS_MOBILE"] > count($arResult["TAGS"])):?> mobileHidden<?endif;?>"><a href="#" class="catalogTagLink moreButtonLink" data-last-label="<?=GetMessage("CATALOG_TAGS_MORE_BUTTON_HIDE");?>"><?=GetMessage("CATALOG_TAGS_MORE_BUTTON")?></a></div>
		<?endif;?>
	</div>
<?endif;?>