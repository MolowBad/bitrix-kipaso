<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?if(CModule::IncludeModule("currency")):?>
<?$this->setFrameMode(true);?>
	<?if (!empty($arResult)):?>
		<ul id="leftMenu">
			<?foreach($arResult["ITEMS"] as $nextElement):?>
				<li<?if(!empty($nextElement["ELEMENTS"][1]) || !empty($nextElement["ELEMENTS"][2])):?> class="eChild allow-dropdown nested"<?endif;?>>
					<a href="<?=$nextElement["LINK"]?>" class="menuLink<?if ($nextElement["SELECTED"]):?> selected<?endif?>">
						<span class="tb">
								<span class="pc<?if(empty($nextElement["PICTURE"])):?> no-image<?endif;?>">
									<?if(!empty($nextElement["PICTURE"])):?>
										<img src="<?=$nextElement["PICTURE"]["src"]?>" alt="<?=$nextElement["TEXT"]?>" title="<?=$nextElement["TEXT"]?>">
									<?endif;?>
									<span class="back"></span>
								</span>
							<span class="tx">
								<span class="link-title"><?=$nextElement["TEXT"]?></span>
								<span class="dropdown btn-simple btn-micro"></span>
							</span>
						</span>
					</a>
					<?if(!empty($nextElement["ELEMENTS"][1]) || !empty($nextElement["ELEMENTS"][2])):?>
						<div class="drop">
							<?if(!empty($nextElement["ELEMENTS"][1])):?>
								<ul class="menuItems">
									<?foreach($nextElement["ELEMENTS"][1] as $next2Elements):?>
										<li<?if(!empty($next2Elements["ELEMENTS"])):?> class="nested"<?endif;?>>
											<?if(!empty($next2Elements["PICTURE"]["src"])):?>
												<a href="<?=$next2Elements["LINK"]?>" class="menuLink pictureLink">
													<img src="<?=$next2Elements["PICTURE"]["src"]?>" alt="<?=$next2Elements["TEXT"]?>">
												</a>
											<?endif;?>
											<a href="<?=$next2Elements["LINK"]?>" class="menuLink">
												<span class="link-title"><?=$next2Elements["TEXT"]?></span>
												<small><?=$next2Elements["ELEMENT_CNT"]?></small>
												<?if(!empty($next2Elements["ELEMENTS"])):?>
													<span class="dropdown btn-simple btn-micro"></span>
												<?endif;?>
											</a>
										</li>
											<?if(!empty($next2Elements["ELEMENTS"])):?>
												<li class="allow-dropdown">
													<ul class="menuDropItems">
														<?foreach($next2Elements["ELEMENTS"] as $next3Elements):?>
															<li><a href="<?=$next3Elements["LINK"]?>" class="menuLink"><?=$next3Elements["TEXT"]?><small><?=$next3Elements["ELEMENT_CNT"]?></small></a></li>
														<?endforeach;?>
													</ul>
												</li>
											<?endif;?>
									<?endforeach?>
								</ul>
							<?endif;?>
							<?if(!empty($nextElement["ELEMENTS"][2])):?>
								<ul class="menuItems">
									<?foreach($nextElement["ELEMENTS"][2] as $next2Elements):?>
										<li<?if(!empty($next2Elements["ELEMENTS"])):?> class="nested"<?endif;?>>
											<?if(!empty($next2Elements["PICTURE"]["src"])):?>
												<a href="<?=$next2Elements["LINK"]?>" class="menuLink pictureLink">
													<img src="<?=$next2Elements["PICTURE"]["src"]?>" alt="<?=$next2Elements["TEXT"]?>">
												</a>
											<?endif;?>
											<a href="<?=$next2Elements["LINK"]?>" class="menuLink">
												<span class="link-title"><?=$next2Elements["TEXT"]?></span>
												<small><?=$next2Elements["ELEMENT_CNT"]?></small>
												<?if(!empty($next2Elements["ELEMENTS"])):?>
													<span class="dropdown btn-simple btn-micro"></span>
												<?endif;?>
											</a>
										</li>
										<?if(!empty($next2Elements["ELEMENTS"])):?>
											<li class="allow-dropdown">
												<ul class="menuDropItems">
													<?foreach($next2Elements["ELEMENTS"] as $next3Elements):?>
														<li><a href="<?=$next3Elements["LINK"]?>" class="menuLink"><?=$next3Elements["TEXT"]?><small><?=$next3Elements["ELEMENT_CNT"]?></small></a></li>
													<?endforeach;?>
												</ul>
											</li>
										<?endif;?>
									<?endforeach?>
								</ul>
							<?endif;?>
							<?if(!empty($arResult["PRODUCTS"][$nextElement["ID"]])):?>
								<div id="menuSlider_<?=$nextElement["ID"]?>" class="menuSlider">
									<ul class="productList slideBox">
										<?foreach ($arResult["PRODUCTS"][$nextElement["ID"]] as $x => $arElement):?>
											<li>
												<?$APPLICATION->IncludeComponent(
													"dresscode:catalog.item",
													".default",
													array(
														"CACHE_TIME" => $arParams["CACHE_TIME"],
														"CACHE_TYPE" => $arParams["CACHE_TYPE"],
														"HIDE_MEASURES" => $arParams["HIDE_MEASURES"],
														"HIDE_NOT_AVAILABLE" => $arParams["HIDE_NOT_AVAILABLE"],
														"IBLOCK_ID" => $arElement["IBLOCK_ID"],
														"IBLOCK_TYPE" => $arElement["IBLOCK_TYPE"],
														"PRODUCT_ID" => $arElement["ID"],
														"PICTURE_HEIGHT" => "",
														"PICTURE_WIDTH" => "",
														"PRODUCT_PRICE_CODE" => $arParams["PRODUCT_PRICE_CODE"],
														"CONVERT_CURRENCY" => $arParams["CONVERT_CURRENCY"],
														"CURRENCY_ID" => $arParams["CURRENCY_ID"]
													),
													false,
													array("HIDE_ICONS" => "Y")
												);?>
											</li>
										<?endforeach;?>
									</ul>
									<a href="#" class="slideBtnLeft"></a>
									<a href="#" class="slideBtnRight"></a>
								</div>
								<script type="text/javascript">
									$(document).ready(function(){
										$("#menuSlider_<?=$nextElement["ID"]?>").dwSlider({
											speed: 200,
											delay: 5000,
											leftButton: "#menuSlider_<?=$nextElement["ID"]?> .slideBtnLeft",
											rightButton: "#menuSlider_<?=$nextElement["ID"]?> .slideBtnRight",
										});
									});
								</script>
							<?endif;?>
						</div>
					<?endif;?>
				</li>
			<?endforeach;?>
		</ul>
	<?endif;?>
<?endif;?>