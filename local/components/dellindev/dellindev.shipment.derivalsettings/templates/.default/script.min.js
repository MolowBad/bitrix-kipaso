/**
 * AJAX ���������.
 *
 * �������� �� ��������� ������ ������.
 * ��������� ���� ��� ��������� �������� ����� �����(��������� ��� ������������� ����������).
 *
 * @company BIA-Tech
 * @author Lazev Vadim
 * @year 2021
 */

// �������������� ������������ ���.
BX.namespace('BX.Sale.Dellin.DerivalSettings');

// � ������������� ������� ��������� ������ ��������������.
BX.Sale.Dellin.DerivalSettings =
{
	ajaxUrl: "",
	interruptFlag: false,
	requestFlag: false,
	serviceLocationClass: "",
	isError: false,
	viewMsg: '',// ���� ���-�� �� ������ ���������, ��������� ����� ���������
	errorMsg: null,
	inputDisabled: false,
	cityKladr: null,
	isOpen: false,

	getAppkey: function(){
		return document.querySelector('[name="CONFIG[MAIN][APIKEY]"]')
	},
	setAnswer: function (answer) {

		// if(!answer)
		// {
		// 	//TODO ������� ����� ��������� �� ������.
		// 	// BX.Sale.Location.Map.pb.showError(BX.message('SALE_DLVRS_ADD_LOC_COMP_AJAX_ERROR'));
		// 	return;
		// }
		console.log(answer);
		this.answerData = answer;
		BX.Sale.Dellin.DerivalSettings.loadingData = false;
	},
	getTerminalDerival: function()
	{
		let appkey = this.getAppkey().value;
		let kladr = document.querySelector('[name="CONFIG[DERIVAL][CITY_KLADR]"]').value;

		if (!appkey) return ;
		if (kladr.length > 23){

			let postData = {
				sessid: BX.bitrix_sessid(),
				action: 'get_termianl_derival',
				appkey: appkey,
				kladr: kladr,
				class: BX.Sale.Dellin.DerivalSettings.serviceAjaxClass
			};

		 let response = 	BX.ajax({
				timeout:    300,
				method:     'POST',
				dataType:   'json',
				url:        BX.Sale.Dellin.DerivalSettings.ajaxUrl,
				data:       postData,

				onsuccess: function(result)
				{

					let fieldTerminalList = document.querySelector('[name="CONFIG[DERIVAL][TERMINAL_ID]"]');
					if (!result) return;

					console.log('Terminal LIST', result.TERMINALS);
					console.log('added term', BX.Sale.Dellin.DerivalSettings.clearOptionInTerminalList());
						 result.TERMINALS.map(function (terminal) {

						 	let option = document.createElement('option');
						 		option.innerHTML = terminal.address;
						 		option.value = terminal.id;
						 		option.id = 'terminal-added';
							 	option.selected =  (fieldTerminalList.dataset.value == terminal.id);
							    fieldTerminalList.appendChild(option);
						 })
				//	BX.Dellin.

				},

				onfailure: function(status)
				{
					console.log("onfailture", status);
				}
			});

			return response;

		}

	},
	clearOptionInTerminalList: function(){
		let allTerminalInSelect = document.querySelectorAll('#terminal-added');
			if(!allTerminalInSelect) return;
			allTerminalInSelect.forEach(function (el) {
				el.parentNode.removeChild(el)
			})

	},

	displayLoading: function () {

		this.hideLoading();
		let tabId = document.querySelector('#edit_additional_tab_0');
		let divWrap = document.createElement('div');
			divWrap.className = 'loading-wrap';

		let divLoading = document.createElement('div');
			divLoading.className = 'loading';

		let spanContent = document.createElement('span');
			spanContent.className = 'loading-content';
			spanContent.innerHTML = BX.message("PROCESSING");

		let img = document.createElement('img');
			img.src = '/bitrix/js/main/core/images/wait.gif';
			spanContent.appendChild(img);
			divLoading.appendChild(spanContent);

			tabId.appendChild(divWrap);
			tabId.appendChild(divLoading);


	},

	hideLoading: function()
	{
		let loadingWrap = document.querySelector('.loading-wrap');
		let loading = document.querySelector('.loading');
		if(loadingWrap == null){
			return '';
		}

		if(loadingWrap == undefined){
			return '';
		}

		loadingWrap.remove();
		loading.remove();
		// $('.loading-wrap').remove();
		// $('.loading').remove();
	},

	validateAppkey: function(){
		let value = this.getAppkey().value;
		if(!value){
			this.isError = true;
			this.inputDisabled = true;
			this.errorMsg= BX.message("DELLINDEV_FIELD_APPKEY_UNDEFIEND");
		}

		if(value.length <=0){
			this.isError = true;
			this.inputDisabled = true;
			this.errorMsg = BX.message("DELLINDEV_FIELD_APPKEY_UNDEFIEND");
		}
	},

	getViewMsg: function () {
		let spanMsg = document.createElement('span');
		spanMsg.innerHTML = this.viewMsg;


		//���������� ���� ����� ���
		this.validateAppkey();


		//���� ��������� ���������� � ������, ��������� ���� � ��������
		if(this.isErrors){
			let br = document.createElement('br');
			spanMsg.appendChild(br);
			spanMsg.appendChild(this.getErrors());
		}



		return spanMsg;
	},
	getErrors: function(){

		if(this.isError == false){
			return '' ;
		}

		let spanError = document.createElement('span');
			spanError.className = 'error';
			spanError.innerHTML = this.errorMsg;
		return spanError;
	},

	getNode: function()
	{

		let KLADRdiv = document.createElement('div');
			KLADRdiv.className = 'kladrDiv popup-container';

		let spanMsg = this.getViewMsg();
			KLADRdiv.appendChild(spanMsg);


		let inputKLADR = document.createElement('input');
			inputKLADR.name = 'query';
			inputKLADR.className = 'kladr_autocomplete';
			inputKLADR.addEventListener('keyup', function (e) {


				if(this.value.length > 3){
					let postData = {
						sessid: BX.bitrix_sessid(),
						action: 'get_city_kladr',
						query: this.value,
						class: BX.Sale.Dellin.DerivalSettings.serviceAjaxClass
					};


					BX.ajax({
						timeout:    300,
						method:     'POST',
						dataType:   'json',
						url:        BX.Sale.Dellin.DerivalSettings.ajaxUrl,
						data:       postData,

						onsuccess: function(result)
						{

							let fields = BX.Sale.Dellin.DerivalSettings.getAutoComplete(result);
							inputKLADR.after(fields);
						},

						onfailure: function(status)
						{

							console.log("onfailture", status);
						}
					});

				// console.log(request);



				}

			});
			
		let removeButton = document.createElement('input');
			removeButton.type = 'button';
			removeButton.className = 'close';
			removeButton.value = BX.message('BUTTON_CLOSE');

			removeButton.addEventListener('click', function (e) {
				console.log('this.isOpen state:', BX.Sale.Dellin.DerivalSettings.isOpen);
				if(BX.Sale.Dellin.DerivalSettings.isOpen){
					BX.Sale.Dellin.DerivalSettings.isOpen = !BX.Sale.Dellin.DerivalSettings.isOpen;
					KLADRdiv.remove();
				}

				console.log('this.isOpen state:', BX.Sale.Dellin.DerivalSettings.isOpen);


			});

		KLADRdiv.appendChild(inputKLADR);
		KLADRdiv.appendChild(removeButton);

		//TODO ����������� ���� ��� KLADRDiv ���������� �������� ����.


		return KLADRdiv;


	},
	displayFieldsOnMethod(){

		let fieldChangeMethod = document.querySelector('[name="CONFIG[DERIVAL][GOODSLOADING]"]');
		let rowTerminalId = document.querySelector('#terminal_derival');
		let	rowAddress = document.querySelector('#address_derival');
		let blockWorkOnlySchemeAddress = document.querySelector('#address_additional');
			if(fieldChangeMethod.value == '0'){
				rowTerminalId.style = '';
				rowAddress.style = 'display:none';
				blockWorkOnlySchemeAddress.style = 'display:none';
			} else {
				rowAddress.style = '';
				rowTerminalId.style = 'display:none';
				blockWorkOnlySchemeAddress.style = '';
			}

			console.log(fieldChangeMethod.value);
	},
	getAutoComplete: function(result){


		//TODO ������� ��� �������.
		BX.Sale.Dellin.DerivalSettings.clearAutoComplete();

		let divAutocomplete = document.createElement('div');
			divAutocomplete.className = 'autocomplete';
		let divRows = document.createElement('div');
			divRows.className = 'rows';


			result.LIST.forEach(function (el) {

			let row = BX.Sale.Dellin.DerivalSettings.buildRowAutocompolite(el);

				divRows.appendChild(row);

			});

			console.log(divRows);
			divAutocomplete.appendChild(divRows);

			console.log(divAutocomplete);

			//add block KLADinput

			return divAutocomplete;


	},
    buildRowAutocompolite: function(el) {
        let divRow = document.createElement('div');
        divRow.className = 'autocomplete-row';
        divRow.dataset.id = el.code;
        divRow.innerHTML = el.aString + ' [' + el.code + '] ';
        divRow.addEventListener('click', function() {
            let fieldCityName = document.querySelector('[name="CONFIG[DERIVAL][CITY_NAME]"]');
            fieldCityName.value = el.aString;
            let fieldCityKLADR = document.querySelector('[name="CONFIG[DERIVAL][CITY_KLADR]"]');
            fieldCityKLADR.value = this.dataset.id; console.log('Your select KLADR:', this.dataset.id);
            BX.Sale.Dellin.DerivalSettings.getTerminalDerival();
            BX.Sale.Dellin.DerivalSettings.clearAutoComplete();
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
	showBlockInputAutoComplete: function()
	{
		//��������� ��������. �� ��������� ���������.
		let button = document.querySelector('#findKladr');
		if(!this.isOpen){
			let afterButton = this.getNode();
				button.after(afterButton);
			this.isOpen = !this.isOpen;
		} else {
		let KLADRdiv = document.querySelector('.popup-container');
			KLADRdiv.remove();
			this.isOpen = !this.isOpen;
		}
	},
    selectCity: function() {
        let input = document.querySelector('#cityField');
        let appkey = this.getAppkey().value
        if (input.value.length > 3) {
            let postData = {
                sessid: BX.bitrix_sessid(),
                appkey: appkey,
                action: 'get_city_kladr',
                query: input.value,
                class: BX.Sale.Dellin.DerivalSettings.serviceAjaxClass
            };
            BX.ajax({
                timeout: 300,
                method: 'POST',
                dataType: 'json',
                url: BX.Sale.Dellin.DerivalSettings.ajaxUrl,
                data: postData,
                onsuccess: function(result) {
                    let fields = BX.Sale.Dellin.DerivalSettings.getAutoComplete(result);
                    input.after(fields);
                },
                onfailure: function(status) {
                    console.log("onfailture", status);
                }
            });
        }
    }

};







