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

function sendChunkedData() {
  chunkedTimer = null;
  var chunks = {};
  var item = null;
  while(item = chunkedData.splice(0, 1), item.length>0) {
    item = item[0];
    if(typeof chunks[item.uri] == 'undefined') chunks[item.uri] = {data: {json: {}}, requests: {}};
    chunks[item.uri].data.json[item.request] = $.isEmptyObject(item.data)?false:item.data;
    chunks[item.uri].requests[item.request] = {actions: item.actions, handlers: item.handlers};
  }
  for(uri in chunks) {
    var formdata = new FormData();
  
    var add = function(key, valueOrFunction) {
  
        // If value is a function, invoke it and use its return value
        var value = (typeof valueOrFunction == 'function') ?
          valueOrFunction() :
          valueOrFunction;
  
        formdata.append(key, value == null ? "" : value);
      };
  
    var buildParams = function(prefix, obj) {
      var name;
    
      if(Array.isArray(obj)) {
    
        if(obj.length > 0) {
          // Serialize array item.
          jQuery.each(obj, function(i, v) {
              // Item is non-scalar (array or object), encode its numeric index.
              buildParams(
                prefix + "[" + ( typeof v === "object" && v != null ? i : "" ) + "]",
                v
              );
          } );
        } else {
          buildParams(
            prefix,
            false
          );     
        }
    
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
    
    for(chunk in chunks[uri].data) {
      buildParams(chunk, chunks[uri].data[chunk]);
    }
  
    $.ajax({ type: 'POST', url: '/'+uri, dataType: 'json', data: formdata, processData: false, contentType: false}).always(function(answerdata) {
      if(answerdata instanceof XMLHttpRequest) {
        for(request in chunks[uri].requests) {
          if(typeof chunks[uri].requests[request].handlers.onDone == 'function') {
            chunks[uri].requests[request].handlers.onDone(answerdata.status);
          }
        }
      } else {
        for(request in answerdata.results) {
          if((typeof answerdata.results[request].result != 'undefined') &&
             (typeof chunks[uri].requests[request].handlers.onDone == 'function')) {
            chunks[uri].requests[request].handlers.onDone(answerdata.results[request].result);
          }
        }
      }
    }).done(function(answerdata) {
      var showError = {};  
      for(request in answerdata.results) {
        showError[request] = true;
        if(answerdata.results[request].status!='success') {
          if(typeof chunks[uri].requests[request].handlers.onError == 'function') {
            if(typeof answerdata.results[request].result == 'undefined') answerdata.results[request].result = null;
            showError[request] = chunks[uri].requests[request].handlers.onError(answerdata.results[request].result);
          }
        } else {
          if(typeof chunks[uri].requests[request].handlers.onSuccess == 'function') {
            if(typeof answerdata.results[request].result == 'undefined') answerdata.results[request].result = null;
            showError[request] = chunks[uri].requests[request].handlers.onSuccess(answerdata.results[request].result);
          }
        }
        if(showError[request]&&(typeof answerdata.results[request].status != 'undefined')) {
          if(typeof answerdata.results[request].statustext == 'undefined') answerdata.results[request].statustext = answerdata.results[request].status;
          if(answerdata.results[request].statustext == 'success') answerdata.results[request].statustext='Настройки успешно сохранены';
          showalert(answerdata.results[request].status, _(answerdata.results[request].statustext));
        }
      }
    }).fail(function(ajax) {
      var showError = ajax.status>=300;
      for(request in chunks[uri].requests) {
        if(typeof chunks[uri].requests[request].handlers.onError == 'function') {
          showError &= chunks[uri].requests[request].handlers.onError(ajax.status);
        }
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
      }
    }); 
  }
}

function sendRequest(request, data = {}, uri = urilocation) {
  var resulthandlers = {onDone: null, onSuccess: null, onError: null};
  var resultactions = {done: function(doneFunc) {resulthandlers.onDone = doneFunc; return resultactions;}, success: function(successFunc) {resulthandlers.onSuccess = successFunc; return resultactions;}, error: function(errorFunc) {resulthandlers.onError = errorFunc; return resultactions;}};
  chunkedData.push({uri: uri, request: request, data: data, actions: resultactions, handlers: resulthandlers});
  if(chunkedTimer === null) {
    setTimeout(sendChunkedData, 50);
  }
  return resultactions;
}

function sendSingleRequest(request, data = {}, uri = urilocation) {
  var resulthandlers = {onDone: null, onSuccess: null, onError: null};
  var resultactions = {done: function(doneFunc) {resulthandlers.onDone = doneFunc; return resultactions;}, success: function(successFunc) {resulthandlers.onSuccess = successFunc; return resultactions;}, error: function(errorFunc) {resulthandlers.onError = errorFunc; return resultactions;}};

  var formdata = new FormData();
  
  var add = function(key, valueOrFunction) {

      // If value is a function, invoke it and use its return value
      var value = (typeof valueOrFunction == 'function') ?
        valueOrFunction() :
        valueOrFunction;

      formdata.append(key, value == null ? "" : value);
    };

  var buildParams = function(prefix, obj) {
    var name;
  
    if(Array.isArray(obj)) {
  
      // Serialize array item.
      jQuery.each(obj, function(i, v) {
          // Item is non-scalar (array or object), encode its numeric index.
          buildParams(
            prefix + "[" + ( typeof v === "object" && v != null ? i : "" ) + "]",
            v
          );
      } );
  
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

  $.ajax({ type: 'POST', url: '/'+uri+'?json='+request, dataType: 'json', data: formdata, processData: false, contentType: false}).always(function(answerdata) {
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
    var showError = true;  
    if(typeof resulthandlers.onSuccess == 'function') {
      if(typeof answerdata.result == 'undefined') answerdata.result = null;
      showError = resulthandlers.onSuccess(answerdata.result);
      if(showError&&(typeof answerdata.status != 'undefined')) {
        if(typeof answerdata.statustext == 'undefined') answerdata.statustext = answerdata.status;
        if(answerdata.statustext == 'success') answerdata.statustext='Настройки успешно сохранены';
        showalert(answerdata.status, _(answerdata.statustext));
      }
    }
  }).fail(function(ajax) {
    var showError = ajax.status>=300;
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
    }
  }); 
  return resultactions;
}

function addAccessEntry(select, ip, prefix) {
    var list = readAccessList(select.parent());
    if(list.indexOf(ip+'/'+prefix)==-1) {
      $('<div class="input-group col-12" id="entry">\
            <input class="form-control" type="text" readonly value="'+ip+'/'+prefix+'">\
            <span class="input-group-btn">\
              <button class="btn btn-secondary" style="min-width: 2.4rem;">-</button>\
            </span>\
           </div>').insertBefore(select).find('button').click(function(ev) {$(ev.target).parent().parent().remove()});
    }
}

function accessList(sender, list) {

  var select = $('<div class="input-group col-12">\
            <input class="form-control" type="text" pattern="[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}" value="192.168.0.0">\
            <select class="custom-select">\
             <option>0</option>\
             <option>8</option>\
             <option>12</option>\
             <option>16</option>\
             <option selected>24</option>\
             <option>25</option>\
             <option>27</option>\
             <option>29</option>\
             <option>32</option>\
             <option>1</option>\
             <option>2</option>\
             <option>3</option>\
             <option>4</option>\
             <option>5</option>\
             <option>6</option>\
             <option>7</option>\
             <option>9</option>\
             <option>10</option>\
             <option>11</option>\
             <option>13</option>\
             <option>14</option>\
             <option>15</option>\
             <option>17</option>\
             <option>18</option>\
             <option>19</option>\
             <option>20</option>\
             <option>21</option>\
             <option>22</option>\
             <option>23</option>\
             <option>26</option>\
             <option>28</option>\
             <option>30</option>\
             <option>32</option>\
            </select>\
            <span class="input-group-btn">\
              <button class="btn btn-secondary" style="min-width: 2.4rem;">+</button>\
            </span>\
           </div>').appendTo($(sender));
           select.find('button').click(function(ev) { addAccessEntry(select, select.find('input').val(), select.find('select').val()) });
        setAccessList(sender, list);
}

function setAccessList(sender, list) {
  $(sender).find('#entry').remove();
  var select=$(sender).find('select').parent();
  if(list!=null) {
    for(var i=0; i<list.length; i++) {
      var entry=list[i].split('/');
      addAccessEntry(select, entry[0],entry[1]);
    }
  }
}

function readAccessList(sender) {
  var result = [];
  $(sender).find('#entry input').each(function(i, input) {
    result.push(input.value);
  });
  return result;
}

function inputList(sender) {

  function inputClick(ev) {
    var dragDstEl = ev.target;
    while($(dragDstEl).parent()[0].tagName!='UL')
      dragDstEl = $(dragDstEl).parent()[0];
    var dragSrcEl = dragDstEl;

    if(ev.target.checked) {
      do {
        if($(dragDstEl).prev().length>0) {
          dragDstEl = $(dragDstEl).prev()[0];
        } else {
          $(dragDstEl).before(dragSrcEl);
          return true;
        }
      } while(!$(dragDstEl).find('input').prop('checked'));
      $(dragDstEl).after(dragSrcEl);
    } else {
      do {
        if($(dragDstEl).next().length>0) {
          dragDstEl = $(dragDstEl).next()[0];
        } else {
          $(dragDstEl).after(dragSrcEl);
          return true;
        }
      } while($(dragDstEl).find('input').prop('checked'));
      $(dragDstEl).before(dragSrcEl);
    }
    return true;
  }
  var sortlist = new Sortable($(sender).get(0), {animation: 150, touchStartThreshold: 10, preventOnFilter: false, filter: function(e) {
    var item=$(e.target);
    while(item[0].tagName!='LI') {
      item=item.parent();
    }
    return !item.find('input').prop('checked');
  }, draggable: 'li', onMove: function(e) {
    var item=$(e.related);
    while(item[0].tagName!='LI') {
      item=item.parent();
    }
    return item.find('input').prop('checked');
  } });
//  console.log(sortlist);
//  sortlist._on('click', function(e) {alert('test');});
  $(sender).on('change', 'input', inputClick).on('click','li', function(e) {
    var item=$(e.target);
    while(item[0].tagName!='LI') {
      item=item.parent();
    }
//    item=$(e.target).find('input');
//    item.prop('checked',!item.prop('checked')).trigger('change');
//    e.preventDefault();
  });
  return true;
}

function readInputList(sender) {
  var result = [];
  $(sender).find('input').each(function(i, input) {
    if(input.checked) result.push(input.value);
  });
  return result;
}

function setInputList(sender, values) {
  $(sender).find('input').prop('checked',false);
  for(var i=0; i<values.length; i++) {
    $(sender).find('input').each(function(j, input) {
      if(input.value==values[i]) {
        input.checked=true;
        $(input).trigger('change');
      }
    });
  }
}

function treeList(sender) {

  var tree = $(sender).tree({
                    primaryKey: 'id',
                    uiLibrary: 'bootstrap4',
                    border: 'true',
                    imageCssClassField: 'icon',
                    textField: 'title',
                    childrenField: 'submenu',
                    primaryKey: 'link',
                    checkboxes: true
                });
  $(sender).data('tree',tree);
  return true;
}

function setTreeList(sender, items) {
  $(sender).data('tree').render(items);
}

function checkTreeList(sender, items) {
  var tree = $(sender).data('tree');
  tree.uncheckAll();
  if(items!=null) for(var i=0; i<items.length; i++) {
    tree.check(tree.getNodeById(items[i]));
  }
}

function readTreeList(sender) {
  return $(sender).data('tree').getCheckedNodes();
}

function orderList(sender, onUpdate, selector) {

  if(typeof selector == 'undefined') selector='li';

  Sortable.create($(sender).get(0), {animation: 300, draggable: selector});

  return true;
}

function readOrderList(sender) {
  var result = [];
  $(sender).find('li').each(function(i, input) {
    result.push({id: $(input).find('#id').val(), title: $(input).find('span').html()});
  });
  return result;
}

function setOrderList(sender, values, onRemove) {
  var list = $(sender);
  list.html('');
  for(var i=0; i<values.length; i++) {
    $('<li class="small list-group-item" draggable=true><input type="hidden" id="id" value="'+values[i].id+'"><span>'+values[i].title+'</span><button class="btn btn-danger" style="position: absolute;right: 1.2rem;top: 2px;width: 2.5rem; background-size: 50%; background-repeat: no-repeat; background-position: center center; background-image: url(/img/trashcan.svg);">&nbsp;</button></li>').appendTo(list).find('button').on('click', function(e) {
      line = $(e.target).parent();
      onRemove(sender, line.find('#id').val());
    });
  }
}

function showalert(aclass, atext) {
  $("<div class='alert alert-"+aclass+"'>"+atext+"</div>").appendTo('.alerts').delay((aclass=='success')?700:3000).fadeOut(400, function() {$(this).remove()});
}

async function showdialog(caption, text, type, buttons, action) {
  return new Promise((resolve, reject) => {
    var dialog = $('#messagebox');
    dialog.unbind();
    dialog.find('.modal-title').html(caption);
    dialog.find('.modal-body p').html(text);
    dialog.find('.modal-footer button').hide().unbind().on('click', async function(e) {
      dialog.unbind().modal('hide');
      dialog.modalresult = e.target.id;
      if(typeof action != 'undefined') {
        if((typeof action == 'function')&&(action.constructor.name=='AsyncFunction')) {
          await action(e.target.id);
        } else if((typeof action == 'function')&&(action.constructor.name=='Function')) {
          action(e.target.id);
        }
      } 
      resolve(dialog.modalresult);
    }).removeClass().addClass('btn');
    for(var i = 0; i<buttons.length; i++) {
      dialog.find('.modal-footer button#'+buttons[i]).show();
    }
    dialog.on('hidden.bs.modal', async function(e) {
      dialog.modalresult = 'Cancel';
      if(typeof action != 'undefined') {
        if((typeof action == 'function')&&(action.constructor.name=='AsyncFunction')) {
          await action('Cancel');
        } else if((typeof action == 'function')&&(action.constructor.name=='Function')) {
          action('Cancel');
        }
      }
      resolve(dialog.modalresult);
    });
    var header = dialog.find('.modal-header').removeClass().addClass('modal-header');
    dialog.find('.modal-footer button#OK').addClass('btn-primary');
    dialog.find('.modal-footer button#Yes').addClass('btn-success');
    dialog.find('.modal-footer button#YesToAll').addClass('btn-success');
    dialog.find('.modal-footer button#Copy').addClass('btn-success');
    dialog.find('.modal-footer button#No').addClass('btn-warning');
    dialog.find('.modal-footer button#NoToAll').addClass('btn-warning');
    dialog.find('.modal-footer button#Rename').addClass('btn-warning');
    dialog.find('.modal-footer button#Apply').addClass('btn-success');
    dialog.find('.modal-footer button#Cancel').addClass('btn-danger');
    switch(type) {
      case 'question': {
        header.addClass('bg-light');
      } break;
      case 'information': {
        header.addClass('bg-info text-white');
      } break;
      case 'success': {
        header.addClass('bg-success text-white');
      } break;
      case 'error': {
        header.addClass('bg-danger text-white');
        dialog.find('.modal-footer button').removeClass().addClass('btn');
        dialog.find('.modal-footer button#OK').addClass('btn-warning');
        dialog.find('.modal-footer button#Yes').addClass('btn-danger');
        dialog.find('.modal-footer button#YesToAll').addClass('btn-danger');
        dialog.find('.modal-footer button#Copy').addClass('btn-success');
        dialog.find('.modal-footer button#No').addClass('btn-success');
        dialog.find('.modal-footer button#NoToAll').addClass('btn-success');
        dialog.find('.modal-footer button#Rename').addClass('btn-warning');
        dialog.find('.modal-footer button#Apply').addClass('btn-danger');
        dialog.find('.modal-footer button#Cancel').addClass('btn-success');
      } break;
      case 'warning': {
        header.addClass('bg-warning');
        dialog.find('.modal-footer button').removeClass().addClass('btn');
        dialog.find('.modal-footer button#OK').addClass('btn-warning');
        dialog.find('.modal-footer button#Yes').addClass('btn-warning');
        dialog.find('.modal-footer button#YesToAll').addClass('btn-warning');
        dialog.find('.modal-footer button#Copy').addClass('btn-success');
        dialog.find('.modal-footer button#No').addClass('btn-success');
        dialog.find('.modal-footer button#NoToAll').addClass('btn-success');
        dialog.find('.modal-footer button#Rename').addClass('btn-warning');
        dialog.find('.modal-footer button#Apply').addClass('btn-warning');
        dialog.find('.modal-footer button#Cancel').addClass('btn-success');
      } break;
    }
    dialog.modal('show');
  });
}

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

if([].indexOf) {
  var find = function(array, value) {
    return array.indexOf(value);
  }
} else {
  var find = function(array, value) {
    for (var i = 0; i < array.length; i++) {
      if (array[i] === value) return i;
    }
    return -1;
  }
}

function isTouchDevice() {
  return (
    !!(typeof window !== 'undefined' &&
      ('ontouchstart' in window ||
        (window.DocumentTouch &&
          typeof document !== 'undefined' &&
          document instanceof window.DocumentTouch))) ||
    !!(typeof navigator !== 'undefined' &&
      (navigator.maxTouchPoints || navigator.msMaxTouchPoints))
  );
}