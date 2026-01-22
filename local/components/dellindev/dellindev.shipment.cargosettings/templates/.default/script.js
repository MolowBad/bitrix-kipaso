BX.namespace('BX.Sale.Dellin.CargoSettings');

BX.Sale.Dellin.CargoSettings =
{
	ajaxUrl: "",
	interruptFlag: false,
	requestFlag: false,
	serviceLocationClass: "",
	isError: false,
	viewMsg: '',// ï¿½ï¿½ï¿½ï¿½ ï¿½ï¿½ï¿½-ï¿½ï¿½ ï¿½ï¿½ ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½, ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ ï¿½ï¿½ï¿½ï¿½ï¿½ ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½
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


		//TODO ˜˜˜˜˜˜˜ ˜˜˜ ˜˜˜˜˜˜˜.
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

			//add block KLADinput

			return divAutocomplete;


	},
	buildRowAutocompolite: function (el) {
		let divRow = document.createElement('div');
			divRow.className = 'autocomplete-row';
			divRow.dataset.id = el.code;
			divRow.innerHTML = el.city+' ['+el.code+'] ';

			divRow.addEventListener('click', function () {
				let fieldFrieghtTypeName = document.querySelector('[name="CONFIG[CARGO][FREIGHT_TYPE_NAME]"]');
					fieldFrieghtTypeName.value = el.city;
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