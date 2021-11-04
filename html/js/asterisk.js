(() => {

  // check the visiblility of the page
  let hidden, visibilityState, visibilityChange;

  if (typeof document.hidden !== "undefined") {
      hidden = "hidden", visibilityChange = "visibilitychange", visibilityState = "visibilityState";
  }
  else if (typeof document.mozHidden !== "undefined") {
      hidden = "mozHidden", visibilityChange = "mozvisibilitychange", visibilityState = "mozVisibilityState";
  }
  else if (typeof document.msHidden !== "undefined") {
      hidden = "msHidden", visibilityChange = "msvisibilitychange", visibilityState = "msVisibilityState";
  }
  else if (typeof document.webkitHidden !== "undefined") {
      hidden = "webkitHidden", visibilityChange = "webkitvisibilitychange", visibilityState = "webkitVisibilityState";
  }


  if (typeof document.addEventListener === "undefined" || typeof hidden === "undefined") {
      // not supported
  }
  else {
      document.addEventListener(visibilityChange, function() {
          switch (document[visibilityState]) {
          case "visible":
              window.focused = true;
              break;
          case "hidden":
              window.focused = false;
              break;
          }
      }, false);
  }

  if (document[visibilityState] === "visible") {
      window.focused = true;
  }

})();  

// First, checks if it isn't implemented yet.
if (!String.prototype.format) {
  String.prototype.format = function() {
    var args = arguments;
    return this.replace(/{(\d+)}/g, function(match, number) { 
      return typeof args[number] != 'undefined'
        ? args[number]
        : match
      ;
    });
  };
}

Number.prototype.zeroPad = Number.prototype.zeroPad || 
  function(base){
    var nr = this, len = (String(base).length - String(nr).length)+1;
    return len > 0? new Array(len).join('0')+nr : nr;
};


if(!Array.prototype.equals) {
  Array.prototype.equals = function (array) {
    // if the other array is a falsy value, return
    if (!array)
        return false;

    // compare lengths - can save a lot of time 
    if (this.length != array.length)
        return false;

    for (var i = 0, l=this.length; i < l; i++) {
        // Check if we have nested arrays
        if (this[i] instanceof Array && array[i] instanceof Array) {
            // recurse into the nested arrays
            if (!this[i].equals(array[i]))
                return false;       
        }           
        else if (this[i] != array[i]) { 
            // Warning - two different object instances will never be equal: {x:20} != {x:20}
            return false;   
        }           
    }       
    return true;
  }
  // Hide method from for-in loops
  Object.defineProperty(Array.prototype, "equals", {enumerable: false});
}

if(!Array.prototype.indexOf) {
  Array.prototype.indexOf = function (value) {
    for (var i = 0; i < this.length; i++) {
      if (this[i] === value) return i;
    }
    return -1;
  }
  // Hide method from for-in loops
  Object.defineProperty(Array.prototype, "indexOf", {enumerable: false});
}

if(!Array.prototype.indexOfId) {
  Array.prototype.indexOfId = function (value) {
    for (var i = 0; i < this.length; i++) {
      if (this[i].id === value) return i;
    }
    return -1;
  }
  // Hide method from for-in loops
  Object.defineProperty(Array.prototype, "indexOfId", {enumerable: false});
}

if(!Array.prototype.move) {
  Array.prototype.move = function (old_index, new_index) {
    while (old_index < 0) {
        old_index += this.length;
    }
    while (new_index < 0) {
        new_index += this.length;
    }
    if (new_index >= this.length) {
        var k = new_index - this.length + 1;
        while (k--) {
            this.push(undefined);
        }
    }
    this.splice(new_index, 0, this.splice(old_index, 1)[0]);
    return this; // for testing purposes
  }
  Object.defineProperty(Array.prototype, "move", {enumerable: false});
}

if(!Object.prototype.equals) {
  Object.defineProperty(Object.prototype, 'equals', { value: function(object) {
    if(!object instanceof Object) return false;
    const keys1 = Object.keys(this);
    const keys2 = Object.keys(object);

    if (keys1.length !== keys2.length) {
      return false;
    }

    for (const key of keys1) {
      const val1 = this[key];
      const val2 = object[key];
      const areObjects = (val1 != null && typeof val1 === 'object') && (val2 != null && typeof val2 === 'object');
      if (
        areObjects && !val1.equals(val2) || !areObjects && val1 !== val2
      ) {
        return false;
      }
    }

    return true;
  }, enumerable: false});
}

function _extends() {
  _extends = Object.assign || function (target) {
    for (var i = 1; i < arguments.length; i++) {
      var source = arguments[i];

      for (var key in source) {
        if (Object.prototype.hasOwnProperty.call(source, key)) {
          target[key] = source[key];
        }
      }
    }

    return target;
  };

  return _extends.apply(this, arguments);
}

const {
  colors,
  CssBaseline,
  ThemeProvider,
  makeStyles,
  createMuiTheme,
  createSvgIcon,
  SvgIcon,
  Typography,
} = MaterialUI;

var decCache = [],
decCases = [2, 0, 1, 1, 1, 2];
function decOfNum(number, titles)
{
    if(!decCache[number]) decCache[number] = number % 100 > 4 && number % 100 < 20 ? 2 : decCases[Math.min(number % 10, 5)];
    return titles[decCache[number]];
}

function getDate(date) {
  var day = "0" + date.getDate();
  var month = "0" + (date.getMonth()+1);
  var year = date.getYear()+1900;
  return  day.substr(-2) + '.' + month.substr(-2) + '.' + year;
}

function getTime(date) {
  var hours = date.getHours();
  var minutes = "0" + date.getMinutes();
  var seconds = "0" + date.getSeconds();
  return hours + ':' + minutes.substr(-2) + ':' + seconds.substr(-2);
}

function getDateTime(date) {
  var day = "0" + date.getDate();
  var month = "0" + (date.getMonth()+1);
  var year = date.getYear()+1900;
  var hours = "0" + date.getHours();
  var minutes = "0" + date.getMinutes();
  var seconds = "0" + date.getSeconds();
  return  day.substr(-2) + '.' + month.substr(-2) + '.' + year + ' ' + hours.substr(-2) + ':' + minutes.substr(-2) + ':' + seconds.substr(-2);
}

function getUTCTime(date) {
  var hours = date.getUTCHours();
  var minutes = "0" + date.getUTCMinutes();
  var seconds = "0" + date.getUTCSeconds();
  return hours + ':' + minutes.substr(-2) + ':' + seconds.substr(-2);
}

function UTC(millis) {
  var d = new Date(millis);
  return d;
}

var chunkedTimer = null;
var chunkedData = [];

function isEmpty(obj) {
  if(isSet(obj)) {
      if (obj.length && obj.length > 0) { 
          return false;
      }
  
      for (var key in obj) {
          if (hasOwnProperty.call(obj, key)) {
              return false;
          }
      }
  }
  return true;    
};

function isSet(val) {
  if ((val !== undefined) && (val !== null)){
      return true;
  }
  return false;
};

function postRPCJson(uri, data) {
  let resulthandlers = {always: null, done: null, fail: null};
  let resultactions = {always: function(alwaysFunc) {resulthandlers.always = alwaysFunc; return resultactions;}, done: function(doneFunc) {resulthandlers.done = doneFunc; return resultactions;}, fail: function(failFunc) {resulthandlers.fail = failFunc; return resultactions;}};
  let req = new XMLHttpRequest();
  req.open('POST', uri, true);
  req.responseType = 'arraybuffer';
  //req.setRequestHeader('Content-Type', 'multipart/form-data');
  req.onload = result => {
    let json = null;
    if(req.status>=300) {
      if(resulthandlers.fail) resulthandlers.fail(req);
      if(resulthandlers.always) resulthandlers.always(req);
    } else {
      json = new Uint8Array(req.response);
      let data = json.reduce((data, byte) => data + String.fromCharCode(byte), '');
      try {
        json = JSON.parse(data);
      } catch {
        json = null;
      }
      if(json) {
        if(resulthandlers.done) resulthandlers.done(json);
        if(resulthandlers.always) resulthandlers.always(json);
      } else {
        json = 'data:'+req.getResponseHeader('content-type')+';base64,'+window.btoa(data);
        if(resulthandlers.done) resulthandlers.done(json);
        if(resulthandlers.always) resulthandlers.always(json);
      }
    }
  }
  req.onerror = result => {
    if(resulthandlers.fail) resulthandlers.fail(req);
    if(resulthandlers.always) resulthandlers.always(req);
  }
  req.onabort = result => {
    if(resulthandlers.always) resulthandlers.always(req);
  }

  if(!(data instanceof FormData)) {
    let formdata = new FormData();
  
    let add = function(key, valueOrFunction) {
  
        // If value is a function, invoke it and use its return value
        let value = (typeof valueOrFunction == 'function') ?
          valueOrFunction() :
          valueOrFunction;
  
        formdata.append(key, value == null ? "" : value);
      };
  
    let buildParams = function(prefix, obj) {
      let name;
    
      if(Array.isArray(obj)) {
    
        if(obj.length > 0) {
          // Serialize array item.
          for(let i in obj) {
            // Item is non-scalar (array or object), encode its numeric index.
            buildParams(
              prefix + "[" + ( typeof obj[i] === "object" && obj[i] != null ? i : "" ) + "]",
              obj[i]
            );
          };
        } else {
          buildParams(
            prefix,
            false
          );     
        }
    
      } else if((typeof obj === "object") && !(obj instanceof File) && !(obj instanceof Blob)) {
    
        // Serialize object item.
        for(name in obj) {
          buildParams(prefix + "[" + name + "]", obj[ name ]);
        }
    
      } else {
    
        // Serialize scalar item.
        add(prefix, obj);
      }
    }
    
    for(key in data) {
      buildParams(key, data[key]);
    }
    data = formdata;
  }
  
  req.send(data);
  return resultactions;
}

function sendChunkedData() {
  chunkedTimer = null;
  let chunks = {};
  let item = null;
  while(item = chunkedData.splice(0, 1), item.length>0) {
    item = item[0];
    if(!isSet(chunks[item.uri])) chunks[item.uri] = {data: {json: {}}, requests: {}};
    chunks[item.uri].data.json[item.request] = isEmpty(item.data)?false:item.data;
    chunks[item.uri].requests[item.request] = {actions: item.actions, handlers: item.handlers};
  }
  for(uri in chunks) {
    let formdata = new FormData();
  
    let add = function(key, valueOrFunction) {
  
        // If value is a function, invoke it and use its return value
        let value = (typeof valueOrFunction == 'function') ?
          valueOrFunction() :
          valueOrFunction;
  
        formdata.append(key, value == null ? "" : value);
      };
  
    let buildParams = function(prefix, obj) {
      let name;
    
      if(Array.isArray(obj)) {
    
        if(obj.length > 0) {
          // Serialize array item.
          for(let i in obj) {
              // Item is non-scalar (array or object), encode its numeric index.
              buildParams(
                prefix + "[" + ( typeof obj[i] === "object" && obj[i] != null ? i : "" ) + "]",
                obj[i]
              );
          };
        } else {
          buildParams(
            prefix,
            false
          );     
        }
    
      } else if((typeof obj === "object") && !(obj instanceof File) && !(obj instanceof Blob)) {
    
        // Serialize object item.
        for(name in obj) {
          buildParams(prefix + "[" + name + "]", obj[ name ]);
        }
    
      } else {
    
        // Serialize scalar item.
        add(prefix, obj);
      }
    }
    
    for(chunk in chunks[uri].data) {
      buildParams(chunk, chunks[uri].data[chunk]);
    }
    let luri = uri;
    postRPCJson('/'+uri, formdata).always(function(answerdata) {
      if(answerdata instanceof XMLHttpRequest) {
        for(request in chunks[luri].requests) {
          if(typeof chunks[luri].requests[request].handlers.onDone == 'function') {
            chunks[luri].requests[request].handlers.onDone(answerdata.status);
          }
        }
      } else {
        for(request in answerdata.results) {
          if((typeof answerdata.results[request].result != 'undefined') && (typeof chunks[luri] != 'undefined') && (typeof chunks[luri].requests[request] != 'undefined') &&
          (typeof chunks[luri].requests[request].handlers.onDone == 'function')) {
            chunks[luri].requests[request].handlers.onDone(answerdata.results[request].result);
          }
        }
      }
    }).done(function(answerdata) {
      let showError = {};  
      for(request in answerdata.results) {
        showError[request] = true;
        if(answerdata.results[request].status!='success') {
          if((typeof chunks[luri] != 'undefined') && (typeof chunks[luri].requests[request] != 'undefined') && (typeof chunks[luri].requests[request].handlers.onError == 'function')) {
            if(!isSet(answerdata.results[request].result)) answerdata.results[request].result = null;
            showError[request] = chunks[luri].requests[request].handlers.onError(answerdata.results[request].result);
          }
        } else {
          if((typeof chunks[luri] != 'undefined') && (typeof chunks[luri].requests[request] != 'undefined') && (typeof chunks[luri].requests[request].handlers.onSuccess == 'function')) {
            if(!isSet(answerdata.results[request].result)) answerdata.results[request].result = null;
            showError[request] = chunks[luri].requests[request].handlers.onSuccess(answerdata.results[request].result);
          }
        }
        if(showError[request]&&(typeof answerdata.results[request].status != 'undefined')) {
          if(!isSet(answerdata.results[request].statustext)) answerdata.results[request].statustext = answerdata.results[request].status;
          if(answerdata.results[request].statustext == 'success') answerdata.results[request].statustext='Настройки успешно сохранены';
          showalert(answerdata.results[request].status, _(answerdata.results[request].statustext));
        }
      }
    }).fail(function(ajax) {
      let showError = ajax.status>=300;
      for(request in chunks[luri].requests) {
        if((typeof chunks[luri] != 'undefined') && (typeof chunks[luri].requests[request] != 'undefined') && (typeof chunks[luri].requests[request].handlers.onError == 'function')) {
          showError &= chunks[luri].requests[request].handlers.onError(ajax.status);
        }
      }
      if(showError) {
        let text = '';
        switch(ajax.status) {
  /*        case 100: text = _('Продолжение загрузки'); break;
          case 101: text = _('Смена протокола'); break;
          case 200: text = _('Успешно'); break;
          case 201: text = _('Объект создан'); break;
          case 202: text = _('Разрешено'); break;
          case 203: text = _('Не достоверная информация'); break;
          case 204: text = _('Нет содержимого'); break;
          case 205: text = _('Содержимое обновилось'); break;
          case 206: text = _('Частичное содержимое'); break;*/
          case 300: text = _('Множество вариантов'); break;
          case 301: text = _('Местоположение изменено'); break;
          case 302: text = _('Местоположение временно изменено'); break;
          case 303: text = _('Смотрите другой документ'); break;
          case 304: text = _('Не модифицировался'); break;
          case 305: text = _('Используйте прокси-сервер'); break;
          case 400: text = _('Неверный запрос'); break;
          case 401: text = _('Неавторизованый запрос'); break;
          case 402: text = _('Требуется оплата'); break;
          case 403: text = _('Отказано в доступе'); break;
          case 404: text = _('Страница не найдена'); break;
          case 405: text = _('Метод не разрешен'); break;
          case 406: text = _('Не доступно в текущий момент времени'); break;
          case 407: text = _('Требуется аутентификаиця на прокси-сервере'); break;
          case 408: text = _('Превышено время ожидания запроса'); break;
          case 409: text = _('Конфликт ресурсов'); break;
          case 410: text = _('Объект удален'); break;
          case 411: text = _('Требуется указать длину запрашиваемых данных'); break;
          case 412: text = _('Условия для выполнения запроса не выполнены'); break;
          case 413: text = _('Запрошеный блок данных слишком велик'); break;
          case 414: text = _('Запрошенная ссылка слишком длинная'); break;
          case 415: text = _('Не поддерживаемый тип данных'); break;
          case 500: text = _('Внутренняя ошибка сервера'); break;
          case 501: text = _('Не реализовано на сервере'); break;
          case 502: text = _('Не найден ресурс за реверсивным прокси'); break;
          case 503: text = _('Сервис не доступен'); break;
          case 504: text = _('Превышено время ожидания ресурса за реверсивным прокси'); break;
          case 505: text = _('Версия протокола HTTP не поддерживается'); break;
          default:
            text = _('Неизвестный код ошибки: {0}').format(ajax.status);
          break;
        }
        showalert((ajax.status<300)?'success':'danger', text);
        if(ajax.status == 401) {
          location.reload();
        }
      }
    }); 
  }
}

function sendRequest(request, data = {}, uri) {
  let resulthandlers = {onDone: null, onSuccess: null, onError: null};
  let resultactions = {done: function(doneFunc) {resulthandlers.onDone = doneFunc; return resultactions;}, success: function(successFunc) {resulthandlers.onSuccess = successFunc; return resultactions;}, error: function(errorFunc) {resulthandlers.onError = errorFunc; return resultactions;}};
  chunkedData.push({uri: uri, request: request, data: data, actions: resultactions, handlers: resulthandlers});
  if(chunkedTimer === null) {
    setTimeout(sendChunkedData, 50);
  }
  return resultactions;
}

function sendSingleRequest(request, data = {}, uri) {
  let resulthandlers = {onDone: null, onSuccess: null, onError: null};
  let resultactions = {done: function(doneFunc) {resulthandlers.onDone = doneFunc; return resultactions;}, success: function(successFunc) {resulthandlers.onSuccess = successFunc; return resultactions;}, error: function(errorFunc) {resulthandlers.onError = errorFunc; return resultactions;}};

  let formdata = new FormData();
  
  let add = function(key, valueOrFunction) {

      // If value is a function, invoke it and use its return value
      let value = (typeof valueOrFunction == 'function') ?
        valueOrFunction() :
        valueOrFunction;

      formdata.append(key, value == null ? "" : value);
    };

  let buildParams = function(prefix, obj) {
    let name;
  
    if(Array.isArray(obj)) {
  
      // Serialize array item.
      for(let i in obj) {
        // Item is non-scalar (array or object), encode its numeric index.
        buildParams(
          prefix + "[" + ( typeof obj[i] === "object" && obj[i] != null ? i : "" ) + "]",
          obj[i]
        );
      };

    } else if((typeof obj === "object") && !(obj instanceof File)) {
  
      // Serialize object item.
      for(name in obj) {
        buildParams(prefix + "[" + name + "]", obj[ name ]);
      }
  
    } else {
  
      // Serialize scalar item.
      add(prefix, obj);
    }
  }
  
  for(section in data) {
    buildParams(section, data[section]);
  }

  postRPCJson('/'+uri+'?json='+request, formdata).always(function(answerdata) {
    if(answerdata instanceof XMLHttpRequest) {
      if(typeof resulthandlers.onDone == 'function') {
        resulthandlers.onDone(answerdata.status);
      }
    } else {
      if((typeof answerdata.result != 'undefined') &&
         (typeof resulthandlers.onDone == 'function')) {
        resulthandlers.onDone(answerdata.result);
      }
    }
  }).done(function(answerdata) {
    let showError = true;  
    if(isSet(answerdata.status)&&(answerdata.status!='success')) {
      if(typeof resulthandlers.onError == 'function') {
        if(!isSet(answerdata.result)) answerdata.result = null;
        showError = resulthandlers.onError(answerdata.result);
      }
    } else {
      if(typeof resulthandlers.onSuccess == 'function') {
        if(!isSet(answerdata.result)) answerdata.result = null;
        showError = resulthandlers.onSuccess(isSet(answerdata.result)?answerdata.result:answerdata);
      }
    }
    if(showError&&(typeof answerdata.status != 'undefined')) {
      if(!isSet(answerdata.statustext)) answerdata.statustext = answerdata.status;
      if(answerdata.statustext == 'success') answerdata.statustext='Настройки успешно сохранены';
      showalert(answerdata.status, _(answerdata.statustext));
    }
  }).fail(function(ajax) {
    let showError = ajax.status>=300;
    if(typeof resulthandlers.onError == 'function') {
      showError = resulthandlers.onError(ajax.status);
    }
    if(showError) {
      var text = '';
      switch(ajax.status) {
/*        case 100: text = _('Продолжение загрузки'); break;
        case 101: text = _('Смена протокола'); break;
        case 200: text = _('Успешно'); break;
        case 201: text = _('Объект создан'); break;
        case 202: text = _('Разрешено'); break;
        case 203: text = _('Не достоверная информация'); break;
        case 204: text = _('Нет содержимого'); break;
        case 205: text = _('Содержимое обновилось'); break;
        case 206: text = _('Частичное содержимое'); break;*/
        case 300: text = _('Множество вариантов'); break;
        case 301: text = _('Местоположение изменено'); break;
        case 302: text = _('Местоположение временно изменено'); break;
        case 303: text = _('Смотрите другой документ'); break;
        case 304: text = _('Не модифицировался'); break;
        case 305: text = _('Используйте прокси-сервер'); break;
        case 400: text = _('Неверный запрос'); break;
        case 401: text = _('Неавторизованый запрос'); break;
        case 402: text = _('Требуется оплата'); break;
        case 403: text = _('Отказано в доступе'); break;
        case 404: text = _('Страница не найдена'); break;
        case 405: text = _('Метод не разрешен'); break;
        case 406: text = _('Не доступно в текущий момент времени'); break;
        case 407: text = _('Требуется аутентификаиця на прокси-сервере'); break;
        case 408: text = _('Превышено время ожидания запроса'); break;
        case 409: text = _('Конфликт ресурсов'); break;
        case 410: text = _('Объект удален'); break;
        case 411: text = _('Требуется указать длину запрашиваемых данных'); break;
        case 412: text = _('Условия для выполнения запроса не выполнены'); break;
        case 413: text = _('Запрошеный блок данных слишком велик'); break;
        case 414: text = _('Запрошенная ссылка слишком длинная'); break;
        case 415: text = _('Не поддерживаемый тип данных'); break;
        case 500: text = _('Внутренняя ошибка сервера'); break;
        case 501: text = _('Не реализовано на сервере'); break;
        case 502: text = _('Не найден ресурс за реверсивным прокси'); break;
        case 503: text = _('Сервис не доступен'); break;
        case 504: text = _('Превышено время ожидания ресурса за реверсивным прокси'); break;
        case 505: text = _('Версия протокола HTTP не поддерживается'); break;
        default:
          text = _('Неизвестный код ошибки: {0}').format(ajax.status);
        break;
      }
      showalert((ajax.status<300)?'success':'danger', text);
      if(ajax.status == 401) {
        location.reload();
      }
    }
  }); 
  return resultactions;
}

function asyncRequest(method, data, uri) {
  return Promise.resolve({then: function(resolve, reject) {
    sendRequest(method, data, uri).success(function(data) {
      resolve(data);
      return false;
    }).error(function(data) {
      if(!isSet(uri)) uri = urilocation;
      reject('Error '+String(data)+': '+method+'@'+uri);
      return true;
    });
  }});
}

function currentID() {
  let o = new URLSearchParams(window.location.search);
  return o.get('id');
}

function setCurrentID(id) {
  if((id==null)||(id=='')) {
    window.history.pushState(id, appbar.labeltext, '/'+urilocation);
  } else {
    window.history.pushState(id, appbar.labeltext, '/'+urilocation+'?id='+id);
  }
}

async function loadLocation(location, data) {
  let viewpath = await asyncRequest('getview', {location: location}, 'rest/core');
  urilocation=viewpath.location;
  appbar.mainmenu.closed = true;
  let lastscheme = colorscheme;
  colorscheme = ((urilocation.split('/')[0]=='settings')?darktheme:maintheme);
  if(lastscheme != colorscheme) {
    appbar.render();
    appbar.mainmenu.render();
    rootcontent.render();
  }
  if(viewpath.collection) {
    if((typeof data != 'undefined') && data) {
      await require('collection', rootcontent, {modalview: viewpath.view, data: data});
    } else {
      await require('collection', rootcontent, {modalview: viewpath.view});
    }
  } else {
    if((typeof data != 'undefined') && data) {
      await require(viewpath.view, rootcontent, data);
    } else {
      await require(viewpath.view, rootcontent);
    }
  }
  appbar.mainmenu.redraw();
  return rootcontent.view.load();
}

async function setLocation(location, data) {
  let viewpath = await asyncRequest('getview', {location: location}, 'rest/core');
  appbar.mainmenu.closed = true;
  urilocation=viewpath.location;
  let lastscheme = colorscheme;
  colorscheme = ((urilocation.split('/')[0]=='settings')?darktheme:maintheme);
  if(lastscheme != colorscheme) {
    appbar.render();
    appbar.mainmenu.render();
    rootcontent.render();
  }
  if(viewpath.collection) {
    if((typeof data != 'undefined') && data) {
      await require('collection', rootcontent, {modalview: viewpath.view, data: data});
    } else {
      await require('collection', rootcontent, {modalview: viewpath.view});
    }
  } else {
    if((typeof data != 'undefined') && data) {
      await require(viewpath.view, rootcontent, data);
    } else {
      await require(viewpath.view, rootcontent);
    }
  }
  window.history.pushState(data, appbar.labeltext, '/'+urilocation);
  appbar.mainmenu.redraw();
  appbar.mainmenu.redraw();
  return rootcontent.view.load();
}

function showalert(aclass, atext) {
  new widgets.alert(alerts, {severity: aclass}, atext);
}

async function showdialog(caption, text, type, buttons, action) {
  return new Promise((resolve, reject) => {
    const dialog = new widgets.dialog(dialogcontent, {id: 'MsgBox', hasclose: false}, ' ');
    dialog.renderLock();
    if(dialog.children.length == 0) {
      dialog.text =  new widgets.label(dialog, {asHTML: true}, "");
      dialog.text.selfalign = {xs: 12};
      dialog.savebtn = new widgets.button(null, {id: 'Save', class: 'secondary'}, _('Сохранить'));
      dialog.okbtn = new widgets.button(null, {id: 'OK', class: 'primary'}, _('ОК'));
      dialog.yesbtn = new widgets.button(null, {id: 'Yes', class: 'success'}, _('Да'));
      dialog.yestoallbtn = new widgets.button(null, {id: 'YesToAll', class: 'success'}, _('Да для всех'));
      dialog.copybtn = new widgets.button(null, {id: 'Copy', class: 'success'}, _('Копировать'));
      dialog.nobtn = new widgets.button(null, {id: 'No', class: 'warning'}, _('Нет'));
      dialog.notoallbtn = new widgets.button(null, {id: 'NoToAll', class: 'warning'}, _('Нет для всех'));
      dialog.renamebtn = new widgets.button(null, {id: 'Rename', class: 'warning'}, _('Переименовать'));
      dialog.closebtn = new widgets.button(null, {id: 'Cancel', class: 'default'}, _('Отмена'));
      dialog.buttons.push(dialog.savebtn);
      dialog.buttons.push(dialog.okbtn);
      dialog.buttons.push(dialog.yesbtn);
      dialog.buttons.push(dialog.yestoallbtn);
      dialog.buttons.push(dialog.copybtn);
      dialog.buttons.push(dialog.nobtn);
      dialog.buttons.push(dialog.notoallbtn);
      dialog.buttons.push(dialog.renamebtn);
      dialog.buttons.push(dialog.closebtn);
      let func = async (button) => {
        if(button instanceof widgets.button) {
          dialog.modalresult = button.getID();
          dialog.onClose = null;
          dialog.hide();
        } else {
          dialog.modalresult = 'Cancel';
        }
        if(typeof action != 'undefined') {
          if((typeof action == 'function')&&(action.constructor.name=='AsyncFunction')) {
            await action(dialog.modalresult);
          } else if((typeof action == 'function')&&(action.constructor.name=='Function')) {
            action(dialog.modalresult);
          }
        } 
        resolve(dialog.modalresult);
      };
      dialog.savebtn.onClick = func;
      dialog.okbtn.onClick = func;
      dialog.yesbtn.onClick = func;
      dialog.yestoallbtn.onClick = func;
      dialog.copybtn.onClick = func;
      dialog.nobtn.onClick = func;
      dialog.notoallbtn.onClick = func;
      dialog.renamebtn.onClick = func;
      dialog.onClose = func;
    }
    dialog.setLabel(caption);
    dialog.text.setLabel(text);

    dialog.savebtn.hide();
    dialog.okbtn.hide();
    dialog.yesbtn.hide();
    dialog.yestoallbtn.hide();
    dialog.copybtn.hide();
    dialog.nobtn.hide();
    dialog.notoallbtn.hide();
    dialog.renamebtn.hide();
    dialog.closebtn.hide();

    for(let i in buttons) {
      let btnid = dialog.buttons.indexOfId(buttons[i]);
      if(btnid!=-1) {
        dialog.buttons[btnid].show();
      }
    }

    dialog.okbtn.setValue({color: 'primary'});
    dialog.yesbtn.setValue({color: 'success'});
    dialog.yestoallbtn.setValue({color: 'success'});
    dialog.copybtn.setValue({color: 'success'});
    dialog.nobtn.setValue({color: 'warning'});
    dialog.notoallbtn.setValue({color: 'warning'});
    dialog.renamebtn.setValue({color: 'warning'});
    dialog.savebtn.setValue({color: 'success'});
    dialog.closebtn.setValue({color: 'error'});
    switch(type) {
      case 'question': {
        dialog.setValue({color: 'info'});
      } break;
      case 'information': {
        dialog.setValue({color: 'info'});
      } break;
      case 'success': {
        dialog.setValue({color: 'success'});
      } break;
      case 'error': {
        dialog.setValue({color: 'error'});
        dialog.okbtn.setValue({color: 'primary'});
        dialog.yesbtn.setValue({color: 'error'});
        dialog.yestoallbtn.setValue({color: 'error'});
        dialog.copybtn.setValue({color: 'success'});
        dialog.nobtn.setValue({color: 'success'});
        dialog.notoallbtn.setValue({color: 'success'});
        dialog.renamebtn.setValue({color: 'warning'});
        dialog.savebtn.setValue({color: 'error'});
        dialog.closebtn.setValue({color: 'success'});
      } break;
      case 'warning': {
        dialog.setValue({color: 'warning'});
        dialog.okbtn.setValue({color: 'warning'});
        dialog.yesbtn.setValue({color: 'warning'});
        dialog.yestoallbtn.setValue({color: 'warning'});
        dialog.copybtn.setValue({color: 'success'});
        dialog.nobtn.setValue({color: 'success'});
        dialog.notoallbtn.setValue({color: 'success'});
        dialog.renamebtn.setValue({ccolor: 'warning'});
        dialog.savebtn.setValue({color: 'warning'});
        dialog.closebtn.setValue({color: 'success'});
      } break;
    }
    dialog.renderUnlock();
    dialog.show();
  });
}
