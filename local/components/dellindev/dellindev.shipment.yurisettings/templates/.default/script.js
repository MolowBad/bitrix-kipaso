BX.namespace("BX.Sale.Dellin.YuriSettings");

BX.Sale.Dellin.YuriSettings =
	{
		ajaxUrl: "",
		serviceLocationClass: "",
		getLogin: function(){
			return  document.querySelector('[name="CONFIG[MAIN][LOGIN]"]');
		},

		getPass: function(){
			return document.querySelector('[name="CONFIG[MAIN][PASSWORD]"]');
		},

		getCounterAgents: function () {


			let counteragentField = document.querySelector('[name="CONFIG[YURI][COUNTERAGENT]"]');
			let appkey = BX.Sale.Dellin.DerivalSettings.getAppkey().value;
			let login = this.getLogin().value;
			let pass = this.getPass().value;

			let postData = {
				sessid: BX.bitrix_sessid(),
				appkey: appkey,
				login: login,
				password: pass,
				action: 'get_counteragents',
				class: BX.Sale.Dellin.DerivalSettings.serviceAjaxClass
			};

			BX.ajax({
				timeout:    300,
				method:     'POST',
				dataType:   'json',
				url:        BX.Sale.Dellin.YuriSettings.ajaxUrl,
				data:       postData,

				onsuccess: function(result)
				{

					if (!result) return;

					 result.counteragents.map(function (counteragent) {

						BX.Sale.Dellin.YuriSettings.builderOptionList(counteragentField,
																	  counteragent.uid,
																	  counteragent.name,
																	  'counteragent');
					})

				},

				onfailure: function(status)
				{
					console.log("onfailture", status);
				}
			});

		},

		builderOptionList:function(field, id, name, type){
			let option = document.createElement('option');
				option.innerHTML = name;
				option.value = id;
				option.id = type+'-added';
				option.selected =  (field.dataset.value == id);
				field.appendChild(option);

		},
		getOpfData: function () {
			let appkey = BX.Sale.Dellin.DerivalSettings.getAppkey().value;

			if (!appkey) return;

			let postData = {
				sessid: BX.bitrix_sessid(),
				appkey: appkey,
				action: 'get_opf',
				class: BX.Sale.Dellin.DerivalSettings.serviceAjaxClass
			};

			let getFieldCountry = document.querySelector('[name="CONFIG[YURI][OPF_COUNTRY]"]');
			let getFieldOpf = document.querySelector('[name="CONFIG[YURI][OPF]"]');


			BX.ajax({

				timeout: 300,
				method: 'POST',
				dataType: 'json',
				url: BX.Sale.Dellin.YuriSettings.ajaxUrl,
				data: postData,

				onsuccess: function (result) {
					console.log(result);
					if (!result) return;

					Object.values(result.countries)
						.forEach(function (country) {
							let id = country.countryUID;
							let name = country.country;
							BX.Sale.Dellin.YuriSettings.builderOptionList(getFieldCountry, id, name, 'country');
						});

					getFieldCountry.addEventListener('change', function () {

						BX.Sale.Dellin.YuriSettings.clearOpfList();
						BX.Sale.Dellin.YuriSettings.getOpfList(getFieldCountry, getFieldOpf, result);
						getFieldOpf.dataset.value = false;
					});
					if(getFieldOpf.dataset.value){
						BX.Sale.Dellin.YuriSettings.getOpfList(getFieldCountry, getFieldOpf, result);
					}
				},
				onfailure: function (status) {
					console.log("onfailture", status);
				}
			});

		},
		getOpfList: function (fieldCountry, fieldOpf, result) {

			for (let i = 0; i < Object.keys(result.opf).length; i++) {

				if (Object.keys(result.opf)[i] == fieldCountry.value) {
					//console.log('values', Object.values(result.opf)[i]);
					let values = Object.values(result.opf)[i];
					let entries = Object.entries(values);
					entries.map(function (item) {
						let id = item[0];
						let name = item[1];
						BX.Sale.Dellin.YuriSettings.builderOptionList(fieldOpf, id, name, 'opf');
					})

				}

			}

		},
		clearOpfList: function () {
			let opfInSelect = document.querySelectorAll('#opf-added');
			console.log(opfInSelect);
			if(!opfInSelect) return;
			opfInSelect.forEach(function (el) {
				el.parentNode.removeChild(el)
			});
		}

	};

