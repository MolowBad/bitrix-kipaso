<?
use Bitrix\Main\Localization\Loc;
use Sale\Handlers\Delivery\Dellin\AjaxService;

/** @var CMain $APPLICATION */
Loc::loadMessages(__FILE__);
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/**
 * JS-совская часть скрипта создана на базе стандартного bitrix.order.ajax 
 * Работоспособность гарантируется только на СТАНДАРТНОМ шаблоне, на остальных - не факт.
 * Используйте BitrixLike решения, старайтесь реже отходить от коробочных имплементаций и будет Вам счастье :D 
 * 
 */


?>


<script type="text/javascript">

    BX.namespace('BX.Dellin');

    BX.Dellin = {
        ajaxpage: "<?=$componentPath.'/ajax.php'?>",
        terminalList: <?=json_encode($arParams['dellin']['terminalList'], JSON_UNESCAPED_UNICODE) ?>,
        terminalsMethod: <?=json_encode($arParams['dellin']['terminalsMethod'], JSON_UNESCAPED_UNICODE) ?>,
        fieldTerminalID: <?=$arParams['dellin']['currentTerminalField'] ?>,
        fieldDeliveryTimeStart: <?=$arParams['dellin']['currentDeliveryTimeStartField']?>,
        fieldDeliveryTimeEnd: <?=$arParams['dellin']['currentDeliveryTimeEndField']?>,
        selectedDeliveryMethod: <?=$arParams['USER_RESULT']['DELIVERY_ID']?>,
        widgetParam: <?= json_encode($arParams['dellin']['widget'])?>,
        hasShowDellinMap: <?=json_encode($arParams['dellin']['widget']['hasShowDellinMap'])?>, // �������� ������� ����� �������� �� �������
        isOldViewComponent: <?=json_encode($arParams['dellin']['widget']['isOldViewComponent'])?>, // ������� ��� ������� ������� ������������� � ������ select
        isCloseCustomBlock: false, // �������� ������������ ��������� ��������� ������ ����������� ���������.
        hasMountedMaps: false, // state props
        choosedTerminalId: null, // выбранный терминал
        recent_delivery_value: null,
        isTerminalMethodSelected: function(){
            if(!BX.Dellin.terminalsMethod) return false ;

            let res = false;

            BX.Dellin.terminalsMethod.map(function (method) {

                if(BX.Dellin.getDeliveryData().ID == method){
                    res = true;
                }

            });

            return res;

        },
        setIdFieldTerminal: function(result){

            if(!result.order) return ;
            let fieldId = '';

            result.order.ORDER_PROP.properties.map(function (field) {

                if(field.CODE == "TERMINAL_ID"){
                    fieldId = field.ID;
                }

            });

            return fieldId;
        },
        getBlockPickup: function (){
            return document.querySelector('#bx-soa-pickup');
        },
        init: function () {

            console.log("Dellin init");

            let terminalID = BX.Dellin.getFieldElementByID('input', BX.Dellin.fieldTerminalID);

            if(!terminalID){
                console.warn('DELLIN WARNING: Field terminalID is null!');
                return ;
            }



            terminalID.style = "display:none;";
            console.log('Hide input terminal');

            if(!BX.Dellin.isOldViewComponent)
            {
                BX.Dellin.hideTerminalBlock();
            }

            let buildTerminal = BX.Dellin.getTerminalsList();
            terminalID.after(buildTerminal);


        },
        styledFieldsSelect: function(){

            let selectClassForCheckout = 'form-control bx-soa-customer-select';

            let deliveryTimeStart = BX.Dellin.getFieldElementByID('select', BX.Dellin.fieldDeliveryTimeStart);

            if(!deliveryTimeStart){
                console.warn('DELLIN WARNING: Field deliveryTimeStart is null!');
                return ;
            }

            deliveryTimeStart.className = selectClassForCheckout;

            let deliveryTimeEnd =  BX.Dellin.getFieldElementByID('select', BX.Dellin.fieldDeliveryTimeEnd);

            if(!deliveryTimeEnd){
                console.warn('DELLIN WARNING: Field deliveryTimeEnd is null!');
                return ;
            }

            deliveryTimeEnd.className = selectClassForCheckout;

        },
        getTerminalBlock: function(){
            return document.querySelector('div[data-property-id-row="'+BX.Dellin.fieldTerminalID+'"]');
        },
        hideTerminalBlock: function(){

            let terminalBlock = BX.Dellin.getTerminalBlock();

            if(!terminalBlock){
                console.warn('Field terminal is null!');
                return ;
            }

            terminalBlock.style = 'display:none';

        },
        showTerminalBlock: function(){
            let terminalBlock = BX.Dellin.getTerminalBlock();
            if(!terminalBlock){
                console.warn('DELLIN WARNING: Properties with code "TERMINAL_ID" is null');
                return ;
            }
            terminalBlock.style = '';
        },
        getFieldElementByID: function(typeField, fieldID){
            return  document.querySelector(typeField+'[name="ORDER_PROP_'+fieldID+'"]');
        },
        removeSelectBlock: function(){
            let terminals = document.querySelector('#terminals');
                if(!terminals){
                    return ;
                }
                terminals.remove();
        },
        buildBlockDataWithTerminals: function(parent, result){

            let label = document.createElement('label');
            label.id = 'label_terminals';
            let strong = document.createElement('strong');
            strong.innerHTML = BX.message("DELLINDEV_TERMINAL_LIST");
            label.appendChild(strong);

            let br = document.createElement('br');
            br.id = 'container-br';

            parent.appendChild(label);
            parent.appendChild(br);

            BX.Dellin.getTerminalsList(result, parent);

        },
        builderOptionList:function(fieldterm, field, id, name, type){
            let option = document.createElement('option');
            option.innerHTML = name;
            option.value = id;
            option.id = type+'-added';
            option.selected = (field.dataset.value == id);
            field.appendChild(option);

            // option.addEventListener('click', function () {
            //     console.log(BX.message("DELLINDEV_SELECTED_TERMINAL"), option.value);
            //     BX.Dellin.setValueInInput(fieldterm, option.value);
            // })

        },
        setValueInInput: function(prop_id, value){
            let propElement = document.querySelector('[name="ORDER_PROP_'+prop_id+'"]');
                propElement.style = 'display:none';
                propElement.readonly = true;
                propElement.value = value;
        },
        getCurrentTerminalId: function()
        {
            let prop_id = BX.Dellin.fieldTerminalID;
            let propElement = document.querySelector('[name="ORDER_PROP_'+prop_id+'"]');
                if(!propElement)
                {
                    console.warn('Terminal props is undefined');
                    return ;
                }
            return propElement.value;

        }
        ,
        getTerminalsList: function () {

            if(!BX.Dellin.terminalList){
                return ;
            }

            BX.Dellin.removeSelectBlock();

            let select = document.createElement('select');
            select.id = 'terminals';
            select.className = 'form-control bx-soa-customer-select';
            let prop_id = BX.Dellin.fieldTerminalID;

            //fix bug payment change state
            if(!BX.Dellin.choosedTerminalId){
                let firstValue = BX.Dellin.terminalList[0].terminal_id;
                BX.Dellin.choosedTerminalId = firstValue;
                BX.Dellin.setValueInInput(prop_id, firstValue);
            }
            
            
            
            if(!BX.Dellin.terminalList){
                BX.Dellin.hideTerminalBlock();
            }

            BX.Dellin.terminalList.map(function (terminal) {
                BX.Dellin.builderOptionList(prop_id, select, terminal.terminal_id, terminal.address, 'terminal');

            });


            select.addEventListener('change', function () {

                BX.Dellin.setValueInInput(prop_id, select.value);
                BX.Dellin.choosedTerminalId = select.value;
                console.log(BX.message("DELLINDEV_SELECTED_TERMINAL"), select.value);

            });


            return select;
        },
        getDeliveryData: function () {
            return BX.Sale.OrderAjaxComponent.getSelectedDelivery()
        },
        getFormData: function () {
            return BX.Sale.OrderAjaxComponent.getAllFormData();
        },

        ajaxSend: function (id) {

            let postData = {
                sessid: BX.bitrix_sessid(),
                action: 'terminal_data',
                ajax: 'Y',
                delivery_id: id,
                person_type_id: BX.Dellin.getFormData().PERSON_TYPE,
            };

            BX.ajax({
                timeout:    300,
                method:     'POST',
                dataType:   'json',
                url:        window.location.pathname,
                data:       postData,

                onsuccess: function(result)
                {

                    if(result.RESULT == 'OK'){
                        BX.Dellin.init(result);
                    }

                },

                onfailure: function(status)
                {
                    console.log("onfailture", status);
                }
            });
        },
        changePropsID: function (result) {
            let props = result.order.ORDER_PROP.properties;
            props.map((prop)=> {

                if(prop.CODE == 'DELLIN_DELIVERYTIME_START'){
                    BX.Dellin.fieldDeliveryTimeStart = prop.ID;
                //    console.log(BX.Dellin.fieldDeliveryTimeStart);
                    return;
                }

                if(prop.CODE == 'DELLIN_DELIVERYTIME_END'){
                    BX.Dellin.fieldDeliveryTimeEnd = prop.ID;
                //    console.log(BX.Dellin.fieldDeliveryTimeEnd);
                    return;
                }

                if(prop.CODE == 'TERMINAL_ID'){
                    BX.Dellin.fieldTerminalID = prop.ID;
                //    console.log(BX.Dellin.fieldTerminalID);
                    return;
                }

            })

        },
        appenChildInBlockDescription: function(block)
        {
            let deliveryBlock = document.querySelector('#bx-soa-delivery');
            let description = deliveryBlock.querySelector('div.bx-soa-pp-company-block');
                description.appendChild(block);
        },
        getCityParameters: function()
        {
            let city = {};

            let currentTerminal = BX.Dellin.getCurrentTerminalId();
            if(!currentTerminal)
            {
                console.warn('City is null');
                return ;
            }


            BX.Dellin.terminalList.forEach((terminal)=>{
                
                city = terminal.more.terminal.infoCity;
            });

            return city;
            
        },
        
        getTerminalInfoById: function(terminalId)
        {
            let result = {};

            if(BX.Dellin.isTerminalMethodSelected())
            {
                if(!BX.Dellin.terminalList)
                {
                    return ;
                }
                
                BX.Dellin.terminalList.forEach((terminal)=>
                {
                    terminal = terminal.more.terminal;
                      
                    if(terminal.id == terminalId)
                    {
                    //    console.log(terminal);
                        result = {
                            name: terminal.name,
                            terminal_id: terminalId,    
                            address: terminal.address,
                            latitude: terminal.latitude,
                            longitude: terminal.longitude,
                            schedule: terminal.calcSchedule.arrival,
                            exceptionShedule: terminal.worktables.specialWorktable.giveout,
                            contactPhone: (terminal.mainPhone)?terminal.mainPhone:''
                        }
                    }
                })
            }
            return result;
        },
        buildHiddenBlockTerminals: function()
        {
            /**
             * ���� ������ ���������� ��������� �������� ��� ������ ���� ������ �� ����������. 
             *
             */
            let currentTerminal = BX.Dellin.getCurrentTerminalId()
            let selectedPickUp = BX.Dellin.getTerminalInfoById(currentTerminal);
        //    console.log(selectedPickUp);
        //    console.log(currentTerminal);
            let html = '';

            html += '<strong><?=GetMessageJS("DELLINDEV_SHIPMENT_NAIMENOVANIE_TERMINA")?>' + BX.util.htmlspecialchars(selectedPickUp.name) + '</strong>';
            if (selectedPickUp.address)
                html += '<br><strong>' + BX.message('SOA_PICKUP_ADDRESS') + ':</strong> ' + BX.util.htmlspecialchars(selectedPickUp.address);

            if (selectedPickUp.contactPhone)
                html += '<br><strong>' + BX.message('SOA_PICKUP_PHONE') + ':</strong> ' + BX.util.htmlspecialchars(selectedPickUp.contactPhone);

            if (selectedPickUp.schedule)
                html += '<br><strong>' + BX.message('SOA_PICKUP_WORK') + ':</strong> ' + BX.util.htmlspecialchars(selectedPickUp.schedule);
            
            if(selectedPickUp.longitude && selectedPickUp)

            if(selectedPickUp.exceptionShedule.length < 1)
            {  
                let startText = '';

                let text = selectedPickUp.exceptionShedule.reduce(
                    (acc, currenValue) => acc+ ', '+currenValue,
                    startText
                );    

                html += '<br>' + BX.util.htmlspecialchars(text);

            }
                
            //pickUpContainer.innerHTML = html;
            return html;

        },
        createTerminalBlockInPickUp: function()
        {
            
            let head = document.createElement('div');

            let pickUpMainList = document.createElement('div');
                pickUpMainList.className = 'bx-soa-pickup-list main';
            
            if(!BX.Dellin.terminalList)
            {
                return ;
            }

            BX.Dellin.terminalList.forEach((terminal)=>{

                let data = BX.Dellin.getTerminalInfoById(terminal.terminal_id);
                
                let viewItem = BX.Dellin.createTerminalListItem(data);

                pickUpMainList.append(viewItem);

            });
            


            return pickUpMainList;


        },
        baloonTemplate: function()
        {
            

            let item = document.createElement('div');
                item.className = 'bx-soa-pickup-list-item bx-selected';
                 
                item.style = 'background: #fff;margin: 10px;';
    

            let itemAddress = document.createElement('div');
                itemAddress.className = 'bx-soa-pickup-l-item-adress';
                itemAddress.innerText = '{{properties.name}}';

            
            let itemDetail = document.createElement('div');
                itemDetail.className = 'bx-soa-pickup-l-item-detail';
                
            
            let itemName = document.createElement('div');
                itemName.className = 'bx-soa-pickup-l-item-name';
                itemName.innerText = '';
        
            let itemDescription = document.createElement('div');
                itemDescription.className = 'bx-soa-pickup-l-item-desc';
            let itemStr = BX.message('SOA_PICKUP_ADDRESS')+': '+'{{properties.address}}'+'<br>'+
                          BX.message('SOA_PICKUP_WORK') +': '+'{{properties.schedule}}'+'<br>';
                itemDescription.innerHTML = itemStr;
                

            let itemButton = document.createElement('div');
                itemButton.className = 'bx-soa-pickup-l-item-btn';

            let button = document.createElement('div');
                button.className = 'btn btn-sm btn-primary';
                button.id = 'dellinChooseButton';
                button.innerText = BX.message('INPUT_FILE_BROWSE');
                button.dataset.id = '{{properties.terminal_id}}';
 

                itemButton.append(button);

                itemDetail.append(itemName);
                itemDetail.append(itemDescription);

                item.append(itemAddress);
                item.append(itemDetail);
                item.append(itemButton);

            

            let ballonTemplate = ymaps.templateLayoutFactory.createClass(
                item.outerHTML,
                {
                    build: function(){
                        ballonTemplate.superclass.build.call(this);
                        //TODO ���������� ������������� ���������
                        let button = document.querySelector('#dellinChooseButton');
                      //  button.forEach(function(button){
                            // �������� �� ���� �������. 
                            button.addEventListener('click', this.onClickChoose);
                      //  });
                        
                    },
                    onClickChoose: function(event)
                    {
                        
                        // console.log('Click in baloon', event);
                        // console.log('id', event.srcElement.dataset.id);
                        // BX.Dellin.setValueInInput(BX.Dellin.fieldTerminalID, event.srcElement.dataset.id);
                        // BX.Dellin.changeCountIcon();
                        // BX.Dellin.clearDisplayContainer();
                        // BX.Dellin.changeContentInDellinBlock();
                        // let blockThisSection = document.querySelector('#bx-soa-delivery');
                        // let nextBlock = document.querySelector('#bx-soa-paysystem');
                        // BX.Sale.OrderAjaxComponent.fade(blockThisSection);
                        // BX.Sale.OrderAjaxComponent.show(nextBlock);
                        BX.Dellin.changeChoose(event.srcElement.dataset.id);
                        BX.Dellin.closePropsBlock('choose');
                        BX.Dellin.hideTimeIntervals();
                        BX.Dellin.hideTerminalBlock();
                    }
                }
            );

            return ballonTemplate;
        },
        changeChoose: function(terminal_id)
        {
            console.log('new terminal_id', terminal_id);
            BX.Dellin.setValueInInput(BX.Dellin.fieldTerminalID, terminal_id);
            BX.Dellin.choosedTerminalId = terminal_id;
            BX.Dellin.changeCountIcon();
            BX.Dellin.clearDisplayContainer();
            BX.Dellin.changeContentInDellinBlock();
            let blockThisSection = document.querySelector('#bx-soa-delivery');
            let nextBlock = document.querySelector('#bx-soa-paysystem');
            BX.Sale.OrderAjaxComponent.fade(blockThisSection);
            BX.Sale.OrderAjaxComponent.show(nextBlock);
        },
        createTerminalListItem: function(terminal)
        {
        //    console.log(terminal);

            let item = document.createElement('div');
                item.className = (terminal.terminal_id == BX.Dellin.getCurrentTerminalId())?'bx-soa-pickup-list-item bx-selected':'bx-soa-pickup-list-item';
                 
                item.style = (terminal.terminal_id != BX.Dellin.getCurrentTerminalId())?'background: #fff':'';

                item.setAttribute('onclick', 'BX.Dellin.onItemClick()');
                item.setAttribute('terminalId', terminal.terminal_id);



            let itemAddress = document.createElement('div');
                itemAddress.className = 'bx-soa-pickup-l-item-adress';
                itemAddress.innerText = terminal.name;

            
            let itemDetail = document.createElement('div');
                itemDetail.className = 'bx-soa-pickup-l-item-detail';
                
            
            let itemName = document.createElement('div');
                itemName.className = 'bx-soa-pickup-l-item-name';
                itemName.innerText = terminal.name;
        
            let itemDescription = document.createElement('div');
                itemDescription.className = 'bx-soa-pickup-l-item-desc';
            let itemStr = BX.message('SOA_PICKUP_ADDRESS')+': '+terminal.address+'<br>'+
                          BX.message('SOA_PICKUP_WORK') +': '+terminal.schedule+'<br>';
                itemDescription.innerHTML = itemStr;
                

            let itemButton = document.createElement('div');
                itemButton.className = 'bx-soa-pickup-l-item-btn';

            let button = document.createElement('div');
                button.className = 'btn btn-sm btn-primary';
                button.innerText = BX.message('INPUT_FILE_BROWSE');
                button.setAttribute('terminalId',terminal.terminal_id);
                button.setAttribute('onclick', 'BX.Dellin.onItemChooseClick()');

 

                itemButton.append(button);

                itemDetail.append(itemName);
                itemDetail.append(itemDescription);

                item.append(itemAddress);
                item.append(itemDetail);
                item.append(itemButton);
                

            return item;
        },
        onItemClick: function(){
         //   event.currentTarget;
            BX.Dellin.watchAndCloseItemDetails();
            console.log("click", event);
            console.log('target', event.currentTarget);
            event.currentTarget.className = 'bx-soa-pickup-list-item bx-selected';

        },
        onItemChooseClick: function()
        {
            let chooseTerminalId = event.currentTarget.getAttribute('terminalId');
            console.log('Click on Choose', event.currentTarget);
            console.log('SelectedTerminal', chooseTerminalId);
            BX.Dellin.changeChoose(chooseTerminalId);
            BX.Dellin.closePropsBlock();
        },
        watchAndCloseItemDetails: function()
        {

            let watchedBlock = document.querySelector('.bx-soa-pickup-list.main');
            if(watchedBlock){

                watchedBlock.childNodes.forEach((item)=>{

                item.classList.forEach((selector)=>{
                    if(selector == 'bx-selected')
                    {
                        item.className = 'bx-soa-pickup-list-item';
                    }
                })

})
            }

        },
        createYmapsObject: function()
        {
            if(BX.Dellin.isTerminalMethodSelected())
            {

                if(BX.Dellin.hasMountedMaps)
                {
                    console.warn('BX.Dellin.hasMountedMaps is true');
                    return ;
                }


                if(BX.Dellin.isEmptyLocationField())
                {
                    console.warn('Is Empty Location field');
                    return ;
                }

                BX.Dellin.hasMountedMaps = true;
                
                let city = BX.Dellin.getCityParameters();
                console.log('<?=GetMessageJS("DELLINDEV_SHIPMENT_GOROD")?>', city);
                let blockMap = document.querySelector('#dellinMap');

                if(!blockMap){
                    console.warn('dellinMap is undefined');
                    return ;
                }

                let dellinMap = new ymaps.Map("dellinMap", { 
                    center: [city.latitude, city.longitude], // [������, �������] ��������� �� ������ ������
                    zoom: 10 // ������� ��� �����, ��������������� � ����������� �� �������
                });


                BX.Dellin.terminalList.forEach((terminal)=>{
                //    console.log(terminal);

                    let data = BX.Dellin.getTerminalInfoById(terminal.terminal_id);
                    let  MyIconContentLayout = ymaps.templateLayoutFactory.createClass(
                        '<div style="color: #FFFFFF; font-weight: bold;">$[properties.iconContent]</div>'
                    );
                   // let block = BX.Dellin.createTerminalListItem(data);
                //    console.log('terminal', data);
                    dellinMap.geoObjects.add(new ymaps.Placemark([data.latitude, data.longitude], {
                        terminal_id: data.terminal_id,
                        name: data.name,
                        address: data.address,
                        schedule: data.schedule,
                        contactPhone: data.contactPhone,
                        exceptionShedule: data.exceptionShedule,
                    },  {
						// iconLayout: 'default#imageWithContent',
						// iconImageHref: 'bitrix/bitrix/images/dellindev/sprite-markers.png',
						// iconImageSize: [40, 43],
						// iconImageOffset: [-10, -31],
                        // iconContentLayout: MyIconContentLayout,
                        iconLayout: 'default#image',
                        // ��� ����������� ������ �����.
                        iconImageHref: '/local/components/dellindev/dellindev.shipment.choose/templates/.default/images/sprite-markers.png',
                        // ������� �����.
                        iconImageSize: [51, 67],
                        // �������� ������ �������� ���� ������ ������������
                        // � "�����" (����� ��������).
                        iconImageOffset: [-24, -24],
                        // �������� ���� � ���������� ������������ ���� � ���������.
                       // iconContentOffset: [, 67],
                        // ����� �����������.
                        iconContentLayout: MyIconContentLayout,
                        balloonContentLayout: BX.Dellin.baloonTemplate(),
                        balloonPanelMaxMapArea: '0A',
						}, {
                        balloonContentLayout: BX.Dellin.baloonTemplate(),
                        balloonPanelMaxMapArea: '0A',
                    }))
                    //  }, {
                        
                    //     preset: 'islands#dotIcon', // ����� �������� �����
                    // }))
                   

                });
            }

        },
        buildDisplayBlockTerminals: function()
        {
            /**
             * ���� ������ ���������� ����� ����������, ���� �� �����,
             * ���� ��� ������ � ������������ ���� ������ (����� ������ � ������� �� ������ �����)
             */
            let map = document.createElement('div');
                    map.id = 'dellinMap';
                    map.style = 'width: 100%; margin-bottom: 10px; height: 342px;';

            let col = document.createElement('div');
                col.className = 'col';

            let strong = document.createElement('strong');
                strong.innerText = '<?=GetMessageJS("DELLINDEV_SHIPMENT_VYBERITE_TERMINAL_NA")?>';

            col.append(strong);
            col.append(map);
          //  BX.Dellin.hasMountedMaps = true;
            return col;

        },
        checkClassNameInclude: function(node, className)
        {
            let result = false;
            if(!node)
            {
                console.warn('Node not found');
                return ;// return undefined, for handle 
            }

            node.classList.forEach((el)=>{

                if(el == className){
                    result = true;
                }

            });

            return result;
            
        },
        isPickUpBlockSelected: function()
        {
            let pickUpElement = BX.Dellin.getBlockPickup();
            let className = 'bx-selected';

            return BX.Dellin.checkClassNameInclude(pickUpElement, className);
           
        },
        isPickUpBlockCompleted: function()
        {
            let pickUpElement = BX.Dellin.getBlockPickup();
            let className = 'bx-step-completed';

            return BX.Dellin.checkClassNameInclude(pickUpElement, className);
        },
        isBlockMounted: function()
        {
            let block = document.querySelector('#bx-soa-dellin');
            return (block != null);
        },
        injectionCustomBlock: function()
        {

            if(BX.Dellin.isEmptyLocationField())
            {
                console.warn('Is empty locationField');
                return ;
            }

            if(BX.Dellin.isOldViewComponent)
            {
                console.log('Use old view on terminals')
                return ;
            }
            
            if(BX.Dellin.isBlockMounted())
            {
                console.warn('Dellin block is mounted');
                return ;
            }
            let block = document.createElement('div'); 
                block.className = 'bx-soa-section';
                block.id = 'bx-soa-dellin';
            let title = document.createElement('div');
                title.className = 'bx-soa-section-title-container d-flex justify-content-between align-items-center flex-nowrap';
            
                

            let titleContent = document.createElement('div');
                titleContent.className = 'bx-soa-section-title';
                titleContent.setAttribute('data-entity', 'section-title');

            let titleSpan = document.createElement('span');
                titleSpan.className = 'bx-soa-section-title-count';
                
                titleContent.append(titleSpan);
                titleContent.append('<?=GetMessageJS("DELLINDEV_SHIPMENT_DELOVYE_LINII")?>');

            let titleSpanChange = document.createElement('span');

            let titlePChange = document.createElement('p');
                
                titlePChange.innerText = '<?=GetMessageJS("DELLINDEV_SHIPMENT_VYBRATQ_TERMINAL")?>';
                titlePChange.className = 'bx-soa-editstep';
                if(BX.Dellin.isCloseCustomBlock)
                {
                    
                    titlePChange.style = 'color: var(--theme-b-link)';
                } else {
                    titlePChange.style = 'display:none;';
                  
                }
                


                titleSpanChange.append(titlePChange);
                
                title.append(titleContent);
                title.append(titleSpanChange);
                            


            let innerBlock = BX.Sale.OrderAjaxComponent.getNewContainer();
            let element = document.querySelector('#bx-soa-delivery');
                block.append(title);
                block.append(innerBlock);
                //Block is closed
            let content = block.querySelector('.bx-soa-section-content');

            if(!content)
            {
                content = BX.Sale.OrderAjaxComponent.getNewContainer();
                block.lastChild(content);
            } 
            
            titlePChange.addEventListener('click', (event)=>{

                    console.log('<?=GetMessageJS("DELLINDEV_SHIPMENT_NAJALI_NA")?>', event);
                    
                    BX.Dellin.clearDisplayContainer();

//                    let view = BX.Dellin.buildHiddenBlockTerminals();
//                        content.innerHTML = view;
                    BX.Dellin.changeCountIcon();
                    BX.Dellin.changeContentInDellinBlock();
                    BX.Dellin.createYmapsObject();
                    BX.Dellin.closePropsBlock('change');
                    
                    BX.Dellin.hideTimeIntervals();
                    BX.Dellin.hideTerminalBlock();


                });



            let view = BX.Dellin.getViewWidget();
            //    container.innerHTML = view; 

            //    title.after(view);
           // console.log(view);
            content.innerHTML = view;
                
            element.after(block);

                
        },
        getViewWidget: function()
        {

            if(BX.Dellin.hasMountedMaps)
            {
                console.warn('Dellin block is mounted');
                return ;
            }

            if(BX.Dellin.isCloseCustomBlock)
            {
                let viewShort = BX.Dellin.buildHiddenBlockTerminals()
                return viewShort;
            }
           
            
            if(BX.Dellin.hasShowDellinMap){
                let viewWithMap = BX.Dellin.buildDisplayBlockTerminals();
                return viewWithMap.outerHTML;
            }
          
            if(!BX.Dellin.isCloseCustomBlock && !BX.Dellin.hasShowDellinMap )
            {
                let viewWithDisabledMap = BX.Dellin.createTerminalBlockInPickUp();
                return viewWithDisabledMap.outerHTML; 
            }
            
        },
        clearDisplayContainer: function()
        {            
            let elem = document.querySelector('#bx-soa-dellin');

            let container = elem.querySelector('.bx-soa-section-content');
        //    console.log(container);
            BX.Dellin.hasMountedMaps = false;
            while (container.firstChild) {
                container.removeChild(container.firstChild);
            }

        },
        changeCountIcon: function()
        {
            let elem = document.querySelector('#bx-soa-dellin');

            if(BX.Dellin.isCloseCustomBlock)
            {
                elem.className = 'bx-soa-section';
                BX.Dellin.isCloseCustomBlock = !BX.Dellin.isCloseCustomBlock;
                BX.Dellin.hideTitleLink(elem);
            } else {

                elem.className = 'bx-soa-section bx-active bx-step-completed';
                BX.Dellin.isCloseCustomBlock = !BX.Dellin.isCloseCustomBlock;
                BX.Dellin.hideTitleLink(elem);
            }
        },
        hideTitleLink: function(elem)
        {
            let link = elem.querySelector('.bx-soa-editstep');
                if(BX.Dellin.isCloseCustomBlock)
                {
                    link.style = "color: var(--theme-b-link); display: block;";
                } else {
                    link.style = "display:none;";
                }
        },
        changeContentInDellinBlock: function()
        {

            let element = document.querySelector('#bx-soa-dellin');

            let content = element.querySelector('.bx-soa-section-content');
            
            let widget = BX.Dellin.getViewWidget();

                content.innerHTML = widget;
   
            
        }, 
        removeInjectedBlock: function()
        {
            let injectedBlock = document.querySelector('#bx-soa-dellin');
                if(injectedBlock)
                {
                    BX.Dellin.hasMountedMaps = false;
                    console.log('Remove bx-soa-dellin');
                    injectedBlock.remove();
                }
        }, 
        moreBlock: function()
        {
            let moreBlock = document.createElement('div');
                moreBlock.className = 'row bx-soa-more';
            let buttonCol = document.createElement('div');
                buttonCol.className = 'bx-soa-more-btn col';
            
            let buttonPrev = document.createElement('button');
                buttonPrev.className = 'btn btn-outline-secondary pl-3 pr-3';
                buttonPrev.innerText = '<?=GetMessageJS("DELLINDEV_SHIPMENT_NAZAD")?>';

            let buttonNext = document.createElement('button');
                buttonNext.className = 'pull-right btn btn-primary pl-3 pr-3';
                buttonNext.innerText = '<?=GetMessageJS("DELLINDEV_SHIPMENT_VPERED")?>';

                
            
        },
        isEmptyLocationField: function()
        {
            let fields = BX.Sale.OrderAjaxComponent.getAllFormData();
            let locationFieldState = fields.RECENT_DELIVERY_VALUE;
            let result = false

            if(locationFieldState == '')
            {
                result = true;
            }

            return result;
        },
        closePropsBlock: function()
        {
            let orderBlockNode = BX.Sale.OrderAjaxComponent.orderBlockNode;
            
            let selectors = orderBlockNode.querySelectorAll('.bx-soa-section.bx-active');

            selectors.forEach((block)=>{
                block.classList.forEach((itemClass)=>{
                    if( block.id == "bx-soa-properties" &&
                        itemClass == "bx-selected")
                    {
                    //    if(placeDoes == 'choose'){
                            BX.Sale.OrderAjaxComponent.editFadePropsBlock();
                            BX.addClass(block, 'bx-step-completed');
                            BX.removeClass(block, 'bx-selected');
                            BX.Sale.OrderAjaxComponent.initialized.props = false;
                      //  }

                      //  if(placeDoes == 'change')
                      //  {
                      //      BX.Sale.OrderAjaxComponent.fade(block);
                      //  }

                    }
                });
                
            });
        },
        hideTimeIntervals: function()
        {
            let checkout = BX.Sale.OrderAjaxComponent.orderBlockNode;
            
            let selectorStart = '[data-property-id-row="'+BX.Dellin.fieldDeliveryTimeStart+'"]';
            let selectorEnd = '[data-property-id-row="'+BX.Dellin.fieldDeliveryTimeEnd+'"]';

            let deliveryTimeStart = checkout.querySelector(selectorStart);
            let deliveryTimeEnd = checkout.querySelector(selectorEnd);

            if(deliveryTimeStart)
            {
                deliveryTimeStart.style = 'display:none;';
            }

            if(deliveryTimeEnd)
            {
                deliveryTimeEnd.style = 'display:none;';
            }

        },
        showTimeIntervals: function()
        {
            
            let checkout = BX.Sale.OrderAjaxComponent.orderBlockNode;
            
            let selectorStart = '[data-property-id-row="'+BX.Dellin.fieldDeliveryTimeStart+'"]';
            let selectorEnd = '[data-property-id-row="'+BX.Dellin.fieldDeliveryTimeEnd+'"]';

            let deliveryTimeStart = checkout.querySelector(selectorStart);
            let deliveryTimeEnd = checkout.querySelector(selectorEnd);

            if(deliveryTimeStart)
            {
                deliveryTimeStart.style = '';
            }

            if(deliveryTimeEnd)
            {
                deliveryTimeEnd.style = '';
            }
        },
        observeOrderBlock: function(){
            //new Arch Solution for test 

            let observer = new MutationObserver(mutationRecords => {
                
                if(BX.Dellin.isPickUpBlockSelected){
                    if(!BX.Dellin.isOldViewComponent){
                        BX.Dellin.hideTerminalBlock();
                    }
                    if(BX.Dellin.isTerminalMethodSelected())
                    {
                        BX.Dellin.hideTimeIntervals();
                    }
                    
                } 
                
                });
            
            let checkout = BX.Sale.OrderAjaxComponent.orderBlockNode;
            
            let elem = checkout.querySelector('#bx-soa-properties');

            
            observer.observe(elem, {
                childList: true, 
                subtree: true
                });


        },
        setCityCode: function ()
        {
            BX.Dellin.recent_delivery_value = BX.Sale.OrderAjaxComponent.getAllFormData().RECENT_DELIVERY_VALUE;
        },
        hasChangeCityCode: function(){
            return !(BX.Dellin.recent_delivery_value == 
                    BX.Sale.OrderAjaxComponent.getAllFormData().RECENT_DELIVERY_VALUE );
        },
    }



    BX.ready(function () {

        BX.message({
            "DELLINDEV_TERMINAL_LIST": "<?=Loc::getMessage("DELLINDEV_TERMINAL_LIST")?>",
            "DELLINDEV_SELECTED_TERMINAL": "<?=Loc::getMessage("DELLINDEV_SELECTED_TERMINAL")?>"
        });


           BX.Dellin.setCityCode();


           BX.Dellin.observeOrderBlock();
           // mock to BX proxy
           // it`s fake hidden block 
           let deliveryHidden = document.querySelector('#bx-soa-delivery-hidden');
           let dellin = document.createElement('div');
               dellin.id = 'bx-soa-dellin-hidden';
               deliveryHidden.after(dellin);

           let delivery = document.querySelector('#bx-soa-delivery');
           let footerForButton = delivery.querySelector('.bx-soa-more-btn');

           if(footerForButton)
           {
                let button = footerForButton.querySelector('.pull-right.btn.btn-primary.pl-3.pr-3');
                    button.addEventListener('click', function(){
                        if(BX.Dellin.isTerminalMethodSelected())
                        {
                            if(BX.Dellin.isCloseCustomBlock)
                            {
                                BX.Dellin.clearDisplayContainer();
                                BX.Dellin.changeCountIcon();
                                BX.Dellin.changeContentInDellinBlock();
                                BX.Dellin.createYmapsObject();
                            }
                        }
                    });

           }

        console.log('Dellin ready');


        if(BX.Dellin.isTerminalMethodSelected()){

            let changeLink = document.querySelectorAll('a.bx-soa-editstep');
            
            //todo
            changeLink.forEach((element)=>{
                element.addEventListener('click', (elem)=>{
                    console.log('<?=GetMessageJS("DELLINDEV_SHIPMENT_KLIK_NA_IZMENITQ")?>');
                    
                });
            })

            

            BX.Dellin.showTerminalBlock();
            BX.Dellin.init();
            BX.Dellin.styledFieldsSelect();
            BX.Dellin.hideTimeIntervals();

            if(!BX.Dellin.terminalList)
            {
                let terminalBlock = BX.Dellin.getTerminalBlock();
                    terminalBlock.style = 'display: none'; 
                console.warn('Terminal list is undefined');
                return ;
            } 

            


            if(BX.Dellin.isPickUpBlockSelected){

                if(BX.Dellin.isOldViewComponent)
                {

                    console.warn('Is old view component.')
                } else {
                    BX.Dellin.hideTerminalBlock();
                    BX.Dellin.injectionCustomBlock();
                    if(BX.Dellin.hasShowDellinMap)
                    {
                        ymaps.ready(()=>{
                            BX.Dellin.createYmapsObject();
                        })
                    }
                }

            }
           
            

            
        } else {

            BX.Dellin.styledFieldsSelect();
            BX.Dellin.hideTerminalBlock();
            BX.Dellin.removeInjectedBlock();
            BX.Dellin.showTimeIntervals();
            

        }

       
        BX.addCustomEvent('onAjaxSuccess', function(result){
            

            if(!result) return;
            if(!result.dellin) return;
            console.log(result.dellin);
            BX.Dellin.removeInjectedBlock();
            if(!BX.Dellin.isOldViewComponent)
            {
                BX.Dellin.hideTerminalBlock();
            }
            console.log(result);

            console.log('City is changed?', BX.Dellin.hasChangeCityCode());
            //
            if(BX.Dellin.hasChangeCityCode()){
                BX.Dellin.choosedTerminalId = null;
                BX.Dellin.setCityCode();
            }

            

            BX.Dellin.changePropsID(result);

            BX.Dellin.terminalList = result.dellin.terminals;
            BX.Dellin.terminalsMethod = result.dellin.terminals_method_id;

            //?????????, ?????? ?? ? ??? ????? ???????? "?? ?????????"
            if(BX.Dellin.isTerminalMethodSelected()){

                BX.Dellin.showTerminalBlock();
                BX.Dellin.init();
                BX.Dellin.styledFieldsSelect();
                BX.Dellin.hideTimeIntervals();
                if(!BX.Dellin.terminalList)
                {
                    console.warn('Terminal list is undefined');
                    return ;
                }

                let changeLink = document.querySelectorAll('a.bx-soa-editstep');
            
                //todo
                changeLink.forEach((element)=>{
                    element.addEventListener('click', (elem)=>{
                        console.log('<?=GetMessageJS("DELLINDEV_SHIPMENT_KLIK_NA_IZMENITQ")?>');
                        
                    });
                })

                

                BX.Dellin.showTerminalBlock();
                BX.Dellin.init();
                BX.Dellin.styledFieldsSelect();
                //if result is droped
                if(!BX.Dellin.terminalList)
                {
                    BX.Dellin.hideTerminalBlock();
                    console.warn('Terminal list is undefined');
                    return ;
                } 
                //restore block after droped result
                if(BX.Dellin.terminalList)
                {
                    let terminalBlock = BX.Dellin.getTerminalBlock();
                        terminalBlock.style = ''; 
                }

                if(BX.Dellin.isPickUpBlockSelected){
                    
                    if(BX.Dellin.isOldViewComponent)
                    {
                        console.warn('Is old view component.')
                    } else {
                        if(BX.Dellin.terminalList.length > 0){
                            BX.Dellin.hideTerminalBlock();
                            BX.Dellin.injectionCustomBlock();
                            if(BX.Dellin.hasShowDellinMap)
                            {
                                ymaps.ready(()=>{
                                    BX.Dellin.createYmapsObject();
                                })
                                
                            }
                        }

                    }

                }

                BX.Dellin.styledFieldsSelect();
                

            } else {

                console.log('Dellin init');
                BX.Dellin.styledFieldsSelect();
                BX.Dellin.hideTerminalBlock();
                BX.Dellin.removeInjectedBlock();
                BX.Dellin.showTimeIntervals();

            }




        });


    });





 
</script>