<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<?$this->setFrameMode(true);?>
<?if(!empty($arResult["ITEMS"])):?>
	<div class="advantagesDetail">
		<div class="advantagesDetailCarousel">
			<div class="advantagesItems slideBox">
				<?foreach($arResult["ITEMS"] as $nextItem):?>
					<div class="advantagesItem slideItem">
						<?if(!empty($nextItem["DETAIL_PICTURE"])):?>
							<div class="advantagesPicture"><img src="<?=$nextItem["DETAIL_PICTURE"]["src"]?>" alt="<?=$nextItem["NAME"]?>" title="<?=$nextItem["NAME"]?>"></div>
						<?endif;?>
						<div class="advantagesName"><?=$nextItem["NAME"]?></div>
					</div>
				<?endforeach;?>
			</div>
		</div>
	</div>
	<script>
		$(".advantagesDetailCarousel").dwCarousel({
			countElement: 4,
			resizeElement: true,
			resizeAutoParams: {
				2560: 4,
				1500: 4,
				1200: 3,
				1084: 4,
				920: 3,
				700: 2.5,
				600: 2.2,
				530: 2.1,
				500: 2,
				450: 1.7,
				400: 1.5,
				350: 1.3
			}
		});
	</script>
<?endif;?>