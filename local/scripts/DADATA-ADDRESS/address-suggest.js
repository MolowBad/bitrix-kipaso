/* /local/scripts/DADATA-ADDRESS/address-suggest.js
   Подсказки адресов (город, улица, дом) через DaData Suggestions API с серверным прокси.
   
*/
(function(){
  'use strict';

  var DEFAULTS = {
    
    citySelector: [
      'input[name*="CITY" i]',
      'input[name^="ORDER_PROP_" i][placeholder*="Город" i]',
      'input[name^="ORDER_PROP_" i][data-name*="Город" i]'
    ].join(', '),
    streetSelector: [
      'input[name*="STREET" i]',
      'input[name^="ORDER_PROP_" i][placeholder*="Улица" i]',
      'input[name^="ORDER_PROP_" i][data-name*="Улица" i]'
    ].join(', '),
    houseSelector: [
      'input[name*="HOUSE" i]',
      'input[name^="ORDER_PROP_" i][placeholder*="Дом" i]',
      'input[name^="ORDER_PROP_" i][data-name*="Дом" i]'
    ].join(', '),

 
    endpoint: '/local/ajax/dadata_address.php',

    // Максимум результатов
    count: 10,

    
    language: 'ru',

    
    debounceMs: 250,

    // Включить консольный лог
    debug: false,
  };

  var CFG = window.ADDRESS_SUGGEST_CONFIG ? Object.assign({}, DEFAULTS, window.ADDRESS_SUGGEST_CONFIG) : DEFAULTS;

  function log(){ /* no-op */ }

  function $(sel){ try { return document.querySelector(sel); } catch(e){ return null; } }
  function $all(sel){ try { return Array.prototype.slice.call(document.querySelectorAll(sel)); } catch(e){ return []; } }

  function findByLabelText(texts){
    texts = Array.isArray(texts) ? texts : [texts];
    var labels = $all('label');
    for (var i=0;i<labels.length;i++){
      var t = (labels[i].textContent||'').trim().toLowerCase();
      for (var j=0;j<texts.length;j++){
        var needle = String(texts[j]).toLowerCase();
        if (t.indexOf(needle) !== -1){
          var forId = labels[i].getAttribute('for');
          if (forId){
            var el = document.getElementById(forId);
            if (el && (el.tagName==='INPUT' || el.tagName==='TEXTAREA')) return el;
          }
          var sib = labels[i].nextElementSibling;
          while(sib){ if (sib.tagName==='INPUT'){ return sib; } sib = sib.nextElementSibling; }
        }
      }
    }
    return null;
  }

  function debounce(fn, wait){ var t; return function(){ var ctx=this,args=arguments; clearTimeout(t); t=setTimeout(function(){ fn.apply(ctx,args); }, wait); }; }

  function createDropdown(input){
    var dd = document.createElement('div');
    dd.className = 'addr-suggest-dd';
    dd.style.position = 'absolute';
    dd.style.zIndex = 10000;
    dd.style.background = '#fff';
    dd.style.border = '1px solid #ccc';
    dd.style.borderTop = 'none';
    dd.style.minWidth = (input.offsetWidth || 240) + 'px';
    dd.style.maxHeight = '260px';
    dd.style.overflowY = 'auto';
    dd.style.boxShadow = '0 4px 12px rgba(0,0,0,0.08)';
    dd.style.display = 'none';
    document.body.appendChild(dd);
    function place(){
      var r = input.getBoundingClientRect();
      dd.style.left = (window.pageXOffset + r.left) + 'px';
      dd.style.top = (window.pageYOffset + r.bottom) + 'px';
      dd.style.minWidth = (r.width) + 'px';
    }
    place();
    window.addEventListener('resize', place);
    window.addEventListener('scroll', place, true);
    return dd;
  }

  function showDropdown(dd){ dd.style.display = 'block'; }
  function hideDropdown(dd){ dd.style.display = 'none'; }
  function clearDropdown(dd){ dd.innerHTML = ''; }

  function fetchJSON(url, payload){
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(payload||{})
    }).then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); });
  }

  function renderItems(dd, items, onPick){
    clearDropdown(dd);
    if (!items || !items.length){ hideDropdown(dd); return; }
    items.forEach(function(s){
      var div = document.createElement('div');
      div.textContent = s.value || '';
      div.style.padding = '8px 10px';
      div.style.cursor = 'pointer';
      div.addEventListener('mouseenter', function(){ div.style.background = '#f5f5f5'; });
      div.addEventListener('mouseleave', function(){ div.style.background = '#fff'; });
      div.addEventListener('mousedown', function(e){ e.preventDefault(); });
      div.addEventListener('click', function(){ onPick(s); hideDropdown(dd); });
      dd.appendChild(div);
    });
    showDropdown(dd);
  }

  var state = { city: null, street: null };

  function bindCity(input){
    if (!input) return;
    var dd = createDropdown(input);
    var handler = debounce(function(){
      var q = (input.value||'').trim();
      if (q.length < 2){ hideDropdown(dd); return; }
      fetchJSON(CFG.endpoint, {
        query: q,
        count: CFG.count,
        language: CFG.language,
        from_bound: { value: 'city' },
        to_bound: { value: 'city' }
      }).then(function(res){
        var items = (res && res.suggestions) ? res.suggestions : [];
        renderItems(dd, items, function(s){
          input.value = s.data.city_with_type || s.value || '';
          input.dispatchEvent(new Event('input',{bubbles:true}));
          input.dispatchEvent(new Event('change',{bubbles:true}));
          state.city = s; state.street = null;
        });
      }).catch(function(){ hideDropdown(dd); });
    }, CFG.debounceMs);
    input.addEventListener('input', handler);
    input.addEventListener('focus', handler);
    document.addEventListener('click', function(e){ if (!dd.contains(e.target) && e.target!==input) hideDropdown(dd); });
  }

  function bindStreet(input, cityInput){
    if (!input) return;
    var dd = createDropdown(input);
    var handler = debounce(function(){
      var q = (input.value||'').trim();
      if (q.length < 2){ hideDropdown(dd); return; }
      var locations = [];
      if (state.city && state.city.data && state.city.data.city_fias_id){
        locations.push({ city_fias_id: state.city.data.city_fias_id });
      } else if (cityInput && cityInput.value){
        
        locations.push({ city: cityInput.value });
      }
      fetchJSON(CFG.endpoint, {
        query: q,
        count: CFG.count,
        language: CFG.language,
        from_bound: { value: 'street' },
        to_bound: { value: 'street' },
        locations: locations
      }).then(function(res){
        var items = (res && res.suggestions) ? res.suggestions : [];
        renderItems(dd, items, function(s){
          input.value = s.data.street_with_type || s.value || '';
          input.dispatchEvent(new Event('input',{bubbles:true}));
          input.dispatchEvent(new Event('change',{bubbles:true}));
          state.street = s;
        });
      }).catch(function(){ hideDropdown(dd); });
    }, CFG.debounceMs);
    input.addEventListener('input', handler);
    input.addEventListener('focus', handler);
    document.addEventListener('click', function(e){ if (!dd.contains(e.target) && e.target!==input) hideDropdown(dd); });
  }

  function bindHouse(input, cityInput, streetInput){
    if (!input) return;
    var dd = createDropdown(input);
    var handler = debounce(function(){
      var q = (input.value||'').trim();
      if (q.length < 1){ hideDropdown(dd); return; }
      var locations = [];
      var filters = {};
      if (state.street && state.street.data && state.street.data.street_fias_id){
        locations.push({ street_fias_id: state.street.data.street_fias_id });
      } else if (state.city && state.city.data && state.city.data.city_fias_id){
        locations.push({ city_fias_id: state.city.data.city_fias_id });
        filters.street_q = (streetInput && streetInput.value) || undefined;
      } else if (cityInput && cityInput.value){
        locations.push({ city: cityInput.value });
        filters.street_q = (streetInput && streetInput.value) || undefined;
      }
      var payload = Object.assign({
        query: q,
        count: CFG.count,
        language: CFG.language,
        from_bound: { value: 'house' },
        to_bound: { value: 'house' },
        locations: locations
      }, filters);
      fetchJSON(CFG.endpoint, payload).then(function(res){
        var items = (res && res.suggestions) ? res.suggestions : [];
        renderItems(dd, items, function(s){
          input.value = s.data.house || s.value || '';
          input.dispatchEvent(new Event('input',{bubbles:true}));
          input.dispatchEvent(new Event('change',{bubbles:true}));
        });
      }).catch(function(){ hideDropdown(dd); });
    }, CFG.debounceMs);
    input.addEventListener('input', handler);
    input.addEventListener('focus', handler);
    document.addEventListener('click', function(e){ if (!dd.contains(e.target) && e.target!==input) hideDropdown(dd); });
  }

  function init(){
    var cityEl   = $(CFG.citySelector) || findByLabelText(['Город']);
    var streetEl = $(CFG.streetSelector) || findByLabelText(['Улица']);
    var houseEl  = $(CFG.houseSelector) || findByLabelText(['Дом']);

    if (!cityEl && !streetEl && !houseEl) return;

    bindCity(cityEl);
    bindStreet(streetEl, cityEl);
    bindHouse(houseEl, cityEl, streetEl);

    
    try {
      var mo = new MutationObserver(function(){
        var c = $(CFG.citySelector) || findByLabelText(['Город']);
        var s = $(CFG.streetSelector) || findByLabelText(['Улица']);
        var h = $(CFG.houseSelector) || findByLabelText(['Дом']);
        if (c && c !== cityEl) { cityEl = c; bindCity(cityEl); }
        if (s && s !== streetEl) { streetEl = s; bindStreet(streetEl, cityEl); }
        if (h && h !== houseEl) { houseEl = h; bindHouse(houseEl, cityEl, streetEl); }
      });
      mo.observe(document.body, { childList: true, subtree: true });
    } catch(e){}
  }

  if (document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', init);
  } else { init(); }
})();
