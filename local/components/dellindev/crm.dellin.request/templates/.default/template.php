<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

/**
 * Bitrix vars
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");

if(SITE_TEMPLATE_ID === 'bitrix24')
{
    $APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

if (CModule::IncludeModule('bitrix24') && !\Bitrix\Crm\CallList\CallList::isAvailable())
{
    CBitrix24::initLicenseInfoPopupJS();
}

\Bitrix\Main\UI\Extension::load("ui.dialogs.messagebox");
\Bitrix\Main\UI\Extension::load("ui.buttons");

$orderID = $arParams["LOADER_INFO"]['data']['PARAMS']['orderID'];
$shipmentID = $arParams["LOADER_INFO"]['data']['PARAMS']['shipment'];

$shipmentData = \Sale\Handlers\Delivery\DellinBlockAdmin::getAllDataForJS($orderID);


$result['orderID'] = $orderID;
$result['shipmentID'] = $shipmentID;
$result['shipmentData'] = $shipmentData;

$arParams['LOADER_INFO']["DELLIN"] = $result;


$personInfo = $result['shipmentData']['personInfo'];
$shipmentInfo = $result['shipmentData']['shipments'][$shipmentID];

$derivalPhrase = ($shipmentInfo['IS_TERMINAL_DERIVAL'])?Loc::getMessage("DELLINDEV_FROM_TERMINAL") :
    Loc::getMessage("DELLINDEV_FROM_ADDRESS");
$arrivalPhrase = ($shipmentInfo['IS_TERMINAL_ARRIVAL'])?Loc::getMessage("DELLINDEV_TO_TERMINAL") :
    Loc::getMessage("DELLINDEV_TO_ADDRESS");
$phraseAddressDerival = ($shipmentInfo['IS_TERMINAL_DERIVAL'])? Loc::getMessage("DELLINDEV_ADDRESS_TERMINAL_DERIVAL") :
    Loc::getMessage("DELLINDEV_ADDRESS_DERIVAL");
$phraseAddressArival = ($shipmentInfo['IS_TERMINAL_ARRIVAL'])?  Loc::getMessage("DELLINDEV_ADDRESS_TERMINAL_ARRIVAL") :
    Loc::getMessage("DELLINDEV_ADDRESS_ARRIVAL");

$schemeDelivery = $derivalPhrase.' - '.$arrivalPhrase;
$listProduct = [];


foreach ($shipmentInfo["BASKET"] as $value){

    $listProduct[] = ['data' => [
        'ID' => $value['productId'],
        'NAME' => $value['productName'],
        'COUNT' => $value['quantity'],
        'WGH' => sprintf(Loc::getMessage("DELLINDEV_DEMENSION_BLOCK"), $value['lenght'],
            $value['width'],
            $value['height'],
            $value['weight']),
        'COST' => $value['price']
    ]
    ];

}


CJSCore::Init(array("jquery","date"));


Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/progress_control.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/activity.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/autorun_proc.js');
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/autorun_proc.css');
?>
<h1><?=sprintf(Loc::getMessage("DELLINDEV_HEADER_PAGE"), $shipmentID, $orderID) ?></h1>

<div id="app" class="crm-doc-cart" style="flex-direction: column;">
    <div class="crm-doc-cart" style="display: flex; flex-direction: column;">
        <div class="crm-doc-cart-user-info">
            <h1><?= Loc::getMessage("DELLINDEV_CLIENT_INFO") ?></h1>
        </div>
        <div class="crm-doc-cart-user-info"><?= Loc::getMessage("DELLINDEV_CLIENT_NAME") ?><?= $personInfo['personName']?></div>
        <div class="crm-doc-cart-user-info"><?= Loc::getMessage("DELLINDEV_CONTACT_PHONE") ?><?=$personInfo['personPhone']?></div>
        <div class="crm-doc-cart-user-info">Email: <?=$personInfo['personEmail']?></div>
        <div class="crm-doc-cart-user-info"><?= Loc::getMessage("DELLINDEV_ADDRESS") ?><?=$personInfo['country'].', '.$personInfo['regionName'].
            ', '.$personInfo['cityName'].', '.$personInfo['personAddress']?></div>
    </div>

    <div class="crm-doc-cart" style="flex-direction: column">
        <div class="crm-doc-cart-user-info">
            <h1><?= Loc::getMessage("DELLINDEV_INFO_SHIPMENT") ?></h1>
        </div>
        <div class="crm-doc-cart-user-info"><?= Loc::getMessage("DELLINDEV_SCHEME_DELIVERY") ?><?= $schemeDelivery?></div>
        <div class="crm-doc-cart-user-info"><?= $phraseAddressDerival?>: <?= $shipmentInfo['ADDRESS_DERIVAL']?></div>
        <div class="crm-doc-cart-user-info"><?= $phraseAddressArival ?>: <?= $shipmentInfo['ADDRESS_ARIVAL']?></div>
        <div class="crm-doc-cart">
            <?
            $APPLICATION->IncludeComponent(
                'bitrix:main.ui.grid',
                '',
                [
                    'GRID_ID' => 'report_list',
                    'COLUMNS' => [
                        ['id' => 'ID', 'name' => 'ID', 'sort' => 'ID', 'default' => true],
                        ['id' => 'NAME', 'name' => Loc::getMessage("DELLINDEV_NAMEPRODUCT"), 'sort' => 'NAME', 'default' => true],
                        ['id' => 'COUNT', 'name' => Loc::getMessage('DELLINDEV_COUNT_IN_SHIPMENT'), 'sort' => 'COUNT', 'default' => true],
                        ['id' => 'WGH', 'name' => Loc::getMessage("DELLINDEV_WGH"), 'sort' => 'WGH', 'default' => true],
                        ['id' => 'COST', 'name' => Loc::getMessage('DELLINDEV_COST'), 'sort' => 'COST', 'default' => true],
                    ],
                    'ROWS' => $listProduct,
                    'SHOW_ROW_CHECKBOXES' => false,
                    'AJAX_MODE' => 'Y',
                    'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''),
                    'AJAX_OPTION_JUMP'          => 'N',
                    'SHOW_CHECK_ALL_CHECKBOXES' => false,
                    'SHOW_ROW_ACTIONS_MENU'     => false,
                    'SHOW_GRID_SETTINGS_MENU'   => false,
                    'SHOW_NAVIGATION_PANEL'     => false,
                    'SHOW_PAGINATION'           => false,
                    'SHOW_SELECTED_COUNTER'     => false,
                    'SHOW_TOTAL_COUNTER'        => false,
                    'SHOW_PAGESIZE'             => false,
                    'SHOW_ACTION_PANEL'         => false,
                    'ALLOW_COLUMNS_SORT'        => false,
                    'ALLOW_COLUMNS_RESIZE'      => true,
                    'ALLOW_HORIZONTAL_SCROLL'   => false,
                    'ALLOW_SORT'                => false,
                    'ALLOW_PIN_HEADER'          => false,
                    'AJAX_OPTION_HISTORY'       => 'N'
                ]
            );
            ?>
        </div>
        <?php if (!$shipmentInfo['TRACKING_NUMBER']): ?>
            <div class="crm-doc-cart">
                <div class="crm-doc-cart-user-info"><?= Loc::getMessage("DELLINDEV_DATE_UNLOAD") ?><input id="produce-date" value="<?= $shipmentInfo['DEFAULT_DATE'] ?>" > </div>
                <button id="send" class="ui-btn ui-btn-success"><?= Loc::getMessage("DELLINDEV_CREATE_REQUEST") ?></button>
            </div>
        <? else : ?>
            <div class="crm-doc-cart">
                <div class="crm-doc-cart-user-info"><?= Loc::getMessage("DELLINDEV_TRACKING_NUMBER")?>  <?= $shipmentInfo['TRACKING_NUMBER'] ?></div>
                <div class="crm-doc-cart-user-info"><?= Loc::getMessage("DELLINDEV_STATE_DELIVERY") ?> <?= $shipmentInfo['STATUS_TRACKING'] ?></div>
                <div class="crm-doc-cart-user-info"><?= Loc::getMessage("DELLINDEV_DATE_DELIVERY") ?><?= $shipmentInfo['DATE_ARRIVAL'] ?></div>
            </div>
        <? endif ?>
    </div>
</div>

<script>



    BX.ready(function () {


        BX.namespace('BX.DellinCRM');

        BX.message({
            "DELLINDEV_PROCESS_CREATE_REQUEST" : "<?=Loc::getMessage('DELLINDEV_PROCESS_CREATE_REQUEST') ?>",
            'DELLINDEV_NEXT' : " <?=Loc::getMessage('DELLINDEV_NEXT') ?>"
        });


        BX.DellinCRM = {
            postData: {
                action: 'create_order',
                order_id: <?=$orderID ?> ,
                shipment_id: <?= $shipmentID?>,
                is_order: true,
                produce_date:  "<?= $shipmentInfo['DEFAULT_DATE'] ?>",
                sessid: BX.bitrix_sessid(),
            },
            dateCalendar: "<?= $shipmentInfo['DEFAULT_DATE'] ?>",
            createCalendarWithStateForValidWorkInIframe: function(event){
                BX.calendar({node: event.target,
                    field: event.target,
                    bTime: false,
                    callback_after: function(event) {
                        BX.DellinCRM.postData.produce_date = BX.date.format("d.m.Y", event);
                        console.log('Changed date to:', BX.DellinCRM.postData.produce_date);
                    }
                });

            },
            renderErrorsBlockInAlert: function (result) {
                let block = document.createElement('div');
                block.style = 'color:red;';

                Object.values(result.errors).map((item) => {

                    let span = document.createElement('span');
                    span.innerHTML = item;
                    let br = document.createElement('br');
                    block.appendChild(span);
                    block.appendChild(br);

                });

                return block.outerHTML;
            },
            alertIfsuccess: function (message, action) {

                BX.UI.Dialogs.MessageBox.show(
                    {
                        message: message,
                        title: BX.message('DELLINDEV_PROCESS_CREATE_REQUEST'),
                        buttons: [
                            new BX.UI.Button(
                                {
                                    color: BX.UI.Button.Color.DANGER,
                                    text: action,
                                    onclick: function (button, event) {
                                        setTimeout(() => window.location.reload(), 3000);
                                        button.context.close();
                                    }
                                }
                            ),
                            new BX.UI.CancelButton()
                        ],
                    }
                );
            },
            alertIfPriceChange: function (message, action, postData) {

                BX.UI.Dialogs.MessageBox.show(
                    {
                        message: message,
                        title: BX.message('DELLINDEV_PROCESS_CREATE_REQUEST'),
                        buttons: [
                            new BX.UI.Button(
                                {
                                    color: BX.UI.Button.Color.DANGER,
                                    text: action,
                                    onclick: function (button, event) {
                                        postData.price_changed = true;

                                        console.log('Data sended:', postData);
                                        BX.DellinCRM.ajaxSend(postData);
                                        //TODO �������� ��������
                                        button.context.close();
                                    }
                                }
                            ),
                            new BX.UI.CancelButton()
                        ],
                    }
                );
            },
            alertIfError: function (message, action) {

                BX.UI.Dialogs.MessageBox.show(
                    {
                        message: message ,
                        title: BX.message('DELLINDEV_PROCESS_CREATE_REQUEST'),
                        buttons: [
                            new BX.UI.Button(
                                {
                                    color: BX.UI.Button.Color.DANGER,
                                    text: action,
                                    onclick: function (button, event) {

                                        button.context.close();
                                    }
                                }
                            ),
                            new BX.UI.CancelButton()
                        ],
                    }
                );
            },

            ajaxSend: function (postData) {
                BX.DellinCRM.showLoading();

                BX.ajax({
                    timeout:    300,
                    method:     'POST',
                    dataType:   'json',
                    url:        '/bitrix/tools/dellindev.shipment/dellin_ajax.php',
                    data:       postData,

                    onsuccess: function(result)
                    {

                        if (!result) return;

                        //            console.log(result);
                        if(result.status == 'success'){
                            BX.DellinCRM.hideLoading();
                            BX.DellinCRM.alertIfsuccess(result.message, BX.message('DELLINDEV_NEXT'));
                        }

                        if(result.status == 'price_changed'){
                            BX.DellinCRM.hideLoading();
                            console.log('response price_change', result);

                            BX.DellinCRM.alertIfPriceChange(result.message, BX.message('DELLINDEV_NEXT'), postData);
                        }

                        if(result.status == 'price_update'){
                            BX.DellinCRM.hideLoading();
                            postData.price_changed = false;
                            BX.DellinCRM.ajaxSend(postData)
                        }

                        if(result.status == 'error'){

                            BX.DellinCRM.hideLoading();

                            let info = result.message + '<br/>' + BX.DellinCRM.renderErrorsBlockInAlert(result);
                            BX.DellinCRM.alertIfError(info, BX.message('DELLINDEV_NEXT'));

                            console.error('Errors type:',result.typeErrors);
                            Object.values(result.errors).map((item) => console.error(item));


                        }

                    },

                    onfailure: function(status)
                    {
                        console.log("onfailture", status);
                    }
                });
            },
            showLoading: function (){

                BX.DellinCRM.hideLoading();

                let contentCDialogBlock = document.querySelector('#app');

                let loadingWrap = BX.DellinCRM.createLoadingWrap();
                let loading = BX.DellinCRM.createLoadingBlock();

                contentCDialogBlock.appendChild(loadingWrap);
                contentCDialogBlock.appendChild(loading);
            },
            hideLoading: function(){
                let loadingWrap = document.querySelector('.loading-wrap');
                let loading = document.querySelector('.loading');

                if(!loadingWrap) return;
                if(!loading) return;

                loadingWrap.remove();
                loading.remove();
            },
            createLoadingWrap: function(){
                let loadingWrap = document.createElement('div');
                loadingWrap.className = 'loading-wrap';
                loadingWrap.style = '    width: 100%;' +
                    '    height: 100%;' +
                    '    position: absolute;' +
                    '    top: 0;' +
                    '    left: 0;' +
                    '    background: rgba(0,0,0,0.5);' +
                    '    z-index:99;';

                return loadingWrap;

            },
            createLoadingBlock: function(){

                let styleForBlocks = '    position: fixed;' +
                    '    top: 0px;' +
                    '    right: 0px;' +
                    '    z-index: 9999;' +
                    '    background: #fff;' +
                    '    padding: 20px;' +
                    '    border-radius: 10px;' +
                    '    box-shadow:0 0 5px rgba(0,0,0,0.5);';

                let loading = document.createElement('div');
                loading.className = 'loading';
                loading.style = styleForBlocks;

                let span = document.createElement('span');
                span.className = 'loading-content';
                span.style = styleForBlocks;

                let img = document.createElement('img');
                img.src = '/bitrix/js/main/core/images/wait.gif';

                span.appendChild(img);
                loading.appendChild(span);

                return loading;

            },
        };


        let button = document.querySelector('#send');

        let inputProduceDate = document.querySelector('#produce-date');
        inputProduceDate.addEventListener('click', (event)=>{
            BX.DellinCRM.createCalendarWithStateForValidWorkInIframe(event);
        });

        //let postData = {
        //    action: 'create_order',
        //    order_id: <?//=$orderID ?>// ,
        //    shipment_id: <?//= $shipmentID?>//,
        //    is_order: true,
        //    produce_date:  BX.DellinCRM.dateCalendar,
        //    sessid: BX.bitrix_sessid(),
        //};


        button.addEventListener('click', ()=>{
            
                console.log(BX.DellinCRM.postData);
                BX.DellinCRM.ajaxSend(BX.DellinCRM.postData);
            

        })

    })
</script>
