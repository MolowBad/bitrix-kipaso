<?
	if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

	//namespace
	use Bitrix\Main\Localization\Loc;

	//composit bitrix off
	$this->setFrameMode(false);

	//suuport bitrix:sale.order.ajax events for delivery modules
	$this->addExternalJS($templateFolder."/js/compatibility.js");

	//check created order
	if(!empty($arResult["CONFIRM_ORDER"]) && $arResult["CONFIRM_ORDER"] == "Y"){
		//confirm page
		include_once($_SERVER["DOCUMENT_ROOT"]."/".$templateFolder."/confirm_template.php");
		return false;
	}

	//template functions
	include_once($_SERVER["DOCUMENT_ROOT"]."/".$templateFolder."/template_functions.php");

	//view templates
	$arBasketTemplates = array(
		"SQUARES" => array(
			"CHANGE_URL" => $APPLICATION->GetCurPageParam("basketView=squares", array("basketView")),
			"TEMPLATE_FILE" => "/view_templates/basket_squares.php",
			"CLASS_NAME" => "squares"
		),
		"TABLE" => array(
			"CHANGE_URL" => $APPLICATION->GetCurPageParam("basketView=table", array("basketView")),
			"TEMPLATE_FILE" => "/view_templates/basket_table.php",
			"CLASS_NAME" => "table"
		)
	);

	if(!empty($_GET["basketView"]) && !empty($arBasketTemplates[strtoupper($_GET["basketView"])])){
		setcookie("DW_BASKET_TEMPLATE", strtolower($_GET["basketView"]), time() + 3600000);
		$arBasketTemplates[strtoupper($_GET["basketView"])]["SELECTED"] = "Y";
		$_COOKIE["DW_BASKET_TEMPLATE"] = strtolower($_GET["basketView"]);
	}

	elseif(!empty($_COOKIE["DW_BASKET_TEMPLATE"])){
		$arBasketTemplates[strtoupper($_COOKIE["DW_BASKET_TEMPLATE"])]["SELECTED"] = "Y";
	}

	else{
		$arBasketTemplates[key($arBasketTemplates)]["SELECTED"] = "Y";
	}

?>

<?if(!empty($arResult["ITEMS"])):?>
	<?
		//vars
		$personTypeIndex = 0;
		$component = $this->getComponent();
		$countPos = 0;
	?>

	<div id="personalCart" class="DwBasket">
		<div id="basketTopLine">
			<div id="tabsControl">
				<div class="item"><?=Loc::getMessage("BASKET_TABS_ACTIONS")?></div>
				<div class="item"><a href="<?=SITE_DIR?>personal/cart/order/" id="scrollToOrder" class="orderMove selected"><?=Loc::getMessage("BASKET_TABS_ORDER_MAKE")?></a></div>
				<div class="item"><a href="<?=SITE_DIR?>catalog/"><?=Loc::getMessage("BASKET_TABS_CONTINUE")?></a></div>
				<div class="item"><a href="#" id="allClear" class="clearAllBasketItems"><?=Loc::getMessage("BASKET_TABS_CLEAR")?></a></div>
			</div>
			<?if(!empty($arBasketTemplates)):?>
				<div id="basketView">
						<div class="item">
							<span><?=Loc::getMessage("BASKET_VIEW_LABEL")?></span>
						</div>
					<?foreach ($arBasketTemplates as $arNextBasketTemplate):?>
						<div class="item">
							<a href="<?=$arNextBasketTemplate["CHANGE_URL"]?>" class="<?=$arNextBasketTemplate["CLASS_NAME"]?><?if($arNextBasketTemplate["SELECTED"] == "Y"):?> selected<?endif;?>"></a>
						</div>
					<?endforeach;?>
				</div>
			<?endif;?>
		</div>
		<div id="basketProductList">
			<?if(!empty($_COOKIE["DW_BASKET_TEMPLATE"]) && $_COOKIE["DW_BASKET_TEMPLATE"] == "table"):?>
				<?if(!empty($arBasketTemplates["TABLE"]["TEMPLATE_FILE"])):?>
					<?include($_SERVER["DOCUMENT_ROOT"].$templateFolder.$arBasketTemplates["TABLE"]["TEMPLATE_FILE"]);?>
				<?endif;?>
			<?else:?>
				<?if(!empty($arBasketTemplates["TABLE"]["TEMPLATE_FILE"])):?>
					<?include($_SERVER["DOCUMENT_ROOT"].$templateFolder.$arBasketTemplates["SQUARES"]["TEMPLATE_FILE"]);?>
				<?endif;?>
			<?endif;?>
		</div>
		<div class="orderLine">
			<div id="sum">
				<span class="label hd"><?=Loc::getMessage("TOTAL_QTY")?></span>
				<span class="price hd countItems"><?=$countPos?></span>
				<span class="label"><?=Loc::getMessage("TOTAL_SUM")?></span>
				<span class="price">
					<span class="basketSum"><?=FormatCurrency($arResult["BASKET_SUM"], $arResult["CURRENCY"]["CODE"]);?></span>
				</span>
			</div>
			<?if(empty($arParams["HIDE_COUPON_FIELD"]) || $arParams["HIDE_COUPON_FIELD"] != "Y"):?>
				<form id="coupon" autocomplete="off">
					<input placeholder="<?=Loc::getMessage("COUPON_LABEL")?>" name="user" class="couponField" autocomplete="new-password"><input type="submit" value="<?=Loc::getMessage("COUPON_ACTIVATE")?>" class="couponActivate">
				</form>
			<?endif;?>
		</div>
		<div class="minimumPayment<?if(!empty($arResult["IS_MIN_ORDER_AMOUNT"])):?> hidden<?endif;?>">
			<div class="minimumPaymentLeft">
				<div class="paymentIcon"><img src="<?=$templateFolder?>/images/minOrder.png" alt="" title=""></div>
				<div class="paymentMessage">
					<div class="paymentMessageHeading">
						<?=Loc::getMessage("MINIMUM_PAYMENT_HEADING", array(
							"#MIN_PRICE_FORMATED#" => \DigitalWeb\Basket::formatPrice($arParams["MIN_SUM_TO_PAYMENT"]),
						))?>
					</div>
					<div class="paymentMessageText">
						<?=Loc::getMessage("MINIMUM_PAYMENT_TEXT")?>
					</div>
				</div>
			</div>
			<div class="minimumPaymentRight">
				<div class="paymentButtons">
					<div class="paymentButtonsMain">
						<a href="<?=SITE_DIR?>" class="btn-simple btn-small btn-border"><?=Loc::getMessage("MINIMUM_PAYMENT_MAIN_BUTTON")?></a>
					</div>
					<div class="paymentButtonsCatalog">
						<a href="<?=SITE_DIR?>catalog/" class="btn-simple btn-small"><?=Loc::getMessage("MINIMUM_PAYMENT_CATALOG_BUTTON")?></a>
					</div>
				</div>
			</div>
		</div>
		<div class="giftContainer">
			<?$APPLICATION->IncludeComponent("bitrix:sale.gift.basket", ".default", Array(
					"APPLIED_DISCOUNT_LIST" => $arResult["APPLIED_DISCOUNT_LIST"],
					"HIDE_NOT_AVAILABLE" => $arParams["HIDE_NOT_AVAILABLE"],
					"CONVERT_CURRENCY" => $arParams["GIFT_CONVERT_CURRENCY"],
					"FULL_DISCOUNT_LIST" => $arResult["FULL_DISCOUNT_LIST"],
					"PRODUCT_PRICE_CODE" => $arParams["PRODUCT_PRICE_CODE"],
					"CURRENCY_ID" => $arParams["GIFT_CURRENCY_ID"],
					"HIDE_MEASURES" => $arParams["HIDE_MEASURES"],
					"PAGE_ELEMENT_COUNT" => "12",
					"LINE_ELEMENT_COUNT" => "12",
					"CACHE_GROUPS" => "Y",
				),
				false
			);?>
		</div>
		<div id="order" class="DwBasketOrder orderContainer<?if(empty($arResult["IS_MIN_ORDER_AMOUNT"])):?> hidden<?endif;?>">
			<span class="title"><?=Loc::getMessage("ORDER_HEADING")?></span>
			<table class="personSelectContainer">
				<tr<?if(count($arResult["PERSON_TYPES"]) == 1):?> class="hidden"<?endif;?>>
					<td><span><?=Loc::getMessage("ORDER_PERSON")?></span></td>
					<td>
						<?if(!empty($arResult["PERSON_TYPES"])):?>
							<label><?=Loc::getMessage("ORDER_PERSON_SELECT")?></label>
							<div class="dropDown">
								<?foreach($arResult["PERSON_TYPES"] as $arPersonType):?>
									<?if(!empty($arPersonType["SELECTED"]) && $arPersonType["SELECTED"] == "Y"):?>
										<div class="dropDownSelected"><?=$arPersonType["NAME"]?></div>
									<?endif;?>
								<?endforeach;?>
								<div class="dropDownItems">
									<?foreach($arResult["PERSON_TYPES"] as $arPersonType):?>
										<div class="dropDownItem<?if(!empty($arPersonType["SELECTED"])):?> selected<?endif;?>" data-value="<?=$arPersonType["ID"]?>"><?=$arPersonType["NAME"]?></div>
									<?endforeach;?>
								</div>
								<select id="personSelect" class="personSelect">
									<?foreach($arResult["PERSON_TYPES"] as $arPersonType):?>
										<?if($arPersonType["ACTIVE"] === "Y"):?>
											<option value="<?=$arPersonType["ID"]?>" data-id="<?=$arPersonType["ID"]?>"><?=$arPersonType["NAME"]?></option>
										<?endif;?>
									<?endforeach;?>
								</select>
							</div>
							<div class="personTypeForModules hidden">
								<?$personIter = 0?>
								<?foreach($arResult["PERSON_TYPES"] as $arPersonType):?>
									<?if($arPersonType["ACTIVE"] === "Y"):?>
										<input type="radio" name="PERSON_TYPE" value="<?=$arPersonType["ID"]?>" data-id="<?=$arPersonType["ID"]?>" class="personTypeModules"<?if($personIter++ == 0):?> checked<?endif;?>>
									<?endif;?>
								<?endforeach;?>
							</div>
						<?endif;?>
					</td>
				</tr>
			</table>
			<?if(!empty($arResult["PERSON_TYPES"])):?>
				<?foreach($arResult["PERSON_TYPES"] as $arPersonType):?>
					<form enctype="multipart/form-data" class="orderForm<?if($personTypeIndex == 0):?> active<?endif;?>" id="orderForm_<?=$arPersonType["ID"]?>" data-id="<?=$arPersonType["ID"]?>">
						<table class="orderAreas<?if($personTypeIndex == 0):?> active<?endif;?>" data-person-id="<?=$arPersonType["ID"]?>">
							<?if(!empty($arResult["PROPERTY_GROUPS"])):?>
								<?foreach($arResult["PROPERTY_GROUPS"] as $arGroup):?>
									<?if(\DigitalWeb\Basket::checkGroupProperties($arGroup["ID"], $arPersonType["ID"], $arResult["PROPERTIES"])):?>
										<tr>
											<td><span><?=$arGroup["NAME"]?></span></td>
											<td class="mainPropArea">
												<ul class="userProp">
													<?foreach($arResult["PROPERTIES"] as $arProperty){
														if($arProperty["PROPS_GROUP_ID"] == $arGroup["ID"] && $arProperty["PERSON_TYPE_ID"] == $arPersonType["ID"] && empty($arProperty["RELATION"])){
															printOrderPropertyHTML($arProperty, ' class="propItem"', $arParams);
														}
													}?>
												</ul>
											</td>
										</tr>
									<?endif;?>
								<?endforeach;?>
							<?endif;?>
						</table>
						<table class="orderAreas<?if($personTypeIndex == 0):?> active<?endif;?>" data-person-id="<?=$arPersonType["ID"]?>">
							<?if(!empty($arResult["ORDER"]["DELIVERIES"])):?>
								<tr>
									<td><span><?=Loc::getMessage("ORDER_DELIVERY")?></span></td>
									<td>
										<span class="label"><?=Loc::getMessage("ORDER_DELIVERY")?></span>
										<div class="dropDown">
											<?foreach($arResult["ORDER"]["DELIVERIES"] as $arDevivery):?>
												<?if($arResult["FIRST_DELIVERY"]["ID"] == $arDevivery["ID"]):?>
													<div class="dropDownSelected"><?=htmlspecialcharsbx($arDevivery["NAME"])?> <?=$arDevivery["PRICE_FORMATED"]?></div>
												<?endif;?>
											<?endforeach;?>
											<div class="dropDownItems">
												<?foreach($arResult["ORDER"]["DELIVERIES"] as $arDevivery):?>
													<div class="dropDownItem<?if($arResult["FIRST_DELIVERY"]["ID"] == $arDevivery["ID"]):?> selected<?endif;?>" data-value="<?=$arDevivery["ID"]?>"><?=htmlspecialcharsbx($arDevivery["NAME"])?> <?=$arDevivery["PRICE_FORMATED"]?></div>
												<?endforeach;?>
											</div>
											<select class="deliverySelect" name="DEVIVERY_TYPE" data-default="<?=$arResult["FIRST_DELIVERY"]["ID"]?>">
												<?foreach($arResult["ORDER"]["DELIVERIES"] as $arDevivery):?>
													<option data-price="<?=doubleval($arDevivery["PRICE"])?>" data-code="<?=$arDevivery["CODE"]?>" data-name="<?=htmlspecialcharsbx($arDevivery["NAME"])?>"<?if($arResult["FIRST_DELIVERY"]["ID"] == $arDevivery["ID"]):?> selected<?endif;?> value="<?=$arDevivery["ID"]?>"><?=htmlspecialcharsbx($arDevivery["NAME"])?> <?=$arDevivery["PRICE_FORMATED"]?></option>
												<?endforeach;?>
											</select>
										</div>
										<?if(!empty($arResult["STORES"])):?>
											<?$firstStoreId = 0;?>
											<div class="storeSelectContainer<?if(empty($arResult["FIRST_DELIVERY"]["STORES"])):?> hidden<?endif;?>">
												<span class="label"><?=Loc::getMessage("STORE_SELECT")?></span>
												<div class="dropDown">
													<?foreach($arResult["STORES"] as $arStore):?>
														<?if(in_array($arStore["ID"], $arResult["FIRST_DELIVERY"]["STORES"])):?>
															<?if(empty($firstStoreId) && $firstStoreId = $arStore["ID"]):?>
																<div class="dropDownSelected"><?=$arStore["TITLE"]?> - <?=$arStore["ADDRESS"]?> - <?=$arStore["PRODUCTS_STATUS"]?></div>
															<?endif;?>
														<?endif;?>
													<?endforeach;?>
													<?if(empty($firstStoreId)):?>
														<div class="dropDownSelected"></div>
													<?endif;?>
													<div class="dropDownItems">
														<?foreach($arResult["STORES"] as $arStore):?>
															<?if(in_array($arStore["ID"], $arResult["FIRST_DELIVERY"]["STORES"])):?>
																<div class="dropDownItem<?if($firstStoreId == $arStore["ID"]):?> selected<?endif;?>" data-value="<?=$arStore["ID"]?>"><?=$arStore["TITLE"]?> - <?=$arStore["ADDRESS"]?> - <?=$arStore["PRODUCTS_STATUS"]?></div>
															<?endif;?>
														<?endforeach;?>
													</div>
													<select class="storeSelect" name="STORE">
														<?foreach($arResult["STORES"] as $arStore):?>
															<?if(in_array($arStore["ID"], $arResult["FIRST_DELIVERY"]["STORES"])):?>
																<option value="<?=$arStore["ID"]?>"><?=$arStore["TITLE"]?> - <?=$arStore["ADDRESS"]?> - <?=$arStore["PRODUCTS_STATUS"]?></option>
															<?endif;?>
														<?endforeach;?>
													</select>
												</div>
											</div>
										<?endif;?>
										<div class="extraServiceItems<?if(empty($arResult["FIRST_DELIVERY"]["EXTRA_SERVICES"])):?> hidden<?endif;?>">
											<?if(!empty($arResult["FIRST_DELIVERY"]["EXTRA_SERVICES"])):?>
												<?=getExtraServicesHTML($arResult["FIRST_DELIVERY"]["EXTRA_SERVICES"], $arResult["CURRENCY"]["CODE"]);?>
											<?endif;?>
										</div>
										<?if(!empty($arResult["FIRST_DELIVERY"])):?>
											<ul class="userProp">
												<?foreach($arResult["PROPERTIES"] as $arProperty){
													if(!empty($arProperty["RELATION"]) && $arProperty["PERSON_TYPE_ID"] == $arPersonType["ID"]){
														$attrList = ' data-property-id="'.$arProperty["ID"].'" class="propItem deliveryProps'.(empty($arProperty["DELIVERY_RELATION"]) || $arProperty["DELIVERY_RELATION"] == "N" ? ' hidden' : '').'"';
														printOrderPropertyHTML($arProperty, $attrList, $arParams);
													}
												}?>
											</ul>
										<?endif;?>
										<div class="deliveryDescription<?if(empty($arResult["FIRST_DELIVERY"]["DESCRIPTION"])):?> hidden<?endif;?>">
											<?if(!empty($arResult["FIRST_DELIVERY"]["DESCRIPTION"])):?>
												<?=$arResult["FIRST_DELIVERY"]["DESCRIPTION"]?>
											<?endif;?>
										</div>
										<div class="deliveryPeriod<?if(empty($arResult["FIRST_DELIVERY"]["PERIOD_DESCRIPTION"])):?> hidden<?endif;?>">
											<div class="deliveryPeriodLabel"><?=GetMessage("ORDER_PERIOD_DESCRIPTION")?></div>
											<div class="deliveryPeriodDescription">
												<?if(!empty($arResult["FIRST_DELIVERY"]["PERIOD_DESCRIPTION"])):?>
													<?=$arResult["FIRST_DELIVERY"]["PERIOD_DESCRIPTION"]?>
												<?endif;?>
											</div>
										</div>
										<div id="bx-soa-delivery" class="deliveryModulesContainer">
											<div class="bx-soa-pp-company-desc deliveryModulesContainerDesc"></div>
										</div>
									</td>
								</tr>
							<?endif;?>
							<tr>
								<td>
									<span><?=Loc::getMessage("ORDER_PAY")?></span>
								</td>
								<td>
									<span class="label"><?=Loc::getMessage("ORDER_PAY")?></span>
									<div class="dropDown">
										<?if(!empty($arResult["ORDER"]["PAYSYSTEMS"])):?>
											<?foreach($arResult["ORDER"]["PAYSYSTEMS"] as $arPaysystem):?>
												<?if($arResult["FIRST_PAYSYSTEM"]["ID"] == $arPaysystem["ID"]):?>
													<div class="dropDownSelected"><?=$arPaysystem["NAME"]?></div>
												<?endif;?>
											<?endforeach;?>
											<div class="dropDownItems">
												<?foreach($arResult["ORDER"]["PAYSYSTEMS"] as $arPaysystem):?>
													<div class="dropDownItem<?if($arResult["FIRST_PAYSYSTEM"]["ID"] == $arPaysystem["ID"]):?> selected<?endif;?>" data-value="<?=$arPaysystem["ID"]?>"><?=$arPaysystem["NAME"]?></div>
												<?endforeach;?>
											</div>
										<?else:?>
											<div class="dropDownSelected"><?=$arPaysystem["NAME"]?></div>
											<div class="dropDownItems">
												<div class="dropDownItem selected" data-value="0"><?=Loc::getMessage("EMPTY_PAYSYSTEMS")?></div>
											</div>
										<?endif;?>
										<select class="paySelect" name="PAY_TYPE">
											<?if(!empty($arResult["ORDER"]["PAYSYSTEMS"])):?>
												<?foreach($arResult["ORDER"]["PAYSYSTEMS"] as $arPaysystem):?>
													<option value="<?=$arPaysystem["ID"]?>"><?=$arPaysystem["NAME"]?></option>
												<?endforeach;?>
											<?else:?>
												<option value="0" data-empty="Y"><?=Loc::getMessage("EMPTY_PAYSYSTEMS")?></option>
											<?endif;?>
										</select>
									</div>
									<div class="payFromBudget<?if(empty($arResult["ORDER"]["INNER_PAYMENT"])):?> hidden<?endif;?>">
										<input type="checkbox" value="Y" id="payFromBudget_<?=$arPersonType["ID"]?>" name="payFromBudget" class="budgetSwitch"<?if(!empty($arResult["ORDER"]["INNER_PAYMENT"]["RANGE"]["MAX"])):?> data-max="<?=$arResult["ORDER"]["INNER_PAYMENT"]["RANGE"]["MAX"]?>"<?endif;?> data-account-balance="<?=$arResult["USER_ACCOUNT"]["CURRENT_BUDGET"]?>">
										<label for="payFromBudget_<?=$arPersonType["ID"]?>"><?=Loc::getMessage("PAY_FROM_BUDGET")?> (<?=Loc::getMessage("ACCOUNT_BALANCE")?> <?=$arResult["USER_ACCOUNT"]["PRINT_CURRENT_BUDGET"]?>)</label>
									</div>
									<?if(!empty($arResult["PROPERTIES"])):?>
										<ul class="userProp">
											<?foreach($arResult["PROPERTIES"] as $arProperty){
												if(!empty($arProperty["RELATION"]) && $arProperty["PERSON_TYPE_ID"] == $arPersonType["ID"]){
													$attrList = ' data-property-id="'.$arProperty["ID"].'" class="propItem payProps'.(empty($arProperty["PAYSYSTEM_RELATION"]) || $arProperty["PAYSYSTEM_RELATION"] == "N" ? ' hidden' : '').'"';
													printOrderPropertyHTML($arProperty, $attrList, $arParams);
												}
											}?>
										</ul>
									<?endif;?>
								</td>
							</tr>
							<tr>
								<td></td>
								<td>
									<span class="label"><?=Loc::getMessage("ORDER_COMMENT")?></span>
									<textarea name="comment" class="orderComment"></textarea>
									<div class="personalInfoLabel"><?=Loc::getMessage("PERSONTAL_INFO_ORDER_LABEL")?></div>
								</td>
							</tr>
						</table>
						<input type="hidden" name="PERSON_TYPE" value="<?=$arPersonType["ID"]?>">
					</form>
					<?$personTypeIndex++;?>
				<?endforeach;?>
			<?endif;?>
			<div class="orderLine bottom">
				<div id="sum">
					<a href="#" class="order orderMake" id="orderMake"><img src="<?=SITE_TEMPLATE_PATH?>/images/order.svg"> <?=Loc::getMessage("ORDER_GO")?></a>
					<span class="label hd"><?=Loc::getMessage("TOTAL_QTY")?></span> <span class="price hd countItems"><?=$countPos?></span>
					<span class="label<?if(empty($arResult["ORDER"]["DELIVERIES"])):?> hidden<?endif;?>"><?=Loc::getMessage("ORDER_DELIVERY")?>:</span>
					<span class="price<?if(empty($arResult["ORDER"]["DELIVERIES"])):?> hidden<?endif;?>"><span class="deliverySum"><?=$arResult["FIRST_DELIVERY"]["PRICE_FORMATED"];?></span></span>
					<span class="label"><?=Loc::getMessage("ORDER_TOTAL_SUM")?></span>
					<span class="price"><span class="orderSum"><?=FormatCurrency($arResult["BASKET_SUM"] + $arResult["FIRST_DELIVERY"]["PRICE"], $arResult["CURRENCY"]["CODE"]);?></span></span>
				</div>
			</div>
		</div>
		<div class="clear"></div>
	</div>
	<div class="basketError error1">
		<div class="basketErrorContainer">
			<div class="errorPicture"><img src="<?=$templateFolder?>/images/error.jpg" alt="" title=""></div>
			<div class="errorHeading"><?=Loc::getMessage("ORDER_ERROR_1_HEADING")?></div>
			<a href="#" class="basketErrorClose errorClose"><span class="errorCloseLink"></span></a>
			<div class="errorMessage"><?=Loc::getMessage("ORDER_ERROR_1")?></div>
			<a href="#" class="basketErrorClose btn-simple btn-small"><?=Loc::getMessage("ORDER_CLOSE")?></a>
		</div>
	</div>
	<div class="basketError error2">
		<div class="basketErrorContainer">
			<div class="errorPicture"><img src="<?=$templateFolder?>/images/error.jpg" alt="" title=""></div>
			<div class="errorHeading"><?=Loc::getMessage("ORDER_ERROR_2_HEADING")?></div>
			<a href="#" class="basketErrorClose errorClose"><span class="errorCloseLink"></span></a>
			<div class="errorMessage"><?=Loc::getMessage("ORDER_ERROR_2")?></div>
			<a href="#" class="basketErrorClose btn-simple btn-small"><?=Loc::getMessage("ORDER_CLOSE")?></a>
		</div>
	</div>
	<div class="basketError error3">
		<div class="basketErrorContainer">
			<div class="errorPicture"><img src="<?=$templateFolder?>/images/error.jpg" alt="" title=""></div>
			<div class="errorHeading"><?=Loc::getMessage("ORDER_ERROR_3_HEADING")?></div>
			<a href="#" class="basketErrorClose errorClose"><span class="errorCloseLink"></span></a>
			<div class="errorMessage"><?=Loc::getMessage("ORDER_ERROR_3")?></div>
			<a href="#" class="basketErrorClose btn-simple btn-small"><?=Loc::getMessage("ORDER_CLOSE")?></a>
		</div>
	</div>
<?else:?>
	<div id="empty">
		<div class="emptyWrapper">
			<div class="pictureContainer">
				<img src="<?=SITE_TEMPLATE_PATH?>/images/emptyFolder.svg" alt="<?=Loc::getMessage("EMPTY_HEADING")?>" class="emptyImg">
			</div>
			<div class="info">
				<h3><?=Loc::getMessage("EMPTY_HEADING")?></h3>
				<p><?=Loc::getMessage("EMPTY_TEXT")?></p>
				<a href="<?=SITE_DIR?>" class="back"><?=Loc::getMessage("MAIN_PAGE")?></a>
			</div>
		</div>
		<?$APPLICATION->IncludeComponent("bitrix:menu", "emptyMenu", Array(
			"ROOT_MENU_TYPE" => "left",
				"MENU_CACHE_TYPE" => "N",
				"MENU_CACHE_TIME" => "3600",
				"MENU_CACHE_USE_GROUPS" => "Y",
				"MENU_CACHE_GET_VARS" => "",
				"MAX_LEVEL" => "1",
				"CHILD_MENU_TYPE" => "left",
				"USE_EXT" => "Y",
				"DELAY" => "N",
				"ALLOW_MULTI_SELECT" => "N",
			),
			false
		);?>
	</div>
<?endif;?>

<script>
	var basketLang = {
		"max-quantity": '<?=Loc::getMessage("MAX_QUANTITY")?>',
		"empty-paysystems": '<?=Loc::getMessage("EMPTY_PAYSYSTEMS")?>',
		"empty-deliveries": '<?=Loc::getMessage("EMPTY_DELIVERIES")?>',
	};
	// var ajaxDir = "<?=$componentPath?>";
	var ajaxDir = "<?=$templateFolder?>";
	var ajaxCharset = "<?=LANG_CHARSET?>";
	var siteId = "<?=$component->getSiteId()?>";
	var siteCurrency = <?=\Bitrix\Main\Web\Json::encode($arResult["CURRENCY"]);?>;
	var basketParams = <?=\Bitrix\Main\Web\Json::encode(\DigitalWeb\Basket::clearParams($arParams));?>;
	var basketTemplatePath = "<?=$templateFolder?>";
	var maskedUse = "<?=$arParams["USE_MASKED"]?>";
	var sendSms = "<?=$arParams["SEND_SMS_MESSAGE"]?>";
	var maskedFormat = "<?=$arParams["MASKED_FORMAT"]?>";
</script>
