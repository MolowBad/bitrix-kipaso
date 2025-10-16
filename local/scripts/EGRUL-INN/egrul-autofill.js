/*
  /local/js/egrul-autofill.js
  Автозаполнение реквизитов компании по ИНН на странице оформления заказа.

  Настройка селекторов ниже. Можно переопределить через window.EGRUL_AUTOFILL_CONFIG до загрузки этого скрипта.
*/
(function(){
  'use strict';

  var DEFAULTS = {
    // Селектор поля ИНН (input)
    innSelector: [
      'input[name="INN"]',
      'input[name*="INN" i]',
      'input[name^="ORDER_PROP_" i][placeholder*="ИНН" i]',
      'input[name^="ORDER_PROP_" i][data-name*="ИНН" i]',
      'input[placeholder*="ИНН" i]'
    ].join(', ').trim(),
    // Поля, куда вставлять данные
    companyNameSelector: [
      'input[name*="COMPANY" i]',
      'input[name*="ORGANIZATION" i]',
      'input[name^="ORDER_PROP_" i][placeholder*="Название" i]',
      'input[name^="ORDER_PROP_" i][data-name*="Название" i]',
      'input[placeholder*="Название" i]'
    ].join(', ').trim(),
    legalAddressSelector: [
      'input[name*="ADDRESS" i]',
      'textarea[name*="ADDRESS" i]',
      'input[name^="ORDER_PROP_" i][placeholder*="Юридический адрес" i]',
      'textarea[name^="ORDER_PROP_" i][placeholder*="Юридический адрес" i]',
      'input[name^="ORDER_PROP_" i][data-name*="Юридический адрес" i]',
      'textarea[name^="ORDER_PROP_" i][data-name*="Юридический адрес" i]',
      'input[placeholder*="Юридический адрес" i]',
      'textarea[placeholder*="Юридический адрес" i]'
    ].join(', ').trim(),
    kppSelector: [
      'input[name*="KPP" i]',
      'input[name^="ORDER_PROP_" i][placeholder*="КПП" i]',
      'input[name^="ORDER_PROP_" i][data-name*="КПП" i]',
      'input[placeholder*="КПП" i]'
    ].join(', ').trim(),
    contactPersonSelector: [
      'input[name*="CONTACT" i]',
      'input[name*="FIO" i]',
      'input[name^="ORDER_PROP_" i][placeholder*="Контактное лицо" i]',
      'input[name^="ORDER_PROP_" i][data-name*="Контактное лицо" i]',
      'input[placeholder*="Контактное лицо" i]'
    ].join(', ').trim(),

    // URL AJAX-эндпоинта
    endpoint: '/local/ajax/egrul_lookup.php',

    // Задержка перед запросом (мс)
    debounceMs: 500,

    // Минимальная длина ИНН для запроса (10 или 12)
    minInnLength: 10,

    // Включить лог в консоль
    debug: false,
  };

  var CFG = window.EGRUL_AUTOFILL_CONFIG ? Object.assign({}, DEFAULTS, window.EGRUL_AUTOFILL_CONFIG) : DEFAULTS;

  // Полностью отключаем вывод в консоль для безопасности
  function log(){ /* no-op */ }

  function $(sel){ return document.querySelector(sel); }
  function $all(sel){ try { return Array.prototype.slice.call(document.querySelectorAll(sel)); } catch(e){ return []; } }

  function onlyDigits(v){ return (v||'').replace(/\D+/g,''); }

  function debounce(fn, wait){
    var t; return function(){
      var ctx = this, args = arguments; clearTimeout(t);
      t = setTimeout(function(){ fn.apply(ctx, args); }, wait);
    };
  }

  function fillValue(sel, val){
    if (!sel) return false;
    var el = $(sel);
    if (!el) return false;
    var v = (val==null? '' : String(val)).trim();
    if (v === '') return false; // не перезаписываем пустыми значениями
    if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
      if (el.value !== v) {
        el.value = v;
        el.dispatchEvent(new Event('input', {bubbles:true}));
        el.dispatchEvent(new Event('change', {bubbles:true}));
        // некоторые темы реагируют на blur
        try { el.blur(); setTimeout(function(){ el.focus(); }, 0); } catch(e){}
      }
    } else {
      if (el.textContent !== v) el.textContent = v;
    }
    return true;
  }

  // Поиск поля по тексту метки <label> (фолбэк)
  function findByLabelText(texts){
    texts = Array.isArray(texts) ? texts : [texts];
    var labels = $all('label');
    for (var i=0; i<labels.length; i++){
      var t = (labels[i].textContent || '').trim();
      for (var j=0; j<texts.length; j++){
        if (t.toLowerCase().indexOf(String(texts[j]).toLowerCase()) !== -1){
          // По for="id"
          var forId = labels[i].getAttribute('for');
          if (forId){
            var byId = document.getElementById(forId);
            if (byId && (byId.tagName === 'INPUT' || byId.tagName === 'TEXTAREA')) return byId;
          }
          // По близости в DOM
          var candidate = labels[i].nextElementSibling;
          while (candidate){
            if (candidate.tagName === 'INPUT' || candidate.tagName === 'TEXTAREA') return candidate;
            candidate = candidate.nextElementSibling;
          }
        }
      }
    }
    return null;
  }

  function fetchJSON(url){
    return fetch(url, { credentials: 'same-origin' }).then(function(r){
      if (!r.ok) throw new Error('HTTP '+r.status);
      return r.json();
    });
  }

  function buildUrl(base, params){
    var u = new URL(base, window.location.origin);
    Object.keys(params||{}).forEach(function(k){ if (params[k] !== undefined && params[k] !== null) u.searchParams.set(k, params[k]); });
    return u.toString();
  }

  var LAST_DATA = null;

  function applyData(d){
    if (!d) return;
    // Запомним последний результат для повторного применения
    LAST_DATA = {
      company_name: d.company_name || '',
      legal_address: d.legal_address || '',
      kpp: d.kpp || '',
      contact_person: d.contact_person || ''
    };
    // Компания
    if (!fillValue(CFG.companyNameSelector, d.company_name || '')){
      var el1 = findByLabelText(['Название','Наименование']); if (el1){ el1.value = d.company_name||''; el1.dispatchEvent(new Event('input',{bubbles:true})); }
    }
    // Юр. адрес
    if (!fillValue(CFG.legalAddressSelector, d.legal_address || '')){
      var el2 = findByLabelText(['Юридический адрес','Адрес регистрации']); if (el2){ el2.value = d.legal_address||''; el2.dispatchEvent(new Event('input',{bubbles:true})); }
    }
    // КПП (для ИП часто пусто — не затираем, если пусто)
    if (d.kpp){ if (!fillValue(CFG.kppSelector, d.kpp)) { var el3 = findByLabelText(['КПП']); if (el3){ el3.value = d.kpp; el3.dispatchEvent(new Event('input',{bubbles:true})); } } }
    // Контактное лицо
    if (!fillValue(CFG.contactPersonSelector, d.contact_person || '')){
      var el4 = findByLabelText(['Контактное лицо','ФИО']); if (el4){ el4.value = d.contact_person||''; el4.dispatchEvent(new Event('input',{bubbles:true})); }
    }
  }

  function onInnChanged(value){
    var inn = onlyDigits(value);
    if (inn.length !== 10 && inn.length !== 12) { log('ИНН не полной длины, пропуск'); return; }
    var url = buildUrl(CFG.endpoint, { inn: inn });
    log('Запрос', url);
    fetchJSON(url).then(function(res){
      if (!res || res.success !== true) throw new Error(res && res.error ? res.error : 'Unknown error');
      if (!res.data) { log('Нет результатов'); return; }
      applyData(res.data);
      // Повторные применения через несколько интервалов — на случай перерисовок формы Bitrix
      [200, 500, 1000, 1600].forEach(function(ms){ setTimeout(function(){ applyData(LAST_DATA); }, ms); });
    }).catch(function(e){ log('Ошибка', e && e.message ? e.message : e); });
  }

  var BOUND_INN_EL = null;
  function bindInnField(innInput){
    if (!innInput) return false;
    if (BOUND_INN_EL === innInput) return true; // уже привязан этот элемент
    // Отвяжем от старого, если есть
    try { if (BOUND_INN_EL && BOUND_INN_EL.__egrulHandler) {
      BOUND_INN_EL.removeEventListener('input', BOUND_INN_EL.__egrulHandler);
      BOUND_INN_EL.removeEventListener('change', BOUND_INN_EL.__egrulHandler);
      BOUND_INN_EL.removeEventListener('blur', BOUND_INN_EL.__egrulHandler);
    } } catch(e){}

    var handler = debounce(function(){ onInnChanged(innInput.value); }, CFG.debounceMs);
    innInput.__egrulHandler = handler;
    innInput.addEventListener('input', handler);
    innInput.addEventListener('change', handler);
    innInput.addEventListener('blur', handler);
    BOUND_INN_EL = innInput;
    log('Привязали обработчики к полю ИНН', innInput);
    if (innInput.value) handler();
    return true;
  }

  function init(){
    // первая попытка найти поле
    var found = bindInnField($(CFG.innSelector) || findByLabelText(['ИНН']));

    // Диагностика: покажем, какие элементы будут заполняться
    log('Поля автозаполнения:', {
      companyName: $(CFG.companyNameSelector) || findByLabelText(['Название','Наименование']),
      legalAddress: $(CFG.legalAddressSelector) || findByLabelText(['Юридический адрес','Адрес регистрации']),
      kpp: $(CFG.kppSelector) || findByLabelText(['КПП']),
      contactPerson: $(CFG.contactPersonSelector) || findByLabelText(['Контактное лицо','ФИО'])
    });

    // На страницах Bitrix order форма может перерисовываться. Следим за DOM и перепривязываем при необходимости.
    try {
      var mo = new MutationObserver(function(){
        var current = $(CFG.innSelector) || findByLabelText(['ИНН']);
        if (current && current !== BOUND_INN_EL) {
          bindInnField(current);
          // При появлении новых полей попробуем повторно применить последние данные, если они есть
          if (LAST_DATA) { applyData(LAST_DATA); }
        } else if (LAST_DATA) {
          // Даже без смены input могли перерисоваться другие поля — обновим их при наличии данных
          applyData(LAST_DATA);
        }
      });
      mo.observe(document.body, { childList: true, subtree: true });
      log('MutationObserver активирован для отслеживания формы заказа');
    } catch(e) { log('MutationObserver error', e); }

    if (!found) log('Поле ИНН пока не найдено, ожидаем перерисовку формы');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
