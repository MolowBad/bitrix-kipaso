<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<?$this->setFrameMode(true);?>
<?if(!empty($arResult["LOCATIONS"])):?>
	<div class="locationSwitch">
		<?foreach($arResult["LOCATIONS"] as $nextLocation):?>
			<div class="locationSwitchItem">
				<a href="#" data-id="<?=$nextLocation["ID"]?>" data-code="<?=$nextLocation["CODE"]?>" data-name="<?=$nextLocation["NAME"]?>" data-path="<?=$nextLocation["PATH"]?>" class="locationSwitchLink"><?=$nextLocation["PATH"]?></a>
			</div>
		<?endforeach;?>
	</div>
<?endif;?>