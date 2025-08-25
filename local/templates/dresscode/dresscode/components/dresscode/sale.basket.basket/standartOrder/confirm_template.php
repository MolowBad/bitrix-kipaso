<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?use Bitrix\Main\Localization\Loc;?>
<?global $USER;?>
<?if(!empty($arResult["ERRORS"]["ORDER_NOT_FOUND"])):?>
	<div class="orderError"><?=Loc::GetMessage("ORDER_NOT_FOUND");?></div>
	<?return false;?>
<?endif;?>
<?if(!empty($arResult["ORDER"])):?>
	<div class="orderAccountNumber">
		<?if(empty($arResult["ORDER"]["ALLOW_PAY"])):?>
			<?=Loc::getMessage("ORDER_PAY_NOT_ALLOWED", array(
				"#ORDER_DATE#" => $arResult["ORDER"]["DATE_INSERT"]->toUserTime()->format("d.m.Y H:i"),
				"#ORDER_ID#" => $arResult["ORDER"]["ACCOUNT_NUMBER"]
			))?>
		<?elseif(!empty($arResult["ORDER"]["PAYED"]) && $arResult["ORDER"]["PAYED"] == "Y"):?>
			<?=Loc::getMessage("ORDER_IS_PAID", array(
				"#ORDER_DATE#" => $arResult["ORDER"]["DATE_INSERT"]->toUserTime()->format("d.m.Y H:i"),
				"#ORDER_ID#" => $arResult["ORDER"]["ACCOUNT_NUMBER"]
			))?>
		<?else:?>
			<?=Loc::getMessage("ORDER_MAKE_TEXT", array(
				"#ORDER_DATE#" => $arResult["ORDER"]["DATE_INSERT"]->toUserTime()->format("d.m.Y H:i"),
				"#ORDER_ID#" => $arResult["ORDER"]["ACCOUNT_NUMBER"]
			))?>
		<?endif;?>
	</div>
	<?if(!empty($arParams["ORDER_CONFIRM_BY_SMS_STATUS"]) && $arParams["ORDER_CONFIRM_BY_SMS_CODE"] == "Y"):?>
		<?if($arParams["ORDER_CONFIRM_BY_SMS_STATUS"] != $arResult["ORDER"]["STATUS"]):?>
			<div class="confirmContainer">
				<div class="confirmHeading"><?=Loc::getMessage("ORDER_CONFIRM_HEADING");?></div>
				<div class="confirmText"><?=Loc::getMessage("ORDER_CONFIRM_TEXT");?></div>
				<form class="confirmForm" action="" method="post" autocomplete="off" novalidate>
					<div class="confirmTable">
						<div class="confirmData">
							<div class="confirmLabel"><span><?=Loc::getMessage("ORDER_CONFIRM_LABEL");?></span></div>
							<div class="confirmCodeContainer">
								<div class="confirmSubstrate">0000</div>
								<input type="text" name="sms-code" class="confirmCode" data-order-id="<?=intval($arResult["ORDER"]["ID"])?>" autocomplete="off" value="">
							</div>
						</div>
						<div class="confirmButton">
							<a href="#" class="confirmSend btn-simple btn-medium" data-success="<?=Loc::getMessage("ORDER_CONFIRM_SEND_BUTTON_SUCCESS");?>"><?=Loc::getMessage("ORDER_CONFIRM_SEND_BUTTON");?></a>
						</div>
					</div>
				</form>
				<div class="confirmErrors"></div>
				<a href="#" class="confirmReplay link-dashed" data-order-id="<?=intval($arResult["ORDER"]["ID"])?>"><?=Loc::getMessage("ORDER_CONFIRM_REPLAY");?><span class="confirmTimer">(0:0)</span></a>
				<script>
					$(document).on("ready", function(){
						DwBasket.tools.startTimer(".confirmTimer", ".confirmReplay");
					});
				</script>
			</div>
		<?else:?>
			<div class="confirmContainer confirmed">
				<div class="confirmHeading"><?=Loc::getMessage("ORDER_CONFIRMED_HEADING");?></div>
			</div>
		<?endif;?>
	<?endif;?>
	<div class="paymentNumber">
		<?if(!empty($arResult["ORDER"]["PAYMENT_ID"])):?>
			<?=Loc::getMessage("ORDER_MAKE_PAYMENT", array(
				"#PAYMENT_NUMBER#" => $arResult["ORDER"]["PAYMENTS"][$arResult["ORDER"]["PAYMENT_ID"]]["ACCOUNT_NUMBER"]
			))?>
		<?endif;?>
	</div>
	<?if($USER->IsAuthorized()):?>
		<div class="personalText">
			<?=Loc::getMessage("ORDER_MAKE_NEXT", array(
				"#PERSONAL_LINK#" => SITE_DIR . "personal/order/"
			))?>
		</div>
	<?endif;?>
	<?if(empty($arResult["ERRORS"])):?>
		<?if(!empty($arResult["ORDER"]["PAYMENT_SERVICES"])):?>
			<div class="paymentHeading">
				<?=Loc::getMessage("ORDER_FINAL_PAY")?>
			</div>
			<?foreach($arResult["ORDER"]["PAYMENT_SERVICES"] as $paymentService):?>
				<div class="nextPayment">
					<?if(empty($paymentService["ERROR"])):?>
						<?if(!empty($paymentService["LOGOTIP"])):?>
							<div class="paymentLogo"><img src="<?=$paymentService["LOGOTIP"]["src"]?>" alt="<?=$paymentService["NAME"]?>" title="<?=$paymentService["NAME"]?>"></div>
						<?endif;?>
						<div class="paymentName"><?=$paymentService["NAME"]?></div>
						<div class="paymentContainer">
							<?if(!empty($paymentService["ACTION_FILE"]) && $paymentService["NEW_WINDOW"] == "Y" && $paymentService["IS_CASH"] != "Y"):?>
								<script>
									window.open("<?=$arParams["PATH_TO_PAYMENT"]?>?ORDER_ID=<?=urlencode(urlencode($arResult["ORDER"]["ACCOUNT_NUMBER"]))?>&PAYMENT_ID=<?=$arResult["ORDER"]["PAYMENTS"][$arResult["ORDER"]["PAYMENT_ID"]]["ACCOUNT_NUMBER"]?>");
								</script>
								<?=Loc::getMessage("ORDER_PAY_LINK", array("#LINK#" => $arParams["PATH_TO_PAYMENT"]."?ORDER_ID=".urlencode(urlencode($arResult["ORDER"]["ACCOUNT_NUMBER"]))."&PAYMENT_ID=".$arResult["ORDER"]["PAYMENTS"][$arResult["ORDER"]["PAYMENT_ID"]]["ACCOUNT_NUMBER"]))?>
								<?if(CSalePdf::isPdfAvailable() && $paymentService["IS_AFFORD_PDF"]):?>
									<?=Loc::getMessage("ORDER_PAY_PDF", array("#LINK#" => $arParams["PATH_TO_PAYMENT"]."?ORDER_ID=".urlencode(urlencode($arResult["ORDER"]["ACCOUNT_NUMBER"]))."&pdf=1&DOWNLOAD=Y"))?>
								<?endif?>
							<?else:?>
								<?=$paymentService["BUFFERED_OUTPUT"]?>
							<?endif;?>
						</div>
					<?else:?>
						<div class="paymentError">
							<?=Loc::GetMessage("ORDER_PAY_ERROR");?>
						</div>
					<?endif;?>
				</div>
			<?endforeach;?>
		<?endif;?>
	<?endif;?>
	<script>
		var ajaxDir = "<?=$templateFolder?>";
		var siteId = "<?=$component->getSiteId()?>";
		var basketParams = <?=\Bitrix\Main\Web\Json::encode(\DigitalWeb\Basket::clearParams($arParams));?>;
	</script>
<?endif;?>