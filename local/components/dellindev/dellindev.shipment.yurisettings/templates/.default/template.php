<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);


if(isset($_REQUEST['ID']) || !empty($_REQUEST['ID'])) {

    $configParams = \Bitrix\Sale\Delivery\Services\Manager::getById((int)$_REQUEST['ID']);
    $dataValueYuri = $configParams['CONFIG']['YURI'];
    $dataValuePerson = $configParams['CONFIG']['PERSON'];

}




?>

<tr class="heading">
    <td colspan="2"><?= Loc::getMessage("DELLINDEV_SELECT_COUNTERAGENT") ?><br/>
        <small><?= Loc::getMessage("DELLINDEV_SELECT_COUNTERAGENT_INFO") ?></small></td>
</tr>
<tr>
    <td width="40%"><?= Loc::getMessage("DELLINDEV_COUNTERAGENT") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <select name="CONFIG[YURI][COUNTERAGENT]"
                data-value = <? echo $dataValueYuri["COUNTERAGENT"]?>>
            <option value="0" ><?= Loc::getMessage("DELLINDEV_NOT_SELECT") ?></option>
        </select>
    </td>
</tr>

<tr class="heading">
    <td colspan="2"><?= Loc::getMessage("DELLINDEV_YURI_BLOCK") ?><br/>
       <small><?= Loc::getMessage("DELLINDEV_YURI_BLOCK_INFO") ?></small></td>
</tr>
<tr>

    <td width="40%"><?= Loc::getMessage("DELLINDEV_COUNTRY_DERIVAL") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <select name="CONFIG[YURI][OPF_COUNTRY]" data-value = <? echo $dataValueYuri["OPF_COUNTRY"]?>>
            <option value="0" >
                <?= Loc::getMessage("DELLINDEV_NOT_SELECT") ?></option>
        </select>
    </td>

</tr>

<tr>
    <td width="40%"><?= Loc::getMessage("DELLINDEV_OPF") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <select name="CONFIG[YURI][OPF]" data-value = <? echo $dataValueYuri["OPF"]?>>
            <option value="0" >
                <?= Loc::getMessage("DELLINDEV_NOT_SELECT") ?></option>
        </select>
    </td>
</tr>

<tr>

    <td width="40%"><?= Loc::getMessage("DELLINDEV_YURI_ADDRESS") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count" >
        <input type="text" name="CONFIG[YURI][ADDRESS]" style="width: 80%;"
               value='<? echo $dataValueYuri['ADDRESS'] ?>'
        >
    </td>

</tr>

<tr>

    <td width="40%"><?= Loc::getMessage("DELLINDEV_NAME_COMPANY") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <input type="text" name="CONFIG[YURI][NAME]"
               value='<? echo $dataValueYuri['NAME'] ?>'
        >
    </td>

</tr>


<tr>

    <td width="40%"><?= Loc::getMessage("DELLINDEV_INN_SENDER") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <input type="text" name="CONFIG[YURI][INN]"
               value='<? echo $dataValueYuri['INN'] ?>'
        >
    </td>

</tr>

<tr class="heading">
    <td colspan="2"><?= Loc::getMessage("DELLINDEV_CONTACT_PERSON_BLOCK") ?><br/></td>
</tr>

<tr>

    <td width="40%"><?= Loc::getMessage("DELLINDEV_PERSON_FIO") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <input type="text" name="CONFIG[PERSON][NAME]"
               value='<? echo $dataValuePerson['NAME'] ?>'
        >
    </td>

</tr>

<tr>

    <td width="40%"><?= Loc::getMessage("DELLINDEV_PERSON_PHONE") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <input type="text" name="CONFIG[PERSON][PHONE]"
               value='<? echo $dataValuePerson['PHONE'] ?>'
        >
    </td>

</tr>

<tr>

    <td width="40%"><?= Loc::getMessage("DELLINDEV_PERSON_EMAIL") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <input type="text" name="CONFIG[PERSON][EMAIL]"
               value='<? echo $dataValuePerson['EMAIL'] ?>'
        >
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
        BX.Sale.Dellin.YuriSettings.ajaxUrl = "<?=$componentPath.'/ajax.php'?>";
        BX.Sale.Dellin.YuriSettings.getOpfData();
        BX.Sale.Dellin.YuriSettings.getCounterAgents();
		//BX.Sale.Location.Map.serviceLocationClass = "<?//=CUtil::JSEscape($arParams['EXTERNAL_LOCATION_CLASS'])?>//";
	});
</script>
