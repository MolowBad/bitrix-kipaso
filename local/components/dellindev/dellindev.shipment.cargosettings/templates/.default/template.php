<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
//���������

if(isset($_REQUEST['ID']) || !empty($_REQUEST['ID'])) {

    $configParams = \Bitrix\Sale\Delivery\Services\Manager::getById((int)$_REQUEST['ID'])['CONFIG'];

}




?>



<tr class="heading">
    <td colspan="2"><?= Loc::getMessage("DELLINDEV_SELECT_SCHEME_DELIVERY") ?></td>
</tr>

<tr>
    <td width="40%"><?= Loc::getMessage("DELLINDEV_SCHEME_DELIVERY") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <select name="CONFIG[ARRIVAL][GOODSLOADING]">
            <option value="0"
                <? ($configParams['ARRIVAL']['GOODSLOADING'] == '0')?print('selected'):''; ?>><?= Loc::getMessage("DELLINDEV_TO_TERMINAL") ?></option>
            <option value="1"
                <? ($configParams['ARRIVAL']['GOODSLOADING'] == '1')?print('selected'):''; ?>><?= Loc::getMessage("DELLINDEV_TO_ADDRESS") ?></option>
        </select>
    </td>
</tr>

<tr class="heading">
    <td colspan="2"><?= Loc::getMessage("DELLINDEV_PARAM_SMALL_CARGO") ?></td>
</tr>

<tr>
    <td width="40%"><?= Loc::getMessage("DELLINDEV_USE_CALC_HOW_SMALL_CARGO") ?><br/>
        <small><?= Loc::getMessage("DELLINDEV_CONDITION_USE_SMALL_CARGO") ?></small></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <select name="CONFIG[ARRIVAL][IS_SMALL_GOODS]">
            <option value="0"
                <? ($configParams["ARRIVAL"]["IS_SMALL_GOODS"] == '0')?print('selected'):''; ?>>
                <?= Loc::getMessage("DELLINDEV_YES_CALC") ?></option>
            <option value="1"
                <? ($configParams["ARRIVAL"]["IS_SMALL_GOODS"] == '1')?print('selected'):''; ?>>
                <?= Loc::getMessage("DELLINDEV_NO_USE_SMALL_CARGO") ?></option>
        </select>
    </td>
</tr>

<tr class="heading">
    <td colspan="2"><?= Loc::getMessage("DELLINDEV_PARAM_INSURANCE") ?></td>
</tr>

<tr>
    <td width="40%"><?= Loc::getMessage("DELLINDEV_INSURANCE_DECLARE_PRICE") ?><br/>
       <small><?= Loc::getMessage("DELLINDEV_INSURANCE_DECLARE_PRICE_NOTE") ?></small>	</td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <select name="CONFIG[ARRIVAL][IS_INSURANCE_GOODS_WITH_DECLARE_PRICE]">
            <option value="0"
                <? ($configParams['ARRIVAL']['IS_INSURANCE_GOODS_WITH_DECLARE_PRICE'] == '0')?print('selected'):''; ?>>
                <?= Loc::getMessage("DELLINDEV_YES_CALC_INSURANCE") ?></option>
            <option value="1"
                <? ($configParams['ARRIVAL']['IS_INSURANCE_GOODS_WITH_DECLARE_PRICE'] == '1')?print('selected'):''; ?>>
                <?= Loc::getMessage("DELLINDEV_NO_CALC_INSURANCE") ?></option>
        </select>
    </td>
</tr>

<tr class="heading">
    <td colspan="2"><?= Loc::getMessage("DELLINDEV_PARAM_GROUP_CARGO_PLACE") ?></td>
</tr>


<tr>
    <td width="40%"><?= Loc::getMessage("DELLINDEV_GROUP_PRODUCTS_IN_CARGO_PLACE") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <select name="CONFIG[CARGO][LOADING_GROUPING_OF_GOODS]">
            <option  value="ONE_CARGO_SPACE"
                <? ($configParams['CARGO']['LOADING_GROUPING_OF_GOODS'] == 'ONE_CARGO_SPACE')?print('selected'):''; ?>>
                <?= Loc::getMessage("DELLINDEV_CALC_ALL_PRODUCT_AS_ONE_PLACE") ?></option>
            <option value="SEPARATED_CARGO_SPACE"
                <? ($configParams['CARGO']['LOADING_GROUPING_OF_GOODS'] == 'SEPARATED_CARGO_SPACE')?
                    print('selected'):''; ?>><?= Loc::getMessage("DELLINDEV_SEPARATED_CARGO_SPACE") ?></option>
            <option value="SINGLE_ITEM_SINGLE_SPACE"
                <? ($configParams['CARGO']['LOADING_GROUPING_OF_GOODS'] == 'SINGLE_ITEM_SINGLE_SPACE')?
                    print('selected'):''; ?>><?= Loc::getMessage("DELLINDEV_SINGLE_ITEM_SINGLE_SPACE") ?></option>
        </select>
    </td>
</tr>



<tr style="display:none;" class="heading">
    <td colspan="2"><?= Loc::getMessage("DELLINDEV_REQUIREMENTS_FOR_TRANSPORT_LOADING") ?><br/>
    </td>
</tr>

<tr style="display: none;">
    <td width="40%"><?= Loc::getMessage("DELLINDEV_TYPE_UNLOADING") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <select name="CONFIG[ARRIVAL][UNLOADING_TYPE]">
            <option value="NULL"
                <? ($configParams['ARRIVAL']['UNLOADING_TYPE'] == 'NULL')?print('selected'):''; ?>>
                <?= Loc::getMessage("DELLINDEV_NOT_REQUIREMENT") ?></option>
            <option value="0xb83b7589658a3851440a853325d1bf69"
                <? ($configParams['ARRIVAL']['UNLOADING_TYPE'] == '0xb83b7589658a3851440a853325d1bf69')?
                    print('selected'):''; ?>><?= Loc::getMessage("DELLINDEV_BACK_UNLOADING") ?></option>
            <option value="0xabb9c63c596b08f94c3664c930e77778"
                <? ($configParams['ARRIVAL']['UNLOADING_TYPE'] == '0xabb9c63c596b08f94c3664c930e77778')?
                    print('selected'):''; ?>><?= Loc::getMessage("DELLINDEV_UPPER_UNLOADING") ?></option>
        </select>
    </td>
</tr>

<tr  style="display: none;">
    <td width="40%"><?= Loc::getMessage("DELLINDEV_SPEC_REQUIREMENT_FOR_TRANSPORT") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <select name="CONFIG[ARRIVAL][UNLOADING_TRANSPORT_REQUIREMENTS]">
            <option value="NULL"
                <? ($configParams['ARRIVAL']['UNLOADING_TRANSPORT_REQUIREMENTS'] == 'NULL')?print('selected'):''; ?>>
                <?= Loc::getMessage("DELLINDEV_NOT_REQUIREMENT") ?></option>
            <option value="0x9951e0ff97188f6b4b1b153dfde3cfec"
                <? ($configParams['ARRIVAL']['UNLOADING_TRANSPORT_REQUIREMENTS'] ==
                    '0x9951e0ff97188f6b4b1b153dfde3cfec')?
                    print('selected'):''; ?>><?= Loc::getMessage("DELLINDEV_OPEN_CAR") ?></option>
            <option value="0x818e8ff1eda1abc349318a478659af08"
                <? ($configParams['ARRIVAL']['UNLOADING_TRANSPORT_REQUIREMENTS'] ==
                    '0x818e8ff1eda1abc349318a478659af08')?
                    print('selected'):''; ?>><?= Loc::getMessage("DELLINDEV_REQUIREMENT_UN_CANOPY") ?></option>
        </select>
    </td>
</tr>

<tr style="display: none;">
    <td width="40%"><?= Loc::getMessage("DELLINDEV_ADITIONAL_EQUPMENT") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <select name="CONFIG[ARRIVAL][UNLOADING_ADDITIONAL_EQUIPMENT]">
            <option value="NULL"
                <? ($configParams['ARRIVAL']['UNLOADING_ADDITIONAL_EQUIPMENT'] == 'NULL')?print('selected'):''; ?>>
                <?= Loc::getMessage("DELLINDEV_NOT_REQUIREMENT") ?></option>
            <option value="0x92fce2284f000b0241dad7c2e88b1655"
                <? ($configParams['ARRIVAL']['UNLOADING_ADDITIONAL_EQUIPMENT'] ==
                    '0x92fce2284f000b0241dad7c2e88b1655')?
                    print('selected'):''; ?>><?= Loc::getMessage("DELLINDEV_TAIL_LIFT") ?></option>
            <option value="0x88f93a2c37f106d94ff9f7ada8efe886"
                <? ($configParams['ARRIVAL']['UNLOADING_ADDITIONAL_EQUIPMENT'] ==
                    '0x88f93a2c37f106d94ff9f7ada8efe886')?
                    print('selected'):''; ?>><?= Loc::getMessage("DELLINDEV_MANIPULATOR") ?></option>
        </select>
    </td>
</tr>

<tr class="heading">
    <td colspan="2"><?= Loc::getMessage("DELLINDEV_DEFAULT_PRODUCT_PARAMS") ?></td>
</tr>

<tr>
    <td width="40%"><?= Loc::getMessage("DELLINDEV_USE_DEFAULT_PARAMS_PRODUCTS") ?><br/>
        <small><?= Loc::getMessage("DELLINDEV_USE_DEFAULT_PARAMS_PRODUCTS_INFO") ?></small></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <select name="CONFIG[CARGO][DEFAULT_PARAMS]">
            <option value="0"
                <? ($configParams['CARGO']['DEFAULT_PARAMS'] == '0')?print('selected'):''; ?>>
                <?= Loc::getMessage("DELLINDEV_YES_USE_PARAMS") ?></option>
            <option value="1"
                <? ($configParams['CARGO']['DEFAULT_PARAMS'] == '1')?print('selected'):''; ?>>
                <?= Loc::getMessage("DELLINDEV_NO_USE_PARAMS") ?></option>
        </select>
    </td>
</tr>

<tr>

    <td width="40%"><?= Loc::getMessage("DELLINDEV_LENGHT_MM") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <input type="text" name="CONFIG[PRODUCT][LENGTH]"
               value='<? echo $configParams['PRODUCT']['LENGTH'] ?>'
        >
    </td>

</tr>

<tr>

    <td width="40%"><?= Loc::getMessage("DELLINDEV_WIDTH_MM") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <input type="text" name="CONFIG[PRODUCT][WIDTH]"
               value='<? echo $configParams['PRODUCT']['WIDTH'] ?>'
        >
    </td>

</tr>

<tr>

    <td width="40%"><?= Loc::getMessage("DELLINDEV_HEIGHT_MM") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <input type="text" name="CONFIG[PRODUCT][HEIGHT]"
               value='<? echo $configParams['PRODUCT']['HEIGHT'] ?>'
        >
    </td>

</tr>

<tr>

    <td width="40%"><?= Loc::getMessage("DELLINDEV_MASS_G") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <input type="text" name="CONFIG[PRODUCT][WEIGHT]"
               value='<? echo $configParams['PRODUCT']['WEIGHT'] ?>'
        >
    </td>

</tr>


<tr class="heading">
    <td colspan="2"><?= Loc::getMessage("DELLINDEV_FREIGHT_TYPES_HEADER") ?><br/>
</tr>

<tr>

    <td width="20%"><?= Loc::getMessage("DELLINDEV_INPUT_FREIGHT_TYPES") ?></td>
    <td width="40%" id="b_sale_hndl_dlv_add_loc_count">
        <input type="text" name="CONFIG[CARGO][FREIGHT_TYPE_NAME]"
               onkeyup="BX.Sale.Dellin.CargoSettings.selectFrieghtType()"
               onblur="BX.Sale.Dellin.CargoSettings.onblurAutocomplete()"
               id="FrieghtTypeUid"
               value='<? echo $configParams['CARGO']['FREIGHT_TYPE_NAME'] ?>'
        >
    </td>
</tr>
<tr>

    <td width="20%"><?= Loc::getMessage("DELLINDEV_FREIGHT_TYPES_UID") ?></td>
    <td width="40%" id="b_sale_hndl_dlv_add_loc_count">
        <input type="text" name="CONFIG[CARGO][FREIGHT_TYPE_UID]"
               readonly
               value='<? echo $configParams['CARGO']['FREIGHT_TYPE_UID'] ?>'
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
        BX.namespace('BX.Sale.Dellin.CargoSettings');

        BX.Sale.Dellin.CargoSettings =
        {
            ajaxUrl: "",
            interruptFlag: false,
            requestFlag: false,
            serviceLocationClass: "",
            isError: false,
            viewMsg: '',// пїЅпїЅпїЅпїЅ пїЅпїЅпїЅ-пїЅпїЅ пїЅпїЅ пїЅпїЅпїЅпїЅпїЅпїЅ пїЅпїЅпїЅпїЅпїЅпїЅпїЅпїЅпїЅ, пїЅпїЅпїЅпїЅпїЅпїЅпїЅпїЅпїЅ пїЅпїЅпїЅпїЅпїЅ пїЅпїЅпїЅпїЅпїЅпїЅпїЅпїЅпїЅ
            errorMsg: null,
            inputDisabled: false,
            cityKladr: null,
            isOpen: false,

            getAppkey: function(){
                return document.querySelector('[name="CONFIG[MAIN][APIKEY]"]')
            },
            selectFrieghtType: function () {
                let input = document.querySelector('#FrieghtTypeUid');
                if(input.value.length > 3){
                    let postData = {
                        sessid: BX.bitrix_sessid(),
                        action: 'get_frieght_type',
                        query: input.value,
                        appkey: BX.Sale.Dellin.CargoSettings.getAppkey().value,
                        class: BX.Sale.Dellin.CargoSettings.serviceAjaxClass
                    };


                    BX.ajax({
                        timeout:    300,
                        method:     'POST',
                        dataType:   'json',
                        url:        BX.Sale.Dellin.CargoSettings.ajaxUrl,
                        data:       postData,

                        onsuccess: function(result)
                        {
                            let fields = BX.Sale.Dellin.CargoSettings.getAutoComplete(result);
                            input.after(fields);
                        },

                        onfailure: function(status)
                        {
                            console.log("onfailture", status);
                        }
                    });
                }
            },
            getAutoComplete: function(result){


                //TODO ������� ��� �������.
                BX.Sale.Dellin.CargoSettings.clearAutoComplete();

                let divAutocomplete = document.createElement('div');
                    divAutocomplete.className = 'autocomplete';
                let divRows = document.createElement('div');
                    divRows.className = 'rows';


                    result.LIST.forEach(function (el) {

                    let row = BX.Sale.Dellin.CargoSettings.buildRowAutocompolite(el);

                        divRows.appendChild(row);

                    });

                    console.log(divRows);
                    divAutocomplete.appendChild(divRows);

                    console.log(divAutocomplete);


                    return divAutocomplete;


            },
            buildRowAutocompolite: function (el) {
                let divRow = document.createElement('div');
                    divRow.className = 'autocomplete-row';
                    divRow.dataset.id = el.sqlUID;
                    divRow.innerHTML = el.value+' ['+el.comment+'] ';

                    divRow.addEventListener('click', function () {
                        let fieldFrieghtTypeName = document.querySelector('[name="CONFIG[CARGO][FREIGHT_TYPE_NAME]"]');
                            fieldFrieghtTypeName.value = el.value;
                        let fieldFrieghtTypeUid = document.querySelector('[name="CONFIG[CARGO][FREIGHT_TYPE_UID]"]');
                            fieldFrieghtTypeUid.value = this.dataset.id;
                            
                            BX.Sale.Dellin.CargoSettings.clearAutoComplete();
                    });
                return divRow;
            },
            clearAutoComplete: function(){
                let autoComplete = document.querySelector('.autocomplete');
                if(autoComplete !== null){
                    autoComplete.remove();
                }

            },
            onblurAutocomplete: function(){
                setTimeout(this.clearAutoComplete, 300);
            },
        };



	    BX.Sale.Dellin.CargoSettings.ajaxUrl = "<?=$componentPath.'/ajax.php'?>";
        BX.Sale.Dellin.CargoSettings.serviceAjaxClass = "<?=CUtil::JSEscape($arParams['AJAX_SERVICE_CLASS'])?>";;
        


	});
</script>
