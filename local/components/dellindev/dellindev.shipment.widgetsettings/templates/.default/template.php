<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);







?>

<tr class="heading">
    <td colspan="2"><?= GetMessage("DELLINDEV_SHIPMENT_KLUC_DLA") ?><br/>
        <small><?=GetMessage("DELLINDEV_SHIPMENT_PRIMECANIE_POLE_KL")?><a href="/bitrix/admin/settings.php?lang=ru&mid=fileman&mid_menu=1">"<?=GetMessage("DELLINDEV_SHIPMENT_UPRAVLENIE_STRUKTURO")?></a>
        <br/> <?=GetMessage("DELLINDEV_SHIPMENT_ESLI_KLUC_ANDEKSA_NE")?></small></td>
</tr>
<tr>

    <td width="40%"><?= GetMessage("DELLINDEV_SHIPMENT_KLUC_DLA") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <input type="text" readonly
               value='<? echo $arParams['yandex_map_api_key'] ?>'
        >
    </td>

</tr>

<tr class="heading">
    <td colspan="2"><?= GetMessage("DELLINDEV_SHIPMENT_DRUGIE_NASTROYKI") ?><br/></td>
</tr>
<tr>
    <td width="40%"><?= GetMessage("DELLINDEV_SHIPMENT_PREDSTAVLENIE_VIDJET") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
    <select name="CONFIG[WIDGET][VIEW_TYPE]">
            <option value="0"
                <? ($arParams['WIDGET']['VIEW_TYPE'] == '0')?print('selected'):''; ?>><?= GetMessage("DELLINDEV_SHIPMENT_ISPOLQZOVATQ_STAROE") ?></option>
            <option value="1"
                <? ($arParams['WIDGET']['VIEW_TYPE'] == '1')?print('selected'):''; ?>><?= GetMessage("DELLINDEV_SHIPMENT_ISPOLQZOVATQ_NOVOE_P") ?></option>
            <option value="2"
                <? ($arParams['WIDGET']['VIEW_TYPE'] == '2')?print('selected'):''; ?>><?= GetMessage("DELLINDEV_SHIPMENT_ISPOLQZOVATQ_NOVOE_P1") ?></option>
    </select>
    </td>

</tr>


<script type="text/javascript">

	BX.message({
		"SALE_LOCATION_MAP_CLOSE": "<?=Loc::getMessage("SALE_LOCATION_MAP_CLOSE")?>",
		"SALE_LOCATION_MAP_LOC_MAPPING": "<?=Loc::getMessage("SALE_LOCATION_MAP_LOC_MAPPING")?>",
		"SALE_LOCATION_MAP_CANCEL": "<?=Loc::getMessage("SALE_LOCATION_MAP_CANCEL")?>",
		"SALE_LOCATION_MAP_PREPARING": "<?=Loc::getMessage("SALE_LOCATION_MAP_PREPARING")?>",
		"SALE_LOCATION_MAP_LOC_MAPPED": "<?=Loc::getMessage("SALE_LOCATION_MAP_LOC_MAPPED")?>"
	});

	BX.ready(function() {

	});
</script>
