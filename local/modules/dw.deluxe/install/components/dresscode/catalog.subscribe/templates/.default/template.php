<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?$this->setFrameMode(true);?>
<?if(!empty($arResult["RUBRIC"])):?>
	<div class="catalogSubscribe">
		<div class="catalogSubscribeContainer">
			<div class="catalogSubscribeColumn">
				<div class="catalogSubscribeHeading"><?=$arResult["RUBRIC"]["NAME"]?></div>
				<div class="catalogSubscribeDescription"><?=$arResult["RUBRIC"]["DESCRIPTION"]?></div>
			</div>
			<div class="catalogSubscribeColumn">
				<form action="<?=$componentPath?>/ajax.php" class="catalogSubscribeForm"<?if(!empty($arResult["RUBRIC"]["CODE"])):?> name="subscribe_<?=$arResult["RUBRIC"]["CODE"]?>"<?endif;?> data-subscribe-id="<?=$arResult["RUBRIC"]["ID"]?>">
					<div class="catalogSubscribeRotator">
						<div class="catalogSubscribeRotatorContainer">
							<div class="catalogSubscribeRotatorBg">
								<div class="catalogSubscribeLabel">
									<img src="<?=$templateFolder?>/images/email.jpg" alt="<?=GetMessage("CATALOG_SUBSCRIBE_EMAIL");?>" title="<?=GetMessage("CATALOG_SUBSCRIBE_EMAIL");?>">
								</div>
								<div class="catalogSubscribeField">
									<input type="text" name="subscribe_email" placeholder="<?=GetMessage("CATALOG_SUBSCRIBE_EMAIL");?>" class="catalogSubscribeEmail">
								</div>
								<div class="catalogSubscribeButtons">
									<a href="#" class="catalogSubscribeSend btn-simple btn-micro"><span class="icon"></span><span class="text"><?=GetMessage("CATALOG_SUBSCRIBE_SEND");?></span></a>
								</div>
							</div>
							<div class="catalogSubscribePersonal">
								<input type="checkbox" name="catalogSubscribePersonalInfo" id="catalogSubscribePersonalInfo">
								<label for="catalogSubscribePersonalInfo"><?=GetMessage("CATALOG_SUBSCRIBE_PERSONAL_INFO", array("#SITE_DIR#" => SITE_DIR))?></label>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
	<div class="catalogSubscribeSuccess">
		<div class="catalogSubscribeSuccessIcon">
			<img src="<?=$templateFolder?>/images/success.png" class="catalogSubscribeSuccessIconImage" alt="<?=GetMessage("CATALOG_SUBSCRIBE_SUCCESS_HEADING")?>" title="<?=GetMessage("CATALOG_SUBSCRIBE_SUCCESS_HEADING")?>">
		</div>
		<div class="catalogSubscribeSuccessHeading"><?=GetMessage("CATALOG_SUBSCRIBE_SUCCESS_HEADING")?></div>
		<div class="catalogSubscribeSuccessText"><?=GetMessage("CATALOG_SUBSCRIBE_SUCCESS_TEXT")?></div>
		<div class="catalogSubscribeSuccessClose"><a href="#" class="catalogSubscribeSuccessCloseButton btn-simple"><?=GetMessage("CATALOG_SUBSCRIBE_SUCCESS_CLOSE")?></a></div>
	</div>
	<div class="catalogSubscribeError">
		<div class="catalogSubscribeErrorIcon">
			<img src="<?=$templateFolder?>/images/error.png" class="catalogSubscribeErrorIconImage" alt="<?=GetMessage("CATALOG_SUBSCRIBE_ERROR_HEADING")?>" title="<?=GetMessage("CATALOG_SUBSCRIBE_ERROR_HEADING")?>">
		</div>
		<div class="catalogSubscribeErrorHeading"><?=GetMessage("CATALOG_SUBSCRIBE_ERROR_HEADING")?></div>
		<div class="catalogSubscribeErrorText"><?=GetMessage("CATALOG_SUBSCRIBE_ERROR_TEXT")?></div>
		<div class="catalogSubscribeErrorClose"><a href="#" class="catalogSubscribeErrorCloseButton btn-simple"><?=GetMessage("CATALOG_SUBSCRIBE_SUCCESS_CLOSE")?></a></div>
	</div>
	<script>
		var subscribeSiteId = "<?=SITE_ID?>";
	</script>
<?endif;?>