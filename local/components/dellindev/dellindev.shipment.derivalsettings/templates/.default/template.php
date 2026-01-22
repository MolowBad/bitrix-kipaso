<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;
use Sale\Handlers\Delivery\DellinHandler;
use Bitrix\Sale\Internals\Input;
Loc::loadMessages(__FILE__);



if(isset($_REQUEST['ID']) || !empty($_REQUEST['ID'])) {
//    throw new \Bitrix\Main\ArgumentNullException('ID');

    $configParams = \Bitrix\Sale\Delivery\Services\Manager::getById((int)$_REQUEST['ID']);
    $dataValue = $configParams['CONFIG']['DERIVAL'];
}
//echo '<pre>';
//var_dump($configParams);
//echo '</pre>';
//die();

//var_dump($arParams['CONFIG']['DERIVAL']);

function makeListTimeToWorkAndBreak($value){
    $html = '';
    $ttt = ($value == "0")?'selected':'';
    $html.= '<option value ="0" '.$ttt.' >'.GetMessage("DELLINDEV_SHIPMENT_CHOOSE_TIME").'</option>';
    for($n = 0;$n<=24;$n++) {
        if ($n == 24 || $n == 0) {
            $changed = ($value == '23:59')?'selected':'';
            $html.=  '<option value = "23:59" '.$changed.'>00:00</option>';
        } else {
            $valueAndKey = (($n < 10) ? "0" : "") . $n . ":00";
            $changed = ($value == $valueAndKey)?'selected': '';
            $html.= '<option value ='.$valueAndKey.' '.$changed.'>'
                .$valueAndKey.'</option>';

        }
    }


    return $html;
}

//unset($dataValue["REQUIREMENTSTRANSPORT"]);

$requirements = json_decode($dataValue["REQUIREMENTSTRANSPORT"]);



function displaySpecOnRender($dataValue){

   return (isset($dataValue['LOADING_TYPE']) && ($dataValue['LOADING_TYPE'] == 'SPEC')) ? ""  : "display:none";

}



?>

<tr class="heading">
    <td colspan="2"><?= Loc::getMessage("DELLINDEV_INPUT_POINT_DERIVAL") ?><br/>
</tr>

<tr>

    <td width="20%"><?= Loc::getMessage("DELLINDEV_INPUT_CITY") ?></td>
    <td width="40%" id="b_sale_hndl_dlv_add_loc_count">
        <input type="text" name="CONFIG[DERIVAL][CITY_NAME]"
               onkeyup="BX.Sale.Dellin.DerivalSettings.selectCity()"
               onblur="BX.Sale.Dellin.DerivalSettings.onblurAutocomplete()"
               id="cityField"
               value='<? echo $dataValue['CITY_NAME'] ?>'
        >
    </td>
</tr>
<tr>

    <td width="20%"><?= Loc::getMessage("DELLINDEV_KLADR_CODE_CITY_DERIVAL") ?></td>
    <td width="40%" id="b_sale_hndl_dlv_add_loc_count">
        <input type="text" name="CONFIG[DERIVAL][CITY_KLADR]"
               readonly
               value='<? echo $dataValue['CITY_KLADR'] ?>'
        >
    </td>
</tr>

<!--<tr class="heading">-->
<!--    <td colspan="2">--><?//= Loc::getMessage("DELLINDEV_SELECTED_SCHEME") ?><!--<br/>-->
<!--        --><?//= Loc::getMessage("DELLINDEV_SELECTED_SCHEME_INFO") ?><!--</td>-->
<!--</tr>-->


<tr>
    <td width="40%"><?= Loc::getMessage("DELLINDEV_SCHEME_DERIVAL") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <select name="CONFIG[DERIVAL][GOODSLOADING]"
                onchange="BX.Sale.Dellin.DerivalSettings.displayFieldsOnMethod()">
            <option value="0"
                <? ($dataValue['GOODSLOADING'] == '0')?print('selected'):''; ?>>
                <?= Loc::getMessage("DELLINDEV_FROM_TERMINAL") ?></option>
            <option value="1"
                <? ($dataValue['GOODSLOADING'] == '1')?print('selected'):''; ?>>
                <?= Loc::getMessage("DELLINDEV_FROM_ADDRESS") ?></option>
        </select>
    </td>
</tr>

<tr id="address_derival">

	<td width="40%"><?= Loc::getMessage("DELLINDEV_ADDRESS_DERIVAL") ?><br/>
        <small><?= Loc::getMessage("DELLINDEV_DESCRIPTION_ADDRESS_VALUE_1") ?><br/><?= Loc::getMessage("DELLINDEV_ADDRESS_VALUE_DESC") ?></small></td>
	<td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <input type="text" style="width:80%;" name="CONFIG[DERIVAL][ADDRESS]"
               value='<? echo $dataValue['ADDRESS'] ?>'
                >
    </td>
</tr>


<tr id="terminal_derival">
    <td width="40%"><?= Loc::getMessage("DELLINDEV_TERMINAL_DERIVAL") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <select name="CONFIG[DERIVAL][TERMINAL_ID]" data-value = <? echo $dataValue["TERMINAL_ID"]?>>
            <option value="0" ><?= Loc::getMessage("DELLINDEV_TERMINAL_NOT_SELECTED") ?></option>
        </select>
    </td>
</tr>

<tbody id="address_additional" style=<?($dataValue['GOODSLOADING'] == '0')?print('display:none;'):'';?>>
    <tr class="heading">
        <td colspan="2"><?= Loc::getMessage("DELLINDEV_WORK_BREAK_START_END_4_HOURS") ?></td>
    </tr>

    <tr>
        <td width="40%"><?= Loc::getMessage("DELLINDEV_WORK_START") ?></td>
        <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
            <select name="CONFIG[DERIVAL][WORKTIMESTART]">
                <? echo makeListTimeToWorkAndBreak($dataValue['WORKTIMESTART']) ?>
            </select>
        </td>
    </tr>

    <tr>
        <td width="40%"><?= Loc::getMessage("DELLINDEV_WORK_BREAK_START") ?></td>
        <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
            <select name="CONFIG[DERIVAL][BREAKSTART]">
                <? echo makeListTimeToWorkAndBreak($dataValue['BREAKSTART']) ?>
            </select>
        </td>
    </tr>

    <tr>
        <td width="40%"><?= Loc::getMessage("DELLINDEV_WORK_BREAK_END") ?></td>
        <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
            <select name="CONFIG[DERIVAL][BREAKEND]">
                <? echo makeListTimeToWorkAndBreak($dataValue['BREAKEND']) ?>
            </select>
        </td>
    </tr>


    <tr>
        <td width="40%"><?= Loc::getMessage("DELLINDEV_WORK_END") ?></td>
        <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
            <select name="CONFIG[DERIVAL][WORKTIMEEND]">
                <? echo makeListTimeToWorkAndBreak($dataValue['WORKTIMEEND']) ?>
            </select>
        </td>
    </tr>


    <tr class="heading">
        <td colspan="2"><?= Loc::getMessage("DELLINDEV_REQUIREMENTS_TRANSPORT_LOADING") ?><br/>
           </td>
    </tr>

    <tr>
        <td width="40%"><?= Loc::getMessage("DELLINDEV_LOADING_TYPE") ?></td>
        <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
            <select name="CONFIG[DERIVAL][LOADING_TYPE]">
                <option value="BACK"
                    <? ($dataValue['LOADING_TYPE'] == 'BACK')?print('selected'):''; ?>>
                    <?= Loc::getMessage("DELLINDEV_BACK_LOADING") ?></option>
                <option value="SPEC"
                    <? ($dataValue['LOADING_TYPE'] == 'SPEC')?
                        print('selected'):''; ?>><?= Loc::getMessage("DELLINDEV_SPEC_REQUIREMENT_TRANSPORT")?></option>
            </select>
            <input name="CONFIG[DERIVAL][REQUIREMENTSTRANSPORT]"
                   style="display:none"
                   readonly
                   type="text"
                   id="stateKeeper"
                   value='<?= $dataValue["REQUIREMENTSTRANSPORT"] ?>'>

        </td>
    </tr>

    <tr id="req"  style ='<?= displaySpecOnRender($dataValue) ?>'>

        <td width="40%"><?= Loc::getMessage("DELLINDEV_SPEC_REQUIREMENT_TRANSPORT") ?></td>
        <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
            <div name="CONFIG[DERIVAL][LOADING_TRANSPORT_REQUIREMENTS]"></div>
        </td>
    </tr>

<!--    <tr>-->
<!---->
<!--        <td width="40%"><label for="loadingWork">--><?//= Loc::getMessage("DELLINDEV_CARGO_UPPER_WORK") ?><!--</label></td>-->
<!--        <td width="60%" id="b_sale_hndl_dlv_add_loc_count">-->
<!--            <input id="loadingWork"-->
<!--                   type="checkbox"-->
<!--                   value="0x88b6f23b70b15e51480587ec9fb77188"-->
<!--            --><?//= ($requirements->{'0x88b6f23b70b15e51480587ec9fb77188'})? 'checked' : "";?><!---->
<!--        </td>-->
<!--    </tr>-->
</tbody>

<tr class="heading">
    <td colspan="2"><?= Loc::getMessage("DELLINDEV_PARAM_DELIVERY_DELAY") ?></td>
</tr>

<tr>
    <td width="40%"><?= Loc::getMessage("DELLINDEV_DELAY_DAY") ?></td>
    <td width="60%" id="b_sale_hndl_dlv_add_loc_count">
        <input type="text" name="CONFIG[CARGO][DAYDELAY]"
               value='<? echo $configParams['CONFIG']['CARGO']['DAYDELAY'] ?>'
        >
        <span><?= Loc::getMessage("DELLINDEV_DAYS") ?></span>
    </td>
</tr>





<script type="text/javascript">

	BX.message({
		"API_KEY_EMPTY": "<?=Loc::getMessage("API_KEY_EMPTY")?>",
		"PROCESSING": "<?=Loc::getMessage("SALE_LOCATION_MAP_LOC_MAPPING")?>",
		"EMPTY_DERIVAL_SEARCH": "<?=Loc::getMessage("EMPTY_DERIVAL_SEARCH")?>",
		"EMPTY_KLADR_SEARCH": "<?=Loc::getMessage("EMPTY_KLADR_SEARCH")?>",
		"EMPTY_API_SEARCH": "<?=Loc::getMessage("EMPTY_API_SEARCH")?>",
        "SIDE_LOADING": "<?=Loc::getMessage("SIDE_LOADING")?>",
        "UPPER_LOADING": "<?=Loc::getMessage("UPPER_LOADING")?>",
        "PALLET_LIFT": "<?=Loc::getMessage("PALLET_LIFT")?>",
        "MANIPULATOR": "<?=Loc::getMessage("MANIPULATOR")?>",
        "REQUIRED_OPEN_CAR": "<?=Loc::getMessage("REQUIRED_OPEN_CAR")?>",
        "UN_CANOPY": "<?=Loc::getMessage("UN_CANOPY")?>",
        "FIND_CITY_KLADR": "<?=Loc::getMessage("FIND_CITY_KLADR")?>",
        "BUTTON_CLOSE": "<?=Loc::getMessage("BUTTON_CLOSE")?>",
        "BUTTON_SELECT": "<?=Loc::getMessage("BUTTON_SELECT")?>"
	});
    BX.Sale.Dellin.DerivalSettings.ajaxUrl = "<?=$componentPath.'/ajax.php'?>";
		BX.Sale.Dellin.DerivalSettings.serviceAjaxClass = "<?=CUtil::JSEscape($arParams['AJAX_SERVICE_CLASS'])?>";
	BX.ready(function() {
        let element = document.querySelector('[name="CONFIG[MAIN][PASSWORD]"]');
            element.type = 'password';
        BX.Sale.Dellin.DerivalSettings.ajaxUrl = "<?=$componentPath.'/ajax.php'?>";
		BX.Sale.Dellin.DerivalSettings.serviceAjaxClass = "<?=CUtil::JSEscape($arParams['AJAX_SERVICE_CLASS'])?>";
		//BX.Sale.Dellin.DerivalSettings.getTerminalDerival();
		BX.Sale.Dellin.DerivalSettings.displayFieldsOnMethod();

        //����� ���������� � ���������.
        //������� ��� �����.
		let messageBlock = document.querySelector('#logsMessage');

		let linkLogs = document.createElement('a');
		    linkLogs.innerHTML = '/bitrix/modules/dellindev.shipment/logs/';
		    linkLogs.target = '_blank';
		    linkLogs.href = '/bitrix/admin/fileman_admin.php?PAGEN_1=1&SIZEN_1=20&lang=ru&site=s1&path=%2Fbitrix%2Fmodules%2Fdellindev.shipment%2Flogs&show_perms_for=0';
            messageBlock.append(linkLogs);


        //��������� ��� ��������� �����
        //

        let blockSpec = document.querySelector('#req');
		let blockCheckbox = document.createElement('div');
		    blockCheckbox.id = 'requirements';

        let selectReq = document.querySelector('[name="CONFIG[DERIVAL][LOADING_TRANSPORT_REQUIREMENTS]"]');
            selectReq.after(blockCheckbox);

        let stateKeeper = document.querySelector('[name="CONFIG[DERIVAL][REQUIREMENTSTRANSPORT]"]');

        let state = <?=(!empty($dataValue['REQUIREMENTSTRANSPORT']))?$dataValue['REQUIREMENTSTRANSPORT']: "{}"; ?>

		const mappingRequirements = [

            {name: BX.message("SIDE_LOADING"),      value:'0xb83b7589658a3851440a853325d1bf69'},
            {name: BX.message("UPPER_LOADING"),     value:'0xabb9c63c596b08f94c3664c930e77778'},
            {name: BX.message("PALLET_LIFT"),       value:'0x92fce2284f000b0241dad7c2e88b1655'},
            {name: BX.message("MANIPULATOR"),       value:'0x88f93a2c37f106d94ff9f7ada8efe886'},
            {name: BX.message("REQUIRED_OPEN_CAR"), value:'0x9951e0ff97188f6b4b1b153dfde3cfec'},
            {name: BX.message("UN_CANOPY"),         value:'0x818e8ff1eda1abc349318a478659af08'}

        ];

		let createCheckbox = (obj, parent) => {

		    let input = document.createElement('input');
		        input.type = 'checkbox';
		        input.id = obj.value;
		        input.value = obj.value;
		        input.name = 'requirements';

		        if(state[obj.value]){
                    input.checked = true;
                }

		    let label = document.createElement('label');
		        label.for = obj.value;
		        label.innerHTML = obj.name;

            let br = document.createElement('br');

            input.addEventListener('change', () => {
                console.log('Changed', input.name);
                if(input.checked) {
                    state[input.value] = true;
                    stateKeeper.value = JSON.stringify(state);
                    console.log(stateKeeper.value);
                } else {
                    state[input.value] = false;
                    stateKeeper.value = JSON.stringify(state);
                    console.log(stateKeeper.value);
                }
            });
            parent.appendChild(input);
            parent.appendChild(label);
            parent.appendChild(br);
        };

		mappingRequirements.map((item)=>{
            createCheckbox(item, blockCheckbox);
        });

		let typeField = document.querySelector('[name="CONFIG[DERIVAL][LOADING_TYPE]"]');
		    typeField.addEventListener('change', () =>{
		        if(typeField.value == 'BACK'){
		            blockSpec.style = 'display:none;';
		            state[typeField.value] = true;
                    stateKeeper.value = JSON.stringify(state);
                    console.log('state', stateKeeper.value);
                } else {
		            blockSpec.style = '';
		            state['BACK'] = false;
                    stateKeeper.value = JSON.stringify(state);
                    console.log('state', stateKeeper.value);
                }
            });

        BX.namespace('BX.Sale.Dellin.SettingStates');

        BX.Sale.Dellin.SettingStates = {
            isLoadingOPF: true,
            isLoadingCountries: true,
            isLoadingTerminals: true,
            isLoadingCounteragents: true,
            checkAllLoad: function ()
            {
                this.isLoading
            }
        }

        const manageTabSaveButton = () => {


            let buttonSave = document.querySelector('input[type="submit"]');

            if(!buttonSave)
            {
                console.warn('ButtonSave is empty');
                return ;
            }

            if(buttonSave.disabled || !buttonSave.disabled )
            {
                buttonSave.className = (buttonSave.disabled)? 'adm-btn-save' : '';
                buttonSave.disabled = !buttonSave.disabled;
            }


        }

        const manageTabApplyButton = () => {

            let buttonApply = document.querySelector('input[name="apply"]');

            if(!buttonApply)
            {
                console.warn('buttonApply is empty');
                return ;
            }

            if(buttonApply.disabled || !buttonApply.disabled)
            {
                buttonApply.disabled = !buttonApply.disabled;
            }
            


        }

        let XMLHttpReqPromise = (method) => 
        {
            return new Promise((resolve, reject)=>{
                method.onload = function() {
                    if (method.status < 200 || method.status >= 300) {
                        reject({request: method});
                    } else {
                        resolve(method);
                    }
                }
            });
        }
        
        BX.showWait();
        manageTabApplyButton();
        manageTabSaveButton();

        //append job in webapi
        setTimeout(()=>{

            //console.log('terminals',BX.Sale.Dellin.DerivalSettings.getTerminalDerival());
            //console.log('opf', BX.Sale.Dellin.YuriSettings.getOpfData());
            //console.log('counteragents', BX.Sale.Dellin.YuriSettings.getCounterAgents());

            let PromiseAllAjaxRequests = Promise.allSettled([
                XMLHttpReqPromise(BX.Sale.Dellin.DerivalSettings.getTerminalDerival()),
                //BX.Sale.Dellin.DerivalSettings.getTerminalDerival,
                XMLHttpReqPromise(BX.Sale.Dellin.YuriSettings.getCounterAgents()),
              //  BX.Sale.Dellin.YuriSettings.getOpfData,
                XMLHttpReqPromise(BX.Sale.Dellin.YuriSettings.getOpfData())
               // BX.Sale.Dellin.YuriSettings.getCounterAgents    
            ]).then(result => {
                console.log('Loaded data');
                BX.closeWait();
                manageTabApplyButton();
                manageTabSaveButton();
            },
                error => console.error('Error Promise'));
        }, 0)

        

        

        

        // let loadingWork = document.querySelector('#loadingWork');
		//     loadingWork.addEventListener('change', ()=>{
		//         if(loadingWork.checked){
        //             state[loadingWork.value] = true;
        //             stateKeeper.value = JSON.stringify(state);
        //             console.log(stateKeeper.value);
        //         } else {
        //             state[loadingWork.value] = false;
        //             stateKeeper.value = JSON.stringify(state);
        //             console.log(stateKeeper.value);
        //         }
        //     });

    });


</script>
