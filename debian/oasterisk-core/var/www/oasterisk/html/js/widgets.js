/**
 * Коллекция виджетов, структура содержащая наследники класса #baseWidget
 * \var widgets
 * \type HashTable
*/
var widgets = {};

/**
 * Текстовые данные локализации в формате l18n_data['LN-id']['ключ'].
 * Используется для загрузки и хранения тектовых записей для текущей локали обозревателя.
 * \var l18n_data
 * \type HashTable
*/
var l18n_data = {};

/**
 * Функция локализации, аналогичная библиотеке gettext.
 * Ищет уникальный идентификатор в данных локализации, если данные не найдёны, возвращает знаение по умолчанию.
 * \tparam String id Уникальный строковый идентификатор
 * \tparam String defval Значение по умолчанию, если не задано возвращает идентификатор
 * \return Локализованная строка
*/
function _(id, defval) {
  if((typeof l18n_data != 'undefined')&&(typeof l18n_data[document.querySelector('html').lang] != 'undefined')&&(typeof l18n_data[document.querySelector('html').lang][id] != 'undefined')) {
    defval=l18n_data[document.querySelector('html').lang][id];
  }
  if(typeof defval == 'undefined') defval = id;
  return defval;
}

/**
 * Функция поиска элемента массива структур по полю id.
 * Ищет элемент массива по полю id и возвращает индекс элемента. Если элемент не найден, возвращает -1.
 * \tparam Array data Массив структур
 * \tparam Number id Идентификатор записи
 * \return Индекс элемента или -1
*/
function findById(data, id) {
  return data.findIndex(function(item) { return item.id==id; });
}

function cloneArray(array) {
  let items = [];
  for(i in array) {
    items.push(Object.assign({}, array[i]));
  }
  return items;
}

/**
 * \class baseWidget
 * Определение базового (абстрактного) класса виджета. Базовый класс определяет общий набор методов для элементов управления, поддерживает метку, всплывающую подсказку, получение и установку значений элемента управления.
*/
class baseWidget {
  /**
   * \ctor baseWidget
   * Конструктор базового класса виджета.
   * \tparam Object parent Родительский элемент для виджета. Допустимые значения: <i>CSS селектор</i>, <i>Элемент DOM</i>, <i>экземпляр класса #baseWidget</i>
   * \tparam Mixed data Значение элемента, структура с параметрами элемента или массив элементов. Зависит от наследуемого виджета.
   * \tparam String label <i>Опционально:</i> Отображаемая метка (текст) виджета.
   * \tparam String hint <i>Опционально:</i> Всплывающая подсказка виджета, создается если задана метка.
  */
  constructor(parent, data, label, hint) {
    /**
    * \var node
    * Ссылка на основной DOM элемент виджета
    * \type Object
    */
    this.node = null; //widget DOM node
    /**
    * \var hint
    * Ссылка на DOM элемент всплывающей подсказки
    * \type Object
    */
    this.hint = null;
    /**
    * \var label
    * Ссылка на DOM элемент метки
    * \type Object
    */
    this.label = null;
    /**
    * \var labeltext
    * Текущий текст подсказки
    * \type String
    */
    this.labeltext = null;
    if((typeof label != 'undefined')&&(label!=null)) {
      this.labeltext = label;
      if(typeof hint != 'undefined' && hint) {
        this.setHint(hint);
      }
      this.setLabel(label);
    }
  }

  /**
  * \fn bool setHint(String ahint)
  * Устанавливает новый текст всплывающей подсказки, может принимать форматированный HTML текст в качестве значения.
  * \tparam String ahint Текст подсказки
  * \return Истину при успешной смене текста подсказки
  */
  setHint(ahint) {
    if(!this.hint) {
      this.hint=document.createElement('span');
      this.hint.className='badge badge-pill badge-info badge-help';
      this.hint.setAttribute('data-toggle','popover');
      this.hint.setAttribute('data-placement','top');
      this.hint.setAttribute('data-trigger','hover');
      this.hint.setAttribute('data-html',true);
      $(this.hint).popover();
      if(this.label) {
        this.label.appendChild(this.hint);
        this.label.textContent = this.labeltext+' ';
      }
    }
    if(typeof ahint == 'string') {
      ahint = {text: ahint};
    }
    if(typeof ahint.caption == 'undefined') ahint.caption=this.labeltext;
    this.hint.title=ahint.caption;
    this.hint.setAttribute('data-original-title',ahint.caption);
    this.hint.setAttribute('data-content',ahint.text);
    return true;
  }

  /**
  * \fn bool setLabel(String avalue)
  * Устанавливает новый текст метки виджета.
  * \tparam String avalue Текст метки
  * \return Истину при успешной смене текста метки
  */
  setLabel(avalue) {
    this.labeltext = avalue;
    if(!this.label) {
      this.label = document.createElement('label');
      this.label.className = 'col form-label mb-md-0';
      this.label.style['align-self']='center';
    }
    if(this.hint) {
      this.label.innerHTML = this.labeltext+'&nbsp;';
      this.label.appendChild(this.hint);
    } else {
      this.label.innerHTML = this.labeltext;
    }
    return true;
  }

  /**
  * \fn bool setValue(String avalue)
  * Абстрактный метод для установки нового значения виджета.
  * \tparam String avalue Принимает текстовое значение, структуру или массив. Режим обработки зависит от виджета. Общий ключ <i>id</i> определяет идентификатор виджета.
  * \return Истину при успешной установке значения
  */
  setValue(avalue) {
    return false;
  }

  /**
  * \fn bool getValue()
  * Абстрактный метод для получения текущего значения виджета.
  * \return Текущее значение элемента управления
  */
  getValue() {
    return false;
  }

  /**
  * \fn bool disable()
  * Абстрактный метод для отключения виджета.
  * \return Истину при успешном отключении элемента управления
  */
  disable() {
    return false;
  }

  /**
  * \fn bool disabled()
  * Абстрактный метод для получения состояния - отключен ли виджет.
  * \return Истину если элемент управления отключен
  */
  disabled() {
    return false;
  }

  /**
  * \fn bool enable()
  * Абстрактный метод для включения виджета.
  * \return Истину при успешном включении элемента управления
  */
  enable() {
    return false;
  }

  /**
  * \fn bool show()
  * Метод для сокрытия элмента
  * \return Истину при успешном скрытии элемента
  */
  hide() {
    if(typeof this.node != 'undefined') {
      this.node.classList.add('d-none');
      return true;
    }
    return false;
  }

  /**
  * \fn bool show()
  * Метод для показа элемента
  * \return Истину при успешном показе элемента
  */
  show() {
    if(typeof this.node != 'undefined') {
      this.node.classList.remove('d-none');
      this.resize();
      return true;
    }
    return false;
  }

  /**
  * \fn bool resize()
  * Команда для масштабирования элемента
  * \return Истину при успешном масштабировании элемента
  */
  resize() {
    return true;
  }

  /**
  * \fn bool getID()
  * Абстрактный метод для получения текущего значения виджета.
  * \return Текущее значение элемента управления
  */
  getID() {
    return null;
  }

  /**
  * \fn bool getContent()
  * Возвращает ссылку на элемент контейнера, в который добавляются дочерние элементы.
  * \return Ссылку на DOM Element
  */
  getContent() {
    if(typeof this.content != 'undefined') return this.content;
    if(typeof this.node != 'undefined') return this.node;
    return null;
  }

  /**
  * \fn void setParent(Object aobject)
  * Определяет родительский элемент управления. Вызывается конструктором в неявном режиме, вызывать явно требуется только для переопределения родителя.<br>
  * Допустимые значения: <i>CSS селектор</i>, <i>Элемент DOM</i>, <i>экземпляр класса #baseWidget</i>
  */
  setParent(aobject) {
    if(aobject == null) return;
    if(typeof aobject == 'string') {
      aobject = document.querySelector(aobject);
    }
    if(this.node) {
      if(aobject instanceof baseWidget) {
        if(aobject.getContent()) {
          aobject.getContent().appendChild(this.node);
        }
      } else {
        aobject.appendChild(this.node);
      }
    }
    this.resize();
  }
}

/**
* \class columnsWidget
* Определение класса виджетва столбцов. Позволяет задать 1/2/3/4 столбца на странице
*/
widgets.columns=class columnsWidget extends baseWidget {
  /**
  * \ctor baseWidget
  * Конструктор виджета столбцов.
  * \tparam Object parent Родительский элемент для виджета. Допустимые значения: <i>CSS селектор</i>, <i>Элемент DOM</i>, <i>экземпляр класса #baseWidget</i>
  * \tparam Mixed data Значение элемента, в виде структуры, допустимые поля перечислены в описании \ref setValue.
  * \tparam String label Не используется.
  * \tparam String hint Не используется.
  */
  constructor(parent, data, label, hint) {
    super(parent,data,null,null);
    this.node = document.createElement('div');
    this.node.widget = this;
    this.node.className='card-deck';
    if((typeof data != 'undefined') && data ) this.setValue(data);
    this.setParent(parent);
  }

  /**
  * \fn bool setLabel(String avalue)
  * Данный виджет контейнера не содрежит метки, по этому данный вызов является заглушкой и всегда возвращает ложь.
  * \tparam String avalue Не используется.
  * \return Ложь.
  */
  setLabel(avalue) {
    return false;
  }

  /**
  * \fn bool setValue(String avalue)
  * Устанавливает новое значение контейнера и всех дочерних виджетов.
  * \return Истину при успешной установке значения
  * \tparam String avalue Принимает текстовое значение, число, структуру или массив. При передаче скалярного типа данных, они принимаются как соответствующее поле структуры.<br>
  * Передача <em>строки</em> устанавливает поле <strong>id</strong>.<br>
  * Передача <em>числа</em> устанавливает поле <strong>num</strong>.<br>
  * Передача <em>массива</em> устанавливает поле <strong>value</strong>.<br>
  * При передаче массива, массив воспронимается как набор значений вложенных виджетов.<br>
  * При передаче структуры используются следующие поля:<br>
  * <strong>id</strong> - Идентификатор объекта.<br>
  * <strong>num</strong> - Количество столбцов 1, 2, 3 или 4.<br>
  * <strong>value</strong> - Хэш таблица со значениями дочерних виджетов в виде набора <strong>ID элемента</strong>: <strong>значение элемента</strong>
  */
  setValue(avalue) {
    if(typeof avalue == 'string') {
      avalue = {id: avalue};
    } else if(typeof avalue == 'number') {
      avalue = {num: avalue};
    } else if((typeof avalue == 'object') && (typeof avalue.value == 'undefined')) {
      avalue = {value: avalue};
    }
    if(typeof avalue.value != 'undefined') {
      for(var property in avalue.value) {
        if(typeof avalue.value[property] != 'undefined') {
          for(var i=0; i<this.node.childElementCount; i++) {
            if(typeof this.node.childNodes[i].widget != 'undefined') {
              if(this.node.childNodes[i].widget.getID()=='') {
                this.node.childNodes[i].widget.setValue({[property]: avalue.value[property]});
              } else if(this.node.childNodes[i].widget.getID()==property) {
                this.node.childNodes[i].widget.setValue(avalue.value[property]);
              }
            }
          }
        }
      }
    }
//    if((typeof avalue.id == 'undefined')&&(this.node.id == '')) avalue.id='columns-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.id != 'undefined') this.node.id=avalue.id;
    if(typeof avalue.num != 'undefined') {
      if(avalue.num==1) {
        this.node.className='col-12';
      } else if(avalue.num==2) {
        this.node.className='col-12 col-lg-6';
      } else if(avalue.num==3) {
        this.node.className='col-12 col-lg-6 col-xl-4';
      } else if(avalue.num==4) {
        this.node.className='col-12 col-md-6 col-lg-4 col-xl-3';
      } else {
        this.node.className='card-deck';
      }
    }
    return true;
  }

  /**
  * \fn bool disable()
  * Отключает все дочерние элементы управления
  * \return Истину при успешном отключении всех дочерних элементов управления
  */
  disable() {
    var result = true;
    for(var i=0; i<this.node.childElementCount; i++) {
      if(typeof this.node.childNodes[i].widget != 'undefined') {
        result = result && this.node.childNodes[i].widget.disable();
      }
    }
    return result;
  }

  /**
  * \fn bool disabled()
  * Проверяет наличие отключенных дочерних элементов управления
  * \return Истину если существует хотя бы один отключенный элемент управления
  */
  disabled() {
    var result = true;
    for(var i=0; i<this.node.childElementCount; i++) {
      if(typeof this.node.childNodes[i].widget != 'undefined') {
        result = result && this.node.childNodes[i].widget.disabled();
      }
    }
    return result;
  }

  /**
  * \fn bool enable()
  * Включает все дочерние элементы управления
  * \return Истину при успешном включении всех дочерних элементов управления
  */
  enable() {
    var result = true;
    for(var i=0; i<this.node.childElementCount; i++) {
      if(typeof this.node.childNodes[i].widget != 'undefined') {
        result = result && this.node.childNodes[i].widget.enable();
      }
    }
    return result;
  }

  /**
  * \fn bool resize()
  * Масштабирует дочерние элементы командой resize
  * \return Истину при успешном масштабировании дочерних элементов управления
  */
  resize() {
    var result = true;
    for(var i=0; i<this.node.childElementCount; i++) {
      if(typeof this.node.childNodes[i].widget != 'undefined') {
        result = result && this.node.childNodes[i].widget.resize();
      }
    }
    return result;
  }

  /**
  * \fn String getID()
  * Получает текущий идентификатор элемента управления
  * \return Идентификатор элемента управления
  */
  getID() {
    return this.node.id;
  }

  /**
  * \fn Array getValue()
  * Получает значения всех дочерних элементов контейнера в виде хэш таблицы вида <strong>ID элемента</strong>: <strong>значение элемента</strong>.
  * \return Хэш таблицу значений дочерних элементов
  */
  getValue() {
    var result = {};
    for(var i=0; i<this.node.childElementCount; i++) {
      if(typeof this.node.childNodes[i].widget != 'undefined') {
        var value = this.node.childNodes[i].widget.getValue();
        var id = this.node.childNodes[i].widget.getID();
        if(value!=null) {
          if(id=='') {
            result=Object.assign(result,value);
          } else {
            result[id]=value;
          }
        }
      }
    }
    return result;
  }

  /**
  * \fn Boolean simplify()
  * Устанавливает максимальную ширину метки в один столбец для всех вложенных элементов
  * \return Успешный код возврата
  */
  simplify() {
    var result = {};
    for(var i=0; i<this.node.childElementCount; i++) {
      if(typeof this.node.childNodes[i].widget != 'undefined') {
        if(this.node.childNodes[i].widget.label) {
          this.node.childNodes[i].widget.label.classList.remove('col');
          this.node.childNodes[i].widget.label.classList.remove('mb-md-0');
          this.node.childNodes[i].widget.label.classList.add('col-12');
        }
        if(typeof this.node.childNodes[i].widget.inputdiv != 'undefined') {
          this.node.childNodes[i].widget.inputdiv.classList.remove('col-md-7');
        }
        if(typeof this.node.childNodes[i].widget.simplify != 'undefined') {
          this.node.childNodes[i].widget.simplify();
        }
      }
    }
    return result;
  }
}

widgets.section=class sectionWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.node = document.createElement('div');
    this.node.widget = this;
    if(this.label) {
      this.node.className='card';
      if(this.label) this.node.appendChild(this.label);
      this.content = document.createElement('div');
      this.content.className='card-body';
      this.content.widget=this;
    } else {
      this.content = this.node;
    }
    if((typeof data != 'undefined') && data ) this.setValue(data);
    if(this.label) {
      this.node.appendChild(this.content);
    }
    this.setParent(parent);
  }
  setLabel(avalue) {
    this.labeltext = avalue;
    if(!this.label) {
      this.label = document.createElement('div');
      this.label.className = 'card-header';
      if(this.hint) {
        this.label.textContent = this.labeltext+' ';
        this.label.appendChild(this.hint);
      }
    }
    if(!this.hint) {
      this.label.textContent = this.labeltext;
    }
    return true;
  }
  setValue(avalue) {
    if(typeof avalue == 'string') {
      avalue = {id: avalue};
    } else if((typeof avalue == 'object') && (typeof avalue.value == 'undefined')) {
      avalue = {value: avalue};
    }
    if(typeof avalue.value != 'undefined') {
      for(var property in avalue.value) {
        if(typeof avalue.value[property] != 'undefined') {
          for(var i=0; i<this.content.childElementCount; i++) {
            if(typeof this.content.childNodes[i].widget != 'undefined') {
              if(this.content.childNodes[i].widget.getID()=='') {
                this.content.childNodes[i].widget.setValue({[property]: avalue.value[property]});
              } else if(this.content.childNodes[i].widget.getID()==property) {
                this.content.childNodes[i].widget.setValue(avalue.value[property]);
              }
            }
          }
        }
      }
    }
//    if((typeof avalue.id == 'undefined')&&(this.node.id == '')) avalue.id='section-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.id != 'undefined') this.node.id=avalue.id;
    return true;
  }

  /**
  * \fn bool resize()
  * Масштабирует дочерние элементы командой resize
  * \return Истину при успешном масштабировании дочерних элементов управления
  */
  resize() {
    var result = true;
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        result = result && this.content.childNodes[i].widget.resize();
      }
    }
    return result;
  }

  disable() {
    var result = true;
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        result = result && this.content.childNodes[i].widget.disable();
      }
    }
    return result;
  }
  disabled() {
    var result = true;
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        result = result && this.content.childNodes[i].widget.disabled();
      }
    }
    return result;
  }
  enable() {
    var result = true;
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        result = result && this.content.childNodes[i].widget.enable();
      }
    }
    return result;
  }
  getID() {
    return this.node.id;
  }
  getValue() {
    var result = {};
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        var value = this.content.childNodes[i].widget.getValue();
        var id = this.content.childNodes[i].widget.getID();
        if(value!=null) {
          if(id=='') {
            result=Object.assign(result,value);
          } else {
            result[id]=value;
          }
        }
      }
    }
    return result;
  }

  /**
  * \fn Boolean simplify()
  * Устанавливает максимальную ширину метки в один столбец для всех вложенных элементов
  * \return Успешный код возврата
  */
  simplify() {
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        if(this.content.childNodes[i].widget.label) {
          this.content.childNodes[i].widget.label.classList.remove('col');
          this.content.childNodes[i].widget.label.classList.remove('mb-md-0');
          this.content.childNodes[i].widget.label.classList.add('col-12');
        }
        if(typeof this.content.childNodes[i].widget.inputdiv != 'undefined') {
          this.content.childNodes[i].widget.inputdiv.classList.remove('col-md-7');
        }
        if(typeof this.content.childNodes[i].widget.simplify != 'undefined') {
          this.content.childNodes[i].widget.simplify();
        }
      }
    }
    return true;
  }
}

widgets.tabs=class tabsWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,null,null);
    this.node = document.createElement('div');
    this.node.widget = this;
    this.header = document.createElement('ul');
    this.header.className='nav nav-tabs';
    this.header.setAttribute('role','tablist');
    this.header.widget = this;
    this.content = document.createElement('div');
    this.content.className='tab-content';
    this.content.widget = this;
    this.node.appendChild(this.header);
    this.node.appendChild(this.content);
    if((typeof data != 'undefined') && data ) this.setValue(data);
    this.setParent(parent);
  }
  setValue(avalue) {
    if(typeof avalue == 'string') {
      avalue = {id: avalue};
    } else if((typeof avalue == 'object') && (typeof avalue.value == 'undefined')) {
      avalue = {value: avalue};
    }
    if(typeof avalue.value != 'undefined') {
      for(var property in avalue.value) {
        if(typeof avalue.value[property] != 'undefined') {
          for(var i=0; i<this.content.childElementCount; i++) {
            if(typeof this.content.childNodes[i].widget != 'undefined') {
              if(this.content.childNodes[i].widget.getID()=='') {
                this.content.childNodes[i].widget.setValue({[property]: avalue.value[property]});
              } else if(this.content.childNodes[i].widget.getID()==property) {
                this.content.childNodes[i].widget.setValue(avalue.value[property]);
              }
            }
          }
        }
      }
    }
    if(typeof avalue.id != 'undefined') this.node.id=avalue.id;
    return true;
  }

  /**
  * \fn bool resize()
  * Масштабирует дочерние элементы командой resize
  * \return Истину при успешном масштабировании дочерних элементов управления
  */
  resize() {
    var result = true;
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        result = result && this.content.childNodes[i].widget.resize();
      }
    }
    return result;
  }

  disable() {
    var result = true;
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        result = result && this.content.childNodes[i].widget.disable();
      }
    }
    return result;
  }
  disabled() {
    var result = true;
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        result = result && this.content.childNodes[i].widget.disabled();
      }
    }
    return result;
  }
  enable() {
    var result = true;
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        result = result && this.content.childNodes[i].widget.enable();
      }
    }
    return result;
  }
  getID() {
    return this.node.id;
  }
  getValue() {
    var result = {};
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        var value = this.content.childNodes[i].widget.getValue();
        var id = this.content.childNodes[i].widget.getID();
        if(value!=null) {
          if(id=='') {
            result=Object.assign(result,value);
          } else {
            result[id]=value;
          }
        }
      }
    }
    return result;
  }

  /**
  * \fn Boolean simplify()
  * Устанавливает максимальную ширину метки в один столбец для всех вложенных элементов
  * \return Успешный код возврата
  */
  simplify() {
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        if(this.content.childNodes[i].widget.label) {
          this.content.childNodes[i].widget.label.classList.remove('col');
          this.content.childNodes[i].widget.label.classList.remove('mb-md-0');
          this.content.childNodes[i].widget.label.classList.add('col-12');
        }
        if(typeof this.content.childNodes[i].widget.inputdiv != 'undefined') {
          this.content.childNodes[i].widget.inputdiv.classList.remove('col-md-7');
        }
        if(typeof this.content.childNodes[i].widget.simplify != 'undefined') {
          this.content.childNodes[i].widget.simplify();
        }
      }
    }
    return true;
  }
}

widgets.tab=class tabsWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.node = document.createElement('div');
    this.node.className='tab-pane fade pr-0';
    this.node.setAttribute('role','tabpanel')
    this.node.widget = this;
    this.header = document.createElement('li');
    this.header.className='nav-item';
    this.header.widget = this;
    if(label) {
      this.header.appendChild(this.label);
    }
    this.content = this.node;
    if((typeof data != 'undefined') && data ) this.setValue(data); else this.setValue({});
    this.setParent(parent);
  }

  /**
  * \fn void setParent(Object aobject)
  * Определяет родительский элемент управления. Вызывается конструктором в неявном режиме, вызывать явно требуется только для переопределения родителя.<br>
  * Допустимые значения: <i>CSS селектор</i>, <i>Элемент DOM</i>, <i>экземпляр класса #baseWidget</i>
  */
  setParent(aobject) {
    if(aobject instanceof widgets.tabs) {
      aobject.content.appendChild(this.node);
      aobject.header.appendChild(this.header);
      if(aobject.content.childNodes.length==1) {
        this.node.classList.add('active');
        this.node.classList.add('show');
        this.label.classList.add('active');
        this.label.setAttribute('aria-selected','true');
      }
    }
  }

  setLabel(avalue) {
    this.labeltext = avalue;
    if(!this.label) {
      this.label = document.createElement('a');
      this.label.className = 'nav-link';
      this.label.setAttribute('data-toggle','tab');
      this.label.setAttribute('aria-selected','false');
      if(this.hint) {
        this.label.textContent = this.labeltext+' ';
        this.label.appendChild(this.hint);
      }
    }
    if(!this.hint) {
      this.label.textContent = this.labeltext;
    }
    return true;
  }

  setValue(avalue) {
    if(typeof avalue == 'string') {
      avalue = {id: avalue};
    } else if((typeof avalue == 'object') && (typeof avalue.value == 'undefined')) {
      avalue = {value: avalue};
    }
    if(typeof avalue.value != 'undefined') {
      for(var property in avalue.value) {
        if(typeof avalue.value[property] != 'undefined') {
          for(var i=0; i<this.content.childElementCount; i++) {
            if(typeof this.content.childNodes[i].widget != 'undefined') {
              if(this.content.childNodes[i].widget.getID()=='') {
                this.content.childNodes[i].widget.setValue({[property]: avalue.value[property]});
              } else if(this.content.childNodes[i].widget.getID()==property) {
                this.content.childNodes[i].widget.setValue(avalue.value[property]);
              }
            }
          }
        }
      }
    }
    if((typeof avalue.id == 'undefined')&&(this.node.id == '')) avalue.id='tab-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.id != 'undefined') {
      this.node.id=avalue.id;
      this.node.setAttribute('aria-labelledby',avalue.id+'-tab');
      this.label.id=avalue.id+'-tab';
      this.label.href='#'+avalue.id;
      this.label.setAttribute('aria-controls',avalue.id);
    }
    return true;
  }

  /**
  * \fn bool resize()
  * Масштабирует дочерние элементы командой resize
  * \return Истину при успешном масштабировании дочерних элементов управления
  */
  resize() {
    var result = true;
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        result = result && this.content.childNodes[i].widget.resize();
      }
    }
    return result;
  }

  disable() {
    var result = true;
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        result = result && this.content.childNodes[i].widget.disable();
      }
    }
    return result;
  }
  disabled() {
    var result = true;
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        result = result && this.content.childNodes[i].widget.disabled();
      }
    }
    return result;
  }
  enable() {
    var result = true;
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        result = result && this.content.childNodes[i].widget.enable();
      }
    }
    return result;
  }
  getID() {
    return this.node.id;
  }
  getValue() {
    var result = {};
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        var value = this.content.childNodes[i].widget.getValue();
        var id = this.content.childNodes[i].widget.getID();
        if(value!=null) {
          if(id=='') {
            result=Object.assign(result,value);
          } else {
            result[id]=value;
          }
        }
      }
    }
    return result;
  }

  /**
  * \fn Boolean simplify()
  * Устанавливает максимальную ширину метки в один столбец для всех вложенных элементов
  * \return Успешный код возврата
  */
  simplify() {
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        if(this.content.childNodes[i].widget.label) {
          this.content.childNodes[i].widget.label.classList.remove('col');
          this.content.childNodes[i].widget.label.classList.remove('mb-md-0');
          this.content.childNodes[i].widget.label.classList.add('col-12');
        }
        if(typeof this.content.childNodes[i].widget.inputdiv != 'undefined') {
          this.content.childNodes[i].widget.inputdiv.classList.remove('col-md-7');
        }
        if(typeof this.content.childNodes[i].widget.simplify != 'undefined') {
          this.content.childNodes[i].widget.simplify();
        }
      }
    }
    return true;
  }
}

widgets.dialog=class dialogWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.onOpened=null;
    this.onOpen=null;
    this.onClosed=null;
    this.onClose=null;
    this.onSave=null;
    this.node = document.createElement('div');
    this.node.className = 'modal fade';
    this.node.widget = this;
    this.dialog = document.createElement('div');
    this.dialog.className = 'modal-dialog modal-lg';
    this.dialog.role='document';
    this.node.appendChild(this.dialog);
    this.form = document.createElement('div');
    this.form.className = 'modal-content';
    this.dialog.appendChild(this.form);
    this.header = document.createElement('div');
    this.header.className = 'modal-header';
    if(!this.label) {
      this.setLabel('');
    }
    this.header.appendChild(this.label);
    this.form.appendChild(this.header);

    this.closexbtn = document.createElement('button');
    this.closexbtn.type = 'button';
    this.closexbtn.className = 'close';
    this.closexbtn.dataset.dismiss = 'modal';
    this.closexbtn.setAttribute('aria-label', _('Закрыть'));
    this.closexbtnlabel = document.createElement('span');
    this.closexbtnlabel.setAttribute('aria-hidden', true);
    this.closexbtnlabel.textContent = '×';
    this.closexbtn.appendChild(this.closexbtnlabel);

    this.header.appendChild(this.closexbtn);
    this.content = document.createElement('div');
    this.content.className = 'modal-body';
    this.content.widget=this;
    this.form.appendChild(this.content);

    this.footer = document.createElement('div');
    this.footer.className = 'modal-footer';
    this.footer.widget=this;
    this.form.appendChild(this.footer);

    this.closebtn = new widgets.button(this.footer, {class: 'secondary', id: 'close'}, _('Закрыть'));
    this.closebtn.node.dataset.dismiss = 'modal';

    this.savebtn = new widgets.button(this.footer, {class: 'success', id: 'save'}, _('Сохранить'));
    this.savebtn.onClick=this.saveClick;

    $(this.node).on('show.bs.modal', this.formShow);
    $(this.node).on('hide.bs.modal', this.formHide);
    $(this.node).on('shown.bs.modal', this.formShown);
    $(this.node).on('hidden.bs.modal', this.formHidden);
    if((typeof data != 'undefined') && data ) this.setValue(data);
    this.setParent(parent);
  }
  saveClick(sender) {
    var result=false;
    if(sender.node.parentNode.widget.onSave) {
      result=sender.node.parentNode.widget.onSave(sender.node.parentNode.widget);
    }
    if(result) sender.node.parentNode.widget.hide();
    return true;
  }
  formShow(sender) {
    if(sender.target.widget.onOpen) {
      return sender.target.widget.onOpen(sender.target.widget);
    }
    return true;
  }
  formHide(sender) {
    if(sender.target.widget.onClose) {
      return sender.target.widget.onClose(sender.target.widget);
    }
    return true;
  }
  formShown(sender) {
    if(sender.target.widget.onOpened) {
      return sender.target.widget.onOpened(sender.target.widget);
    }
    return true;
  }
  formHidden(sender) {
    if(sender.target.widget.onCloseed) {
      return sender.target.widget.onClosed(sender.target.widget);
    }
    return true;
  }
  setLabel(avalue) {
    this.labeltext = avalue;
    if(!this.label) {
      this.label = document.createElement('h5');
      this.label.className = 'modal-title';
      if(this.hint) {
        this.label.textContent = this.labeltext+' ';
        this.label.appendChild(this.hint);
      }
    }
    if(!this.hint) {
      this.label.textContent = this.labeltext;
    }
    return true;
  }
  setValue(avalue) {
    if(typeof avalue == 'string') {
      avalue = {id: avalue};
    } else if((typeof avalue == 'object') && (typeof avalue.value == 'undefined')) {
      avalue = {value: avalue};
    }
    if(typeof avalue.value != 'undefined') {
      for(var property in avalue.value) {
        if(typeof avalue.value[property] != 'undefined') {
          for(var i=0; i<this.content.childElementCount; i++) {
            if(typeof this.content.childNodes[i].widget != 'undefined') {
              if(this.content.childNodes[i].widget.getID()=='') {
                this.content.childNodes[i].widget.setValue({[property]: avalue.value[property]});
              } else if(this.content.childNodes[i].widget.getID()==property) {
                this.content.childNodes[i].widget.setValue(avalue.value[property]);
              }
            }
          }
        }
      }
    }
    if((typeof avalue.id == 'undefined')&&(this.node.id == '')) avalue.id='dialog-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.id != 'undefined') this.node.id=avalue.id;
    return true;
  }

  /**
  * \fn bool resize()
  * Масштабирует дочерние элементы командой resize
  * \return Истину при успешном масштабировании дочерних элементов управления
  */
  resize() {
    var result = true;
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        result = result && this.content.childNodes[i].widget.resize();
      }
    }
    return result;
  }

  disable() {
    var result = true;
    this.savebtn.hide();
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        result = result && this.content.childNodes[i].widget.disable();
      }
    }
    return result;
  }
  disabled() {
    var result = true;
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        result = result && this.content.childNodes[i].widget.disabled();
      }
    }
    return result;
  }
  enable() {
    var result = true;
    this.savebtn.show();
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        result = result && this.content.childNodes[i].widget.enable();
      }
    }
    return result;
  }
  getID() {
    return this.node.id;
  }
  getValue() {
    var result = {};
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        var value = this.content.childNodes[i].widget.getValue();
        var id = this.content.childNodes[i].widget.getID();
        if(value!=null) {
          if(id=='') {
            result=Object.assign(result,value);
          } else {
            result[id]=value;
          }
        }
      }
    }
    return result;
  }
  show() {
    $(this.node).modal('show');
    return true;
  }
  hide() {
    $(this.node).modal('hide');
    return true;
  }

  /**
  * \fn Boolean simplify()
  * Устанавливает максимальную ширину метки в один столбец для всех вложенных элементов
  * \return Успешный код возврата
  */
  simplify() {
    for(var i=0; i<this.content.childElementCount; i++) {
      if(typeof this.content.childNodes[i].widget != 'undefined') {
        if(this.content.childNodes[i].widget.label) {
          this.content.childNodes[i].widget.label.classList.remove('col');
          this.content.childNodes[i].widget.label.classList.remove('mb-md-0');
          this.content.childNodes[i].widget.label.classList.add('col-12');
        }
        if(typeof this.content.childNodes[i].widget.inputdiv != 'undefined') {
          this.content.childNodes[i].widget.inputdiv.classList.remove('col-md-7');
        }
        if(typeof this.content.childNodes[i].widget.simplify != 'undefined') {
          this.content.childNodes[i].widget.simplify();
        }
      }
    }
    return true;
  }
}

widgets.label=class labelWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.node = document.createElement('div');
    this.node.widget = this;
    this.node.className='form-group row';
    if(this.label) this.node.appendChild(this.label);
    this.setParent(parent);
  }
}

widgets.input=class inputWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.node = document.createElement('div');
    this.node.widget = this;
    this.node.className='form-group row';
    if(this.label) this.node.appendChild(this.label);
    this.inputdiv = document.createElement('div');
    this.inputdiv.widget = this;
    if(this.label) {
      this.inputdiv.className='col-12 col-md-7 input-group';
    } else {
      this.inputdiv.className='col-12 input-group';
    }
    this.input = document.createElement('input');
    this.input.type='text';
    this.input.className='form-control';
    this.input.widget=this;
    this.input.oninput=this.inputInput;
    this.input.onkeypress=this.inputKeypress;
    this.showbtn = document.createElement('div');
    this.showbtn.className='btn-group-toggle';
    this.showbtn.dataset.toggle='buttons';
    this.showbtn.style.display='none';
    this.showbtnlabel = document.createElement('label');
    this.showbtnlabel.className = 'btn btn-secondary oi oi-eye';
    this.showbtnlabel.style.minWidth='2.3rem';
    this.showbtnlabel.style.top='0px';
    this.showbtnlabel.style.lineHeight='1.5';
    this.showbtn.appendChild(this.showbtnlabel);
    this.showbtncheck = document.createElement('input');
    this.showbtncheck.type='checkbox';
    this.showbtncheck.autocomplete='off';
    this.showbtncheck.onchange=this.showPwdChange;
    this.showbtncheck.widget=this;
    this.showbtnlabel.appendChild(this.showbtncheck);
    this.onInput=null;
    this.onChange=null;
    if((typeof data != 'undefined') && data ) this.setValue(data);
    if((typeof data != 'undefined')&&(typeof data.inline != 'undefined')&&data.inline&&(!this.label)) {
      this.node = this.input;
      this.setParent(parent);
      this.inputdiv = this.node.parentNode;
      this.inputdiv.appendChild(this.showbtn);
    } else {
      this.inputdiv.appendChild(this.input);
      this.inputdiv.appendChild(this.showbtn);
      this.node.appendChild(this.inputdiv);
      this.setParent(parent);
    }
  }
  showPwdChange(sender) {
    var widget = sender.target.widget;
    if(sender.target.checked) {
      widget.input.type='text';
    } else {
      widget.input.type='password';
    }
  }
  inputInput(sender) {
    var result = true;
    if(sender.target.widget.onInput) result=sender.target.widget.onInput(sender.target.widget, {id: sender.target.widget.getID(), text: sender.target.widget.getValue()});
    return false;
  }
  inputKeypress(sender) {
    var BACKSPACE = 8;
    var DELETE = 46;
    var TAB = 9;
    var LEFT = 37 ;
    var UP = 38 ;
    var RIGHT = 39 ;
    var DOWN = 40 ;
    var END = 35 ;
    var HOME = 35 ;
    var result = false;
    // Checking backspace and delete  
    if(sender.keyCode == BACKSPACE || sender.keyCode == DELETE || sender.keyCode == TAB 
        || sender.keyCode == LEFT || sender.keyCode == UP || sender.keyCode == RIGHT || sender.keyCode == DOWN)  {
        result = true;
    }
    if(sender.target.pattern) {
      var expr=RegExp('^'+sender.target.pattern+'$','g');
      result = expr.test(sender.target.value.substr(0,sender.target.selectionStart)+sender.key+sender.target.value.substr(sender.target.selectionEnd));
    } else result = true;
    return result;
  }
  setValue(avalue) {
    if(typeof avalue == 'undefined') avalue = {};
    if(typeof avalue == 'string') {
      avalue = {value: avalue};
    } else if(typeof avalue == 'number') {
      avalue = {value: String(avalue)};
    }
    if((typeof avalue.id == 'undefined')&&(this.input.id == '')) avalue.id='input-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.pattern == 'undefined') avalue.pattern=null;
    if(typeof avalue.value == 'string') {
      this.input.value=avalue.value;
      this.input.classList.remove('is-invalid');
      this.input.classList.remove('is-valid');
      if(this.onChange) this.onChange(this);
    }
    if(avalue.pattern) this.input.pattern=avalue.pattern;
    if(typeof avalue.placeholder == 'string') this.input.placeholder=avalue.placeholder;
    if(typeof avalue.password != 'undefined') {
      if(avalue.password) {
        this.showbtn.style.display='inline-block';
        this.input.type='password';
      } else {
        this.input.type='text';
        this.showbtn.style.display='none';
      }
    }
    if(typeof avalue.id != 'undefined') {
      this.input.id=avalue.id;
      if(this.label) this.label.htmlFor=this.input.id;
    }
    return true;
  }
  disable() {
    this.input.disabled=true;
    return true;
  }
  disabled() {
    return this.input.disabled;
  }
  enable() {
    this.input.disabled=false;
    return true;
  }
  getID() {
    return this.input.id;
  }
  getValue() {
    return this.input.value;
  }
}

widgets.text=class textWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.node = document.createElement('div');
    this.node.widget = this;
    this.node.className='form-group row';
    if(this.label) this.node.appendChild(this.label);
    this.inputdiv = document.createElement('div');
    this.inputdiv.className='col-12';
    this.inputdiv.widget = this;
    this.input = document.createElement('textarea');
    this.input.className='form-control';
    this.input.widget=this;
    this.input.oninput=this.inputInput;
    this.input.onkeypress=this.inputKeypress;
    this.onInput=null;
    if((typeof data != 'undefined') && data ) this.setValue(data);
    this.inputdiv.appendChild(this.input);
    if((typeof data != 'undefined')&&(typeof data.inline != 'undefined')&&data.inline&&(!this.label)) {
      this.node = this.input;
      this.inputdiv = this.input;
    } else {
      this.node.appendChild(this.inputdiv);
    }
    this.setParent(parent);
  }
  inputInput(sender) {
    var result = true;
    if(sender.target.widget.onInput) result=sender.target.widget.onInput(sender.target.widget, {id: sender.target.widget.getID(), text: sender.target.widget.getValue()});
    return false;
  }
  inputKeypress(sender) {
    var BACKSPACE = 8;
    var DELETE = 46;
    var TAB = 9;
    var LEFT = 37 ;
    var UP = 38 ;
    var RIGHT = 39 ;
    var DOWN = 40 ;
    var END = 35 ;
    var HOME = 35 ;
    var result = false;
    // Checking backspace and delete  
    if(sender.keyCode == BACKSPACE || sender.keyCode == DELETE || sender.keyCode == TAB 
        || sender.keyCode == LEFT || sender.keyCode == UP || sender.keyCode == RIGHT || sender.keyCode == DOWN)  {
        result = true;
    }
    if(sender.target.pattern) {
      var expr=RegExp('^'+sender.target.pattern+'$','g');
      result = expr.test(sender.target.value.substr(0,sender.target.selectionStart)+sender.key+sender.target.value.substr(sender.target.selectionEnd));
    } else result = true;
    return result;
  }
  setValue(avalue) {
    if(typeof avalue == 'string') {
      avalue = {value: avalue};
    } else if(typeof avalue == 'number') {
      avalue = {value: String(avalue)};
    }
    if((typeof avalue.id == 'undefined')&&(this.input.id == '')) avalue.id='text-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.pattern == 'undefined') avalue.pattern=null;
    if(typeof avalue.value == 'string') this.input.value=avalue.value;
    if(avalue.pattern) this.input.pattern=avalue.pattern;
    if(typeof avalue.placeholder == 'string') this.input.placeholder=avalue.placeholder;
    if(typeof avalue.rows != 'undefined') {
      this.input.rows=avalue.rows;
      this.input.style.resize='none';
    }
    if(typeof avalue.id != 'undefined') {
      this.input.id=avalue.id;
      if(this.label) this.label.htmlFor=this.input.id;
    }
    return true;
  }
  disable() {
    this.input.disabled=true;
    return true;
  }
  disabled() {
    return this.input.disabled;
  }
  enable() {
    this.input.disabled=false;
    return true;
  }
  getID() {
    return this.input.id;
  }
  getValue() {
    return this.input.value;
  }
}

widgets.file=class fileWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.node = document.createElement('div');
    this.node.widget = this;
    this.node.className='form-group row';
    if(this.label) this.node.appendChild(this.label);
    this.inputdiv = document.createElement('div');
    this.inputdiv.widget = this;
    if(this.label) {
      this.inputdiv.className='col-12 col-md-7';
    } else {
      this.inputdiv.className='col-12';
    }
    this.inputdivreal = document.createElement('div');
    this.inputdivreal.className='custom-file';
    this.input = document.createElement('input');
    this.input.type='file';
    this.input.className='custom-file-input';
    this.input.setAttribute('lang', document.scrollingElement.lang);
    this.input.widget=this;
    this.input.onchange=this.inputChange;
    this.placeholder = document.createElement('label');
    this.placeholder.className='custom-file-label';
    this.onChange=null;
    if((typeof data != 'undefined') && data ) this.setValue(data);
    this.inputdivreal.appendChild(this.input);
    this.inputdivreal.appendChild(this.placeholder);
    if((typeof data != 'undefined')&&(typeof data.inline != 'undefined')&&data.inline&&(!this.label)) {
      this.node = this.inputdivreal;
      this.inputdiv = this.inputdivreal;
    } else {
      this.inputdiv.appendChild(this.inputdivreal);
      this.node.appendChild(this.inputdiv);
    }
    this.setParent(parent);
  }
  inputChange(sender) {
    var result = true;
    if(typeof sender.target.files[0] != 'undefined') {
      sender.target.widget.placeholder.textContent=sender.target.files[0].name;
      if(sender.target.widget.onChange) result=sender.target.widget.onChange(sender.target.widget, {id: sender.target.widget.getID(), data: sender.target.files[0]});
      return result;
    }
    return false;
  }
  setValue(avalue) {
    if((typeof avalue.id == 'undefined')&&(this.input.id == '')) avalue.id='file-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.placeholder == 'string') this.placeholder.textContent=avalue.placeholder;
    if(typeof avalue.id != 'undefined') {
      this.input.id=avalue.id;
      if(this.label) this.label.htmlFor=this.input.id;
      this.placeholder.htmlFor=this.input.id;
    }
    if(typeof avalue.accept != 'undefined') {
      this.input.accept=avalue.accept;
    }
    if(typeof avalue.returndata != 'undefined') {
      this.returndata = false;
    }
    return true;
  }
  disable() {
    this.input.disabled=true;
    return true;
  }
  disabled() {
    return this.input.disabled;
  }
  enable() {
    this.input.disabled=false;
    return true;
  }
  getID() {
    return this.input.id;
  }
  getValue() {
    if((typeof this.input.files == 'undefined')||(typeof this.input.files[0] == 'undefined')) return null;
    return this.input.files[0];
  }
}

widgets.select=class selectWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.onChange = null;
    this.options = [];
    this.searchmode=null;
    this.search=false;
    this.node = document.createElement('div');
    this.node.widget = this;
    this.node.className='form-group row';
    if(this.label) this.node.appendChild(this.label);
    this.inputdiv = document.createElement('div');
    this.inputdiv.widget = true;
    if(this.label) {
      this.inputdiv.className='col-12 col-md-7';
    } else {
      this.inputdiv.className='col-12';
    }
    this.input = document.createElement('select');
    this.input.className='custom-select col';
    this.input.widget=this;
    this.input.onchange = this.changeSelect;
    if((typeof data != 'undefined')&&(typeof data.inline != 'undefined')&&data.inline&&(!this.label)) {
      this.node = this.input;
      this.inputdiv = this.input;
    } else {
      this.inputdiv.appendChild(this.input);
      this.node.appendChild(this.inputdiv);
    }
    this.setParent(parent);
    if(typeof data != 'undefined') this.setValue(data);
  }
  changeSelect(sender) {
    var result = true;
    if(sender.target.widget.onChange) result = sender.target.widget.onChange(sender.target.widget);
    return result;
  }
  setValue(avalue) {
    if(typeof avalue== 'undefined') avalue = {};
    if(typeof avalue == 'string') {
      if(avalue.trim()=='') avalue={};
      else avalue = {value: [{id: avalue, text: avalue, checked: true}]};
    } else if((typeof avalue == 'object') && (avalue instanceof Array)) {
      avalue = {value: avalue};
    }
    if((typeof avalue.id == 'undefined')&&(this.input.id == '')) avalue.id='select-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.search != 'undefined') {
      this.searchmode=avalue.search;
    }
    if(typeof avalue.clean== 'undefined') {
      avalue.clean=false;
    }
    if((this.searchmode==true)||((this.searchmode==null)&&(typeof avalue.value != 'undefined')&&(avalue.value.length>10))) {
      this.search=true;
      $(this.input).select2({theme: "bootstrap", minimumInputLength: 1, language: document.scrollingElement.lang, ajax: {
            transport: function(params, success, failure) {
              // fitering if params.data.q available
              var items = [];
              if (params.data && params.data.q) {
                items = this.sender.options.filter(function(item) {
                    var t1 = new RegExp(params.data.q,'i').test(item.text);
                    var t2 = new RegExp(params.data.q,'i').test(item.id);
                    return t1 || t2;
                });
              }
              var promise = new Promise(function(resolve, reject) {
                resolve({results: items});
              });
              promise.then(success);
              promise.catch(failure);
            },
            sender: this
          },
          allowClear: true,
          placeholder: _('По умолчанию'),
          dropdownAutoWidth: true,
        });
        if(this.input.nextSibling) {
          for(var i=0; i<this.input.classList.length; i++) {
            if(this.input.classList[i].indexOf('col')===0) {
              this.input.nextSibling.classList.add(this.input.classList[i]);
            }
          }
          if(this.input.classList.contains('d-none')) {
            this.input.nextSibling.classList.add('d-none');
          }
        }
    }
    if(typeof avalue.value != 'undefined' && avalue.value) {
      var oldvalue=(!avalue.clean)?this.input.value:null;
      var newvalue = [];
      for(var i=0; i<avalue.value.length; i++) {
        if((typeof avalue.value[i] == 'string')||(typeof avalue.value[i] == 'number')) {
          if(avalue.value.length==1) avalue.newvalue=avalue.value[i];
          newvalue.push({id: avalue.value[i], text: avalue.value[i]});
        } else {
          newvalue.push(avalue.value[i]);
          if((typeof avalue.value[i].checked != 'undefined') && avalue.value[i].checked) avalue.newvalue=avalue.value[i].id;
        }
      }
      avalue.value = newvalue;
      if(avalue.clean) {
        this.input.textContent = '';
        this.options=avalue.value;
      } else {
        for(var i=0; i<avalue.value.length; i++) {
          if(findById(this.options, avalue.value[i].id)==-1) {
            this.options.push({id: avalue.value[i].id, text: avalue.value[i].text});
          }
        }
      }
      if(!this.search) {
        for(var i=0; i<avalue.value.length; i++) {
          if(this.input.querySelector('option[value="'+avalue.value[i].id+'"]')==null) {
            var option = document.createElement('option');
            option.innerText=avalue.value[i].text;
            option.value=avalue.value[i].id;
            this.input.add(option);
          }
        }
      }
      if(typeof avalue.newvalue == 'undefined') avalue.newvalue=oldvalue;
    }
    if((typeof avalue.newvalue != 'undefined')&&(avalue.newvalue)) {
      this.input.value=avalue.newvalue;
      if(this.search) {
        if(!this.input.value) {
          var j=findById(this.options, avalue.newvalue);
          var option = document.createElement('option');
          option.innerText=this.options[j].text;
          option.value=this.options[j].id;
          this.input.add(option);
          this.input.value=avalue.newvalue;
        }
        $(this.input).trigger('change');
      }
    }
    if(typeof avalue.id != 'undefined') {
      this.input.id=avalue.id;
      if(this.label) this.label.htmlFor=this.input.id;
    }
    return true;
  }
  disable() {
    this.input.disabled=true;
    return true;
  }
  disabled() {
    return this.input.disabled;
  }
  enable() {
    this.input.disabled=false;
    return true;
  }
  getID() {
    return this.input.id;
  }
  getValue() {
    return this.input.value;
  }
  show() {
    super.show();
    if(this.input.nextSibling&&this.input.nextSibling.classList.contains('select2')) this.input.nextSibling.classList.remove('d-none');
    return true;
  }
  hide() {
    super.hide();
    if(this.input.nextSibling&&this.input.nextSibling.classList.contains('select2')) this.input.nextSibling.classList.add('d-none');
    return true;
  }
}

widgets.list=class listWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.columns=1;
    this.disabled=false;
    this.checkbox=false;
    this.sorted=false;
    this.hasedit=false;
    this.hasremove=false;
    this.onEdit=null;
    this.onRemove=null;
    this.onChange=null;
    this.onControlAction=null;
    this.customcontrols=[];
    this.node = document.createElement('div');
    this.node.widget = this;
    this.node.className='form-group row';
    if(this.label) {
      this.label.className = 'col-12 form-label';
      this.node.appendChild(this.label);
    }
    this.inputdiv = document.createElement('div');
    this.inputdiv.className='input-group col-12';
    this.input = document.createElement('ul');
    this.input.className='list-group col-12';
    this.input.widget=this;

    this.sortlist = new Sortable(this.input, {animation: 150, touchStartThreshold: 10, preventOnFilter: false, filter: function(e) {
      if(this.el.widget.sorted) {
        if(this.el.widget.checkbox) {
          var item=e.target;
          while(item.tagName!='LI') {
            item=item.parentNode;
          }
          return !item.querySelector('label > input').checked;
        } else {
          return false;
        }
      } else {
        return false;
      }
    }, draggable: 'li', onMove: function(e) {
      var result=false;
      var widget=this.el.widget;
      if(widget.sorted) {
        if(widget.checkbox) {
          var item=e.related;
          while(item.tagName!='LI') {
            item=item.parentNode;
          }
          result=item.querySelector('label > input').checked;
        } else {
          result=true;
        }
      } else {
        result=true;
      }
      return result;
    } });
    $(this.input).on('end', function() {
      var widget=this.widget;
      if(widget.onChange) setTimeout(function() {widget.onChange(widget)}, 100);
    });
    if(typeof data != 'undefined') this.setValue(data);
    if((typeof data != 'undefined')&&(typeof data.inline != 'undefined')&&data.inline&&(!this.label)) {
      this.node = this.input;
      this.inputdiv = this.input;
    } else {
      this.inputdiv.appendChild(this.input);
      this.node.appendChild(this.inputdiv);
    }
    this.setParent(parent);
  }
  inputClick(ev) {
    if(ev.target.widget.sorted) {
      var dragDstEl = ev.target;
      while(dragDstEl.parentNode.tagName!='UL')
        dragDstEl = dragDstEl.parentNode;
      var dragSrcEl = dragDstEl;
      if(ev.target.checked) {
        do {
          if(dragDstEl.previousSibling) {
            dragDstEl = dragDstEl.previousSibling;
          } else {
            $(dragDstEl).before(dragSrcEl);
            return true;
          }
        } while(!dragDstEl.querySelector('label > input').checked);
        $(dragDstEl).after(dragSrcEl);
      } else {
        do {
          if(dragDstEl.nextSibling) {
            dragDstEl = dragDstEl.nextSibling;
          } else {
            $(dragDstEl).after(dragSrcEl);
            return true;
          }
        } while(dragDstEl.querySelector('label > input').checked);
          $(dragDstEl).before(dragSrcEl);
      }
    }
    return true;
  }
  setValue(avalue) {
    if(typeof avalue.checkbox != 'undefined') {
      this.checkbox=avalue.checkbox;
    }
    if(typeof avalue.remove != 'undefined') {
      this.hasremove=avalue.remove;
    }
    if(typeof avalue.edit != 'undefined') {
      this.hasedit=avalue.edit;
    }
    if(typeof avalue.columns != 'undefined') {
      this.columns=avalue.columns;
    }
    if(this.columns<=1) {
      this.input.style.flexDirection='column';
      this.input.style.flexWrap='nowrap';
      this.input.style.display='inline-block';
    } else {
      this.input.style.flexDirection='row';
      this.input.style.flexWrap='wrap';
      this.input.style.display='inline-flex';
    }
    if(typeof avalue.controls != 'undefined') {
      this.customcontrols=[];
      for(var i=0; i<avalue.controls.length; i++) {
        if((typeof avalue.controls[i].class != 'undefined')&&(typeof avalue.controls[i].id != 'undefined')) {
          this.customcontrols.push(avalue.controls[i]);
        }
      }
    }
    if(typeof avalue == 'string') {
      if(this.checkbox) {
        avalue = {value: [{id: avalue, text: avalue, checked: true}]};
      } else {
        avalue = {value: [{id: avalue, text: avalue}]};
      }
    } else if((typeof avalue == 'object') && (avalue instanceof Array)) {
      avalue = {value: avalue};
    }
    if((typeof avalue.value == 'object') && (avalue.value instanceof Array)) {
      var values=[];
      for(var i=0; i<avalue.value.length; i++) {
        if(typeof avalue.value[i] == 'string') {
          if(this.checkbox) {
            values.push({id: avalue.value[i], text: avalue.value[i], checked: true});
          } else {
            values.push({id: avalue.value[i], text: avalue.value[i]});
          }
        } else {
          values.push(avalue.value[i]);
        }
      }
      avalue.value=values;
    }
    if(typeof avalue.sorted!= 'undefined') {
      this.sorted=(avalue.sorted==true);
    }
    if(typeof avalue.clean== 'undefined') {
      avalue.clean=false;
    }
    if(typeof avalue.uncheck== 'undefined') {
      avalue.uncheck=false;
    }
    if((typeof avalue.id == 'undefined')&&(this.input.id == '')) avalue.id='list-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if((typeof avalue.value != 'undefined') && (avalue.value)) {
      var addnew=avalue.clean;
      if(this.checkbox&&avalue.uncheck) {
        var cb=this.input.querySelectorAll('li > label > input');
        for(var i=0; i<cb.length; i++) {
          cb[i].checked=false;
        }
      }
      for(var i=0; i<avalue.value.length; i++) {
        var cb=this.input.querySelector('[id=\''+avalue.value[i].id+'\'');
        if(cb) {
          if(this.checkbox) {
            if(typeof avalue.value[i].checked != 'undefined') {
              cb.checked=avalue.value[i].checked;
              this.inputClick({target: cb});
            }
          }
          if(typeof avalue.value[i].class != 'undefined') {
            var classname=avalue.value[i].class.toLowerCase();
            switch(classname) {
              case 'primary':
              case 'secondary':
              case 'success':
              case 'danger':
              case 'warning':
              case 'info':
              case 'light':
              case 'dark':
              case 'link': {
                cb.parentNode.parentNode.className='list-group-item';
                if(!this.checkbox) cb.parentNode.parentNode.classList.add('small');
                cb.parentNode.parentNode.classList.add('list-group-item-'+classname);
                switch(this.columns) {
                  case 2: {
                    cb.parentNode.parentNode.classList.add('col-12');
                    cb.parentNode.parentNode.classList.add('col-md-6');
                  } break;
                  case 3: {
                    cb.parentNode.parentNode.classList.add('col-12');
                    cb.parentNode.parentNode.classList.add('col-md-6');
                    cb.parentNode.parentNode.classList.add('col-lg-4');
                  } break;
                  case 4: {
                    cb.parentNode.parentNode.classList.add('col-12');
                    cb.parentNode.parentNode.classList.add('col-md-6');
                    cb.parentNode.parentNode.classList.add('col-lg-4');
                    cb.parentNode.parentNode.classList.add('col-xl-3');
                  } break;
                  default: cb.parentNode.parentNode.classList.add('col-'+(12/this.columns));
                }
              } break;
              case '': {
                cb.parentNode.parentNode.className='list-group-item';
                if(!this.checkbox) cb.parentNode.parentNode.classList.add('small');
                switch(this.columns) {
                  case 2: {
                    cb.parentNode.parentNode.classList.add('col-12');
                    cb.parentNode.parentNode.classList.add('col-md-6');
                  } break;
                  case 3: {
                    cb.parentNode.parentNode.classList.add('col-12');
                    cb.parentNode.parentNode.classList.add('col-md-6');
                    cb.parentNode.parentNode.classList.add('col-lg-4');
                  } break;
                  case 4: {
                    cb.parentNode.parentNode.classList.add('col-12');
                    cb.parentNode.parentNode.classList.add('col-md-6');
                    cb.parentNode.parentNode.classList.add('col-lg-4');
                    cb.parentNode.parentNode.classList.add('col-xl-3');
                  } break;
                  default: cb.parentNode.parentNode.classList.add('col-'+(12/this.columns));
                }
              } break;
            }
          }
          for(var j=0; i<this.customcontrols.length; i++) {
            var obj = cb.parentNode.querySelector('[id=\''+this.customcontrols[j].id+'\'');
            if(obj) {
              var val=null;
              if(typeof avalue.value[i][this.customcontrols[j].id] != 'undefined') val=avalue.value[i][this.customcontrols[j].id];
              if(val) obj.widget.setValue(val);
            }
          }
        } else {
          addnew=true;
        }
      }
      if(addnew) {
        if(avalue.clean) this.input.textContent = '';
        for(var i=0; i<avalue.value.length; i++) {
          var option = document.createElement('li');
          option.className='list-group-item';
          if(typeof avalue.value[i].opacity) {
            option.style.filter='opacity('+avalue.value[i].opacity+'%)';
          }
          option.style.lineHeight = '2.7rem';
          option.draggable=true;
          this.input.appendChild(option);
          var cb = document.createElement('input');
          if(this.checkbox) {
            var li = document.createElement('label');
            li.className='custom-control custom-checkbox col-12 d-inline-block';
            cb.onchange=this.inputClick;
            cb.widget=this;
            cb.type='checkbox';
            cb.className='custom-control-input';
            cb.id=avalue.value[i].id;
            cb.value=avalue.value[i].id;
            if(typeof avalue.value[i].checked != 'undefined') {
              cb.checked=avalue.value[i].checked;
            }
            li.appendChild(cb);
            var span = document.createElement('span');
            span.className='custom-control-label';
            span.textContent = avalue.value[i].text;
            li.appendChild(span);
            option.appendChild(li);
            this.inputClick({target: cb});
          } else {
            option.className='small list-group-item';
            var li = document.createElement('label');
            li.className='custom-control col-12 d-inline-block';
            li.textContent=avalue.value[i].text;
            option.appendChild(li);
            cb.type='hidden';
            cb.id=avalue.value[i].id;
            cb.value=avalue.value[i].id;
            li.appendChild(cb);
          }
          if(typeof avalue.value[i].subtext != 'undefined') {
            var br = document.createElement('br');
            li.appendChild(br);
            var span = document.createElement('span');
            span.className='small';
            span.textContent = avalue.value[i].subtext;
            li.appendChild(span);
          }
          li.style.paddingTop = '0rem';
          li.style.paddingBottom = '0rem';
          li.style.verticalAlign = 'middle';
          li.style.lineHeight = '1.2rem';
          li.style.overflow = 'hidden';
          li.style.textOverflow = 'ellipsis';
          li.style.whiteSpace = 'nowrap';
          switch(this.columns) {
            case 2: {
              option.classList.add('col-12');
              option.classList.add('col-md-6');
            } break;
            case 3: {
              option.classList.add('col-12');
              option.classList.add('col-md-6');
              option.classList.add('col-lg-4');
            } break;
            case 4: {
              option.classList.add('col-12');
              option.classList.add('col-md-6');
              option.classList.add('col-lg-4');
              option.classList.add('col-xl-3');
            } break;
            default: option.classList.add('col-'+(12/this.columns));
          }
          if(typeof avalue.value[i].class != 'undefined') {
            var classname=avalue.value[i].class.toLowerCase();
            switch(classname) {
              case 'primary':
              case 'secondary':
              case 'success':
              case 'danger':
              case 'warning':
              case 'info':
              case 'light':
              case 'dark':
              case 'link': {
                option.classList.add('list-group-item-'+classname);
              } break;
            }
          }
          var rightbox = document.createElement('span');
          rightbox.className='right list-group d-inline-block';
          option.appendChild(rightbox);
          if(this.hasremove) {
            var removebtn = document.createElement('button');
            removebtn.className='btn btn-danger';
            removebtn.style.float='right';
            removebtn.style.margin='0.2rem';
            removebtn.style['margin-left']='0rem';
            removebtn.style['margin-right']='0.35rem';
            removebtn.style.width='2.3rem';
            var removebtnicon=document.createElement('span');
            removebtnicon.className='d-inline-block oi oi-trash';
            removebtn.appendChild(removebtnicon);
            removebtn.onclick=this.removeButton;
            removebtn.widget=this;
            rightbox.appendChild(removebtn);
          }
          if(this.hasedit) {
            var editbtn = document.createElement('button');
            editbtn.className='btn btn-info';
            editbtn.style.float='right';
            editbtn.style.margin='0.2rem';
            editbtn.style['margin-left']='0rem';
            editbtn.style['margin-right']='0.35rem';
            editbtn.style.width='2.3rem';
            var editbtnicon=document.createElement('span');
            editbtnicon.className='d-inline-block oi oi-pencil';
            editbtn.appendChild(editbtnicon);
            editbtn.onclick=this.editButton;
            editbtn.widget=this;
            rightbox.appendChild(editbtn);
          }
          for(var j=0; j<this.customcontrols.length; j++) {
            if(typeof widgets[this.customcontrols[j].class] != 'undefined') {
              var val={};
              var label=null;
              var desc=null;
              if(typeof this.customcontrols[j].initval != 'undefined') val=this.customcontrols[j].initval;
              if(typeof this.customcontrols[j].label != 'undefined') label=this.customcontrols[j].label;
              if(typeof this.customcontrols[j].desc != 'undefined') desc=this.customcontrols[j].desc;
              val.id=cb.id+'_'+this.customcontrols[j].id;
              var obj = new widgets[this.customcontrols[j].class](rightbox, val, label, desc);
              if(typeof avalue.value[i][this.customcontrols[j].id] != 'undefined') {
                if(typeof avalue.value[i][this.customcontrols[j].id] != 'object') avalue.value[i][this.customcontrols[j].id]={value: avalue.value[i][this.customcontrols[j].id]};
                val=avalue.value[i][this.customcontrols[j].id];
                obj.setValue(val);
              }
              obj.node.classList.add('d-inline-block');
              obj.node.style.lineHeight = '2.0rem';
              obj.node.style.float='right';
              obj.node.style.margin='0.2rem';
              obj.node.style['margin-left']='0rem';
              obj.node.style['margin-right']='0.35rem';
              if(typeof obj.onChange!='undefined') obj.onChange=function(sender) {obj.node.parentNode.parentNode.parentNode.widget.controlAction(sender, 'change')};
              if(typeof obj.onClick!='undefined') obj.onClick=function(sender) {obj.node.parentNode.parentNode.parentNode.widget.controlAction(sender, 'click')};
              if(typeof obj.onRemove!='undefined') obj.onRemove=function(sender) {obj.node.parentNode.parentNode.parentNode.widget.controlAction(sender, 'remove')};
              if(typeof obj.onAdd!='undefined') obj.onAdd=function(sender) {obj.node.parentNode.parentNode.parentNode.widget.controlAction(sender, 'add')};
            }
          }
          if(typeof avalue.value[i].badge != 'undefined') {
            var span = document.createElement('span');
            span.className="badge badge-pill right mr-2 mt-2";
            if(typeof avalue.value[i].badgeclass != 'undefined') {
              span.classList.add('badge-'+avalue.value[i].badgeclass);
            } else {
              span.classList.add('badge-secondary');
            }
            span.textContent=avalue.value[i].badge;
            rightbox.appendChild(span);
          }
        }
        if(this.disabled) this.disable();
      }
      var w = this;
      setTimeout(function() { w.resize() }, 100);
    }
    if(typeof avalue.id != 'undefined') {
      this.input.id=avalue.id;
      if(this.label) this.label.htmlFor=this.input.id;
    }
    return true;
  }
  removeButton(sender) {
    var result = true;
    var listitem = sender.target.parentNode.parentNode;
    var id = listitem.querySelector('label > input').value;
    if(sender.target.widget.onRemove) result=sender.target.widget.onRemove(sender.target.widget, {id: id, text: listitem.textContent});
    if(result) return sender.target.widget.delete(id);
    return false;
  }
  editButton(sender) {
    var result = true;
    var listitem = sender.target.parentNode.parentNode;
    var id = listitem.querySelector('label > input').value;
    if(sender.target.widget.onEdit) result=sender.target.widget.onEdit(sender.target.widget, {id: id, text: listitem.textContent});
    return result;
  }
  controlAction(sender, action) {
    var result = true;
    var listitem = sender.node.parentNode.parentNode;
    var id = listitem.querySelector('label > input').value;
    if(listitem.parentNode.widget.onControlAction) result=listitem.parentNode.widget.onControlAction(listitem.parentNode.widget, {id: id, text: listitem.textContent, action: action, control: sender});
    return result;
  }
  delete(id) {
    var option=this.inputdiv.querySelector('li > label > input[value="'+id+'"]').parentNode.parentNode;
    if(option) {
      option.parentNode.removeChild(option);
      return true;
    }
    return false;
  }
  resize() {
    var result = true;
    var inputs = this.input.querySelectorAll('li');
    for(var i=0; i<inputs.length; i++) {
      var label=inputs[i].querySelector('label');
      var box=inputs[i].querySelector('.list-group');
      var rects = box.offsetLeft;
      label.style.width='calc( '+(rects-2)+'px - 1.5rem )';
    }
    return result;
  }
  disable() {
    this.disabled=true;
    var inputs = this.input.querySelectorAll('input');
    for(var i=0; i<inputs.length; i++) {
      inputs[i].disabled=true;
    }
    inputs = this.input.querySelectorAll('li');
    for(var i=0; i<inputs.length; i++) {
      inputs[i].classList.add('disabled');
    }
    return true;
  }
  isDisabled() {
    var result = this.disabled;
    var inputs = this.input.querySelectorAll('input');
    for(var i=0; i<inputs.length; i++) {
      result |= inputs[i].disabled;
    }
    return result;
  }
  enable() {
    this.disabled=false;
    var inputs = this.input.querySelectorAll('input');
    for(var i=0; i<inputs.length; i++) {
      inputs[i].disabled=false;
    }
    inputs = this.input.querySelectorAll('li');
    for(var i=0; i<inputs.length; i++) {
      inputs[i].classList.remove('disabled');
    }
    return true;
  }
  getID() {
    return this.input.id;
  }
  getValue() {
    var result = [];
    var elements = this.input.querySelectorAll('li > label > input');
    for(var i=0; i<elements.length; i++) {
      if(this.checkbox) {
        if(elements[i].checked) result.push(elements[i].id);
      } else {
        result.push(elements[i].id);
      }
    }
    return result;
  }
  getOptions() {
    var result = [];
    var elements = this.input.querySelectorAll('li > label > input');
    for(var i=0; i<elements.length; i++) {
      result.push({id: elements[i].id, text: elements[i].parentNode.textContent});
    }
    return result;
  }
}

widgets.togglelist=class togglelistWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.sorted=false;
    this.three=false;
    this.node = document.createElement('div');
    this.node.widget = this;
    this.node.className='form-group row';
    this.onChange=null;
    if(this.label) {
      this.label.className = 'col-12 form-label';
      this.node.appendChild(this.label);
    }
    this.inputdiv = document.createElement('div');
    this.inputdiv.widget = this;
    this.inputdiv.className='input-group col-12';
    this.input = document.createElement('ul');
    this.input.className='list-group col-12';
    this.input.widget=this;

    this.sortlist = new Sortable(this.input, {animation: 150, touchStartThreshold: 10, preventOnFilter: false, filter: function(e) {
      if(this.el.widget.sorted) {
        var item=e.target;
        while(item.tagName!='LI') {
          item=item.parentNode;
        }
        return !item.querySelector('input').checked;
      } else {
        return false;
      }
    }, draggable: 'li', onMove: function(e) {
      if(this.el.widget.sorted) {
        var item=e.related;
        while(item.tagName!='LI') {
          item=item.parentNode;
        }
        return item.querySelector('input').checked;
      } else {
        return true;
      }
    } });
    if(typeof data != 'undefined') this.setValue(data);
    if((typeof data != 'undefined')&&(typeof data.inline != 'undefined')&&data.inline&&(!this.label)) {
      this.inputdiv = this.input;
      this.node = this.input;
    } else {
      this.inputdiv.appendChild(this.input);
      this.node.appendChild(this.inputdiv);
    }
    this.setParent(parent);
  }
  inputClick(ev) {
    if(ev.target.sorted) {
      var dragDstEl = ev.target;
      while(dragDstEl.parentNode.tagName!='UL')
        dragDstEl = dragDstEl.parentNode;
      var dragSrcEl = dragDstEl;
      if(ev.target.checked) {
        do {
          if(dragDstEl.previousSibling) {
            dragDstEl = dragDstEl.previousSibling;
          } else {
            $(dragDstEl).before(dragSrcEl);
            return true;
          }
        } while(!dragDstEl.querySelector('input').checked);
        $(dragDstEl).after(dragSrcEl);
      } else {
        do {
          if(dragDstEl.nextSibling) {
            dragDstEl = dragDstEl.nextSibling;
          } else {
            $(dragDstEl).after(dragSrcEl);
            return true;
          }
        } while(dragDstEl.querySelector('input').checked);
          $(dragDstEl).before(dragSrcEl);
      }
    }
    return true;
  }
  setValue(avalue) {
    if(typeof avalue.checkbox != 'undefined') {
      $(this.input).on('change', 'input', this.inputClick);
    }
    if(typeof avalue == 'string') {
      avalue = {value: [{id: avalue, text: avalue, checked: true}]};
    } else if((typeof avalue == 'object') && (avalue instanceof Array)) {
      avalue = {value: avalue};
    }
    if(typeof avalue.sorted!= 'undefined') {
      this.sorted=(avalue.sorted==true);
    }
    if(typeof avalue.three!= 'undefined') {
      this.three=(avalue.three==true);
    }
    if(typeof avalue.clean== 'undefined') {
      avalue.clean=false;
    }
    if((typeof avalue.id == 'undefined')&&(this.input.id == '')) avalue.id='togglelist-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.value != 'undefined') {
      var addnew=false;
      for(var i=0; i<avalue.value.length; i++) {
        var cb=this.input.querySelector('[id=\''+avalue.value[i].id+'\'');
        if(cb) {
          if(typeof avalue.value[i].checked != 'undefined') {
            cb.checked=avalue.value[i].checked;
            this.inputClick({target: cb});
          }
          if(typeof avalue.value[i].class != 'undefined') {
            var classname=avalue.value[i].class.toLowerCase();
            switch(classname) {
              case 'primary':
              case 'secondary':
              case 'success':
              case 'danger':
              case 'warning':
              case 'info':
              case 'light':
              case 'dark':
              case 'link': {
                cb.parentNode.parentNode.className='list-group-item';
                if(!this.checkbox) cb.parentNode.parentNode.classList.add('small');
                cb.parentNode.parentNode.classList.add('list-group-item-'+classname);
              } break;
            }
          }
        } else {
          addnew=true;
        }
      }
      if(addnew) {
        if(avalue.clean||(avalue.value.length>1)) this.input.textContent = '';
        for(var i=0; i<avalue.value.length; i++) {
          var option = document.createElement('li');
          option.className='list-group-item small pt-2 pb-2 pl-3 pr-3';
          option.draggable=true;
          this.input.appendChild(option);
          option.textContent=avalue.value[i].text;
          if(typeof avalue.value[i].checked == 'undefined') avalue.value[i].checked=false;
          option.checkbox=new widgets.checkbox(option, {value: avalue.value[i].checked, id: avalue.value[i].id, three: this.three, toggle: true, large: this.three, right: true});
          this.inputClick({target: option.checkbox.input});
          option.checkbox.onChange=this.toggleChange;

          if(typeof avalue.value[i].class != 'undefined') {
            var classname=avalue.value[i].class.toLowerCase();
            switch(classname) {
              case 'primary':
              case 'secondary':
              case 'success':
              case 'danger':
              case 'warning':
              case 'info':
              case 'light':
              case 'dark':
              case 'link': {
                option.classList.add('list-group-item-'+classname);
              } break;
            }
          }
        }
      }
    }
    if(typeof avalue.id != 'undefined') {
      this.input.id=avalue.id;
      if(this.label) this.label.htmlFor=this.input.id;
    }
    return true;
  }
  toggleChange(sender) {
    var widget = sender.node.parentNode.parentNode.widget;
    if(widget.onChange) widget.onChange(widget, {id: sender.getID(), value: sender.getValue()});
  }
  disable() {
    this.input.disabled=true;
    return true;
  }
  isDisabled() {
    return this.input.disabled;
  }
  enable() {
    this.input.disabled=false;
    return true;
  }
  getID() {
    return this.input.id;
  }
  getValue() {
    var result = [];
    var elements = this.input.querySelectorAll('input');
    for(var i=0; i<elements.length; i++) {
      result.push({id: elements[i].widget.getID(), value: elements[i].widget.getValue()});
    }
    return result;
  }
}

widgets.tree=class treeWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.onUnselect = null;
    this.onSelect = null;
    this.onChange = null;
    this.options = [];
    this.searchmode=null;
    this.search=false;
    this.node = document.createElement('div');
    this.node.widget = this;
    this.node.className='form-group row';
    if(this.label) this.node.appendChild(this.label);
    this.label.classList.remove('mb-md-0');
    this.input = document.createElement('div');
    this.input.className='col-12';
    this.input.widget=this;
    if((typeof data != 'undefined')&&(typeof data.inline != 'undefined')&&data.inline&&(!this.label)) {
      this.node = this.input;
      this.node.classList.remove('col-12');
      this.node.classList.add('col');
    } else {
      this.node.appendChild(this.input);
    }
    this.tree = $(this.input)
                  .bind('select', this.treeSelect)
                  .bind('unselect', this.treeUnselect)
                  .bind('checkboxChange', this.treeChange)
                  .tree({
                    uiLibrary: 'bootstrap4',
                    border: 'true',
                    imageCssClassField: 'icon',
                    textField: 'text',
                    childrenField: 'value',
                    primaryKey: 'id',
                    cascadeCheck: false,
                    selectionType: ((typeof data.multiple != 'undefined') && (data.multiple))?'multiple':'single',
                    checkboxes: ((typeof data.checkbox != 'undefined') && (data.checkbox))
                });
    this.tree.widget=this;
    this.onDropFiles = null;

    $(this.input).on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
      e.preventDefault();
      //e.stopPropagation();
    })
    .on('dragover dragenter', function(e) {
      e.currentTarget.widget.input.classList.add('dragover');
    })
    .on('dragleave dragend drop', function(e) {
      e.currentTarget.widget.input.classList.remove('dragover');
    })
    .on('drop', function(e) {
      var droppedFiles = e.originalEvent.dataTransfer.files;
      if(e.currentTarget.widget.onDropFiles) e.currentTarget.widget.onDropFiles(e.currentTarget.widget, droppedFiles);
    });

    if(typeof data != 'undefined') this.setValue(data);
    this.setParent(parent);
  }
  treeSelect(sender, item, id) {
    var sender=sender.currentTarget.widget;
    if(sender.onSelect) sender.onSelect(sender, id);
  }
  treeUnselect(sender, item, id) {
    var sender=sender.currentTarget.widget;
    if(sender.onUnselect) sender.onUnselect(sender, id);
  }
  treeChange(sender, item, record, state) {
    var sender=sender.currentTarget.widget;
    if(record.id.indexOf('#')==-1) {
      sender.tree.unbind('checkboxChange');
      var node = sender.tree.getNodeById(record.id);
      var checkednode = node;
      if(state=='checked') {
        var parentid=node.parent().parent().data('id');
        while(parentid) {
          var allcheck=true;
          var props=node.siblings().add(node).find('> div input[type=checkbox]');
          for(var i=0; i<props.length; i++) {
            allcheck&=props[i].checked;
          }
          node = sender.tree.getNodeById(parentid);
          parentid=node.parent().parent().data('id');
          if(!allcheck) {
            sender.tree.uncheck(node);
            node.find('> div input[type=checkbox]').prop('indeterminate', true);
          } else {
            sender.tree.check(node);
          }
        };
        var child=sender.tree.getChildren(checkednode, true);
        var checked=sender.tree.getCheckedNodes();
        for(var i=0; i<child.length; i++) {
          if(child[i].indexOf('#')==-1) {
            if(checked.indexOf(child[i])==-1) sender.tree.check(sender.tree.getNodeById(child[i]));
          }
        }
      } else {
        var parentid=node.parent().parent().data('id');
        while(parentid) {
          var anycheck=false;
          var props=node.siblings().add(node).find('> div input[type=checkbox]');
          for(var i=0; i<props.length; i++) {
            anycheck|=props[i].checked;
          }
          node = sender.tree.getNodeById(parentid);
          parentid=node.parent().parent().data('id');
          if(node.find('> div input[type=checkbox]').prop('checked')) {
            sender.tree.uncheck(node);
          }
          node.find('> div input[type=checkbox]').prop('indeterminate', anycheck);
        };
        var child=sender.tree.getChildren(checkednode, true);
        for(var i=0; i<child.length; i++) {
          if(child[i].indexOf('#')==-1) sender.tree.uncheck(sender.tree.getNodeById(child[i]));
        }
      }
      sender.tree.bind('checkboxChange', sender.treeChange);
    }
    if(sender.onChange) sender.onChange(sender, record, state);
  }
  setValue(avalue) {
    if(avalue==null) avalue={};
    if(typeof avalue == 'string') {
      if(this.checkbox) {
        avalue = {value: [{id: avalue, text: avalue, checked: true}]};
      } else {
        avalue = {value: [{id: avalue, text: avalue}]};
      }
    } else if((typeof avalue == 'object') && (avalue instanceof Array)) {
      avalue = {value: avalue};
    }
    if(typeof avalue.checkbox != 'undefined') {
      this.checkbox=avalue.checkbox;
    }
    if((typeof avalue.id == 'undefined')&&(this.input.id == '')) avalue.id='tree-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.noclean == 'undefined') {
      avalue.noclean = false;
    }
    if(typeof avalue.clean == 'undefined') {
      avalue.clean = false;
    }
    if((typeof avalue.draggable != 'undefined')) {
      this.draggable = avalue.draggable;
    }
    if(typeof avalue.height != 'undefined') {
      this.input.childNodes[0].style.height = avalue.height;
      this.input.childNodes[0].style.overflowY = 'auto';
      this.input.childNodes[0].classList.add('tree-control');
    }
    if(typeof avalue.uncheck == 'undefined') {
      avalue.uncheck = false;
    }
    if(avalue.uncheck) avalue.noclean = true;
    if(typeof avalue.value != 'undefined') {
      if(avalue.value==null) avalue.value=[];
      for(var i=0; i<avalue.value.length; i++) {
        if(typeof avalue.value[i] == 'string') {
          if(this.checkbox) {
            avalue.value[i] = {id: avalue.value[i], text: avalue.value[i], checked: true};
          } else {
            avalue.value[i] = {id: avalue.value[i], text: avalue.value[i]};
          }
        }
      }
      var addnew=avalue.clean;
      if(!avalue.noclean) {
        for(var i=0; i<avalue.value.length; i++) {
          var cb=this.tree.getNodeById(avalue.value[i].id);
          if(!cb) {
            addnew=true;
          }
        }
      }
      if(addnew) {
        if(avalue.clean) {
          this.tree.render(avalue.value);
        } else {
          for(node in avalue.value) {
            this.tree.addNode(avalue.value[node]);
          }
        }
      } else {
        if(this.checkbox) {
          if(avalue.uncheck) this.tree.uncheckAll();
          for(var i=0; i<avalue.value.length; i++) {
            var node = this.tree.getNodeById(avalue.value[i].id);
            if(node&&avalue.value[i].checked) this.tree.check(node);
          }
        }
      }
    }
    if(typeof avalue.id != 'undefined') {
      this.input.id=avalue.id;
      if(this.label) this.label.htmlFor=this.input.id;
    }
    return true;
  }
  disable() {
    if(!this.tree.find('li').hasClass('disabled')) this.tree.disableAll();
    return true;
  }
  isDisabled() {
    return this.tree.find('li').hasClass('disabled');
  }
  enable() {
    if(this.tree.find('li').hasClass('disabled')) this.tree.enableAll();
    return true;
  }
  getID() {
    return this.input.id;
  }
  getValue() {
    if(this.checkbox) {
      return this.tree.getCheckedNodes();
    } else {
      return this.tree.getSelections();
    }
  }
}

/**
  * onRemove example:<br>
  * obj.onRemove = function(sender, item) { if(typeof item.removed == 'undefined') { showdialog('dfdfd','dfdf','question',['Yes','No'],function(btn) {if(btn=='Yes') { item.removed=true; if( sender.listRemove(sender.list, item)) sender.list.delete(item.id); }}); return false; } else { return true; } }
*/
widgets.collection=class collectionWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data);
    this.node = document.createElement('div');
    this.node.widget = this;
    this.node.className='row';
    this.inputdiv = document.createElement('div');
    this.inputdiv.className='col-12';
    this.inputdiv.widget=this;
    if((typeof data != 'undefined')&&(typeof data.inline != 'undefined')&&data.inline&&(!this.label)) {
      this.node = this.inputdiv;
      this.node.classList.remove('col-12');
      this.node.classList.add('col');
    } else {
      this.node.appendChild(this.inputdiv);
    }
    this.setParent(parent);
    this.onAdd = null;
    this.onRemove = null;
    this.onChange = null;
    this.select = new widgets['select'](this.inputdiv, {search: false}, label, hint);
    this.list = new widgets['list'](this.inputdiv, {checkbox: false, remove: true});
    this.addbtn = document.createElement('button');
    this.addbtn.className='btn btn-success';
    this.addbtn.widget=this;
    this.addbtnicon=document.createElement('span');
    this.addbtnicon.className='d-inline-block d-lg-none oi oi-plus';
    this.addbtnlabel=document.createElement('span');
    this.addbtnlabel.className='d-none d-lg-inline-block';
    this.addbtnlabel.textContent=_('Добавить');
    this.addbtn.appendChild(this.addbtnicon);
    this.addbtn.appendChild(this.addbtnlabel);
    this.select.inputdiv.appendChild(this.addbtn);
    this.select.inputdiv.className='col-12 input-group';
    if(label) this.select.inputdiv.classList.add('col-lg-7');
    this.addbtn.onclick=this.listAdd;
    if((typeof data != 'undefined') && data ) this.setValue(data);
    this.list.onRemove=this.listRemove;
    this.list.onChange=this.listChange;
  }
  listRemove(sender, item) {
    sender=sender.node.parentNode.widget;
    var result = true;
    if(sender.onRemove) result = sender.onRemove(sender, item);
    if(result) sender.select.setValue([item]);
    sender.addbtn.disabled=sender.select.getValue().length==0;
    return result;
  }
  listChange(sender) {
    sender=sender.node.parentNode.widget;
    var result = true;
    if(sender.onChange) result = sender.onChange(sender);
    return result;
  }
  listAdd(sender) {
    sender=sender.target.widget;
    var val=sender.select.getValue();
    var option=sender.select.input.querySelector('option:checked');
    if(option) {
      var result = true;
      if(sender.onAdd) result = sender.onAdd(sender, {id: val, text: option.textContent});
      if(result) {
        sender.list.setValue([{id: val, text: option.textContent}]);
        var j=findById(sender.select.options, val);
        if(j!=-1) sender.select.options.splice(j,1);
        option.parentNode.removeChild(option);
        sender.addbtn.disabled=sender.select.getValue().length==0;
      }
    }
  }
  resize() {
    if(typeof this.select!='undefined') this.select.resize();
    if(typeof this.list!='undefined') this.list.resize();
    return true;
  }
  setValue(avalue) {
    if(typeof avalue == 'string') {
        avalue = {value: [{id: avalue, text: avalue, checked: true}]};
    } else if((typeof avalue == 'object') && (avalue instanceof Array)) {
      avalue = {value: avalue};
    }
    if(avalue == null) avalue={value: []};
    if((typeof avalue == 'object') && (typeof avalue.value == 'undefined')) avalue.value = [];
    if((typeof avalue.id == 'undefined')&&(this.inputdiv.id == '')) avalue.id='collection-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.id != 'undefined') {
      this.inputdiv.id=avalue.id;
      if(this.label) this.label.htmlFor=this.inputdiv.id;
    }
    if(typeof avalue.clean== 'undefined') {
      avalue.clean=false;
    }
    for(var i=0; i<avalue.value.length; i++) {
      if(typeof avalue.value[i] == 'string') avalue.value[i]={id: avalue.value[i], text: avalue.value[i], checked: true};
    }
    var avaliable = [];
    var selected = [];
    var allitems = [];
    if(!avalue.clean) allitems=this.select.options.concat(this.list.getOptions());
    for(var i=0; i<allitems.length; i++) {
       var j=findById(avalue.value, allitems[i].id);
       if(j!=-1) {
         if((typeof avalue.value[j].checked != 'indefined')&&avalue.value[j].checked) {
           selected.push(allitems[i]);
         } else {
           avaliable.push(allitems[i]);
         }
       } else {
         avaliable.push(allitems[i]);
       }
    }
    for(var i=0; i<avalue.value.length; i++) {
      var j=findById(allitems, avalue.value[i].id);
      if(j==-1) {
        if((typeof avalue.value[i].checked != 'undefined')&&avalue.value[i].checked) {
          selected.push(avalue.value[i]);
        } else {
          avaliable.push(avalue.value[i]);
        }
      }
    }
    if(avaliable.length>0) avaliable[0].checked=true;
    this.addbtn.disabled=avaliable.length==0;
    this.select.setValue({value: avaliable, clean: true});
    this.list.setValue({value: selected, clean: true});
    var w=this;
    setTimeout(function() { w.resize() }, 100);
    return true;
  }
  disable() {
    this.select.disable();
    this.list.disable();
    this.addbtn.disabled=true;
    return true;
  }
  isDisabled() {
    return this.select.isDisabled()||this.list.isDisabled();
  }
  enable() {
    this.select.enable();
    this.list.enable();
    this.addbtn.disabled=false;
    return true;
  }
  getID() {
    return this.inputdiv.id;
  }
  getValue() {
    return this.list.getValue();
  }
  simplify() {
    this.select.label.classList.remove('col');
    this.select.label.classList.remove('mb-md-0');
    this.select.label.classList.add('col-12');
    this.select.inputdiv.classList.remove('col-lg-7');
    return true;
  }
}

widgets.iplist=class iplistWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.pairval = false;
    this.node = document.createElement('div');
    this.node.widget = this;
    this.node.className = 'form-group row';
    if(this.label) {
      this.label.className = 'col-12 form-label';
      this.node.appendChild(this.label);
    }
    this.inputdiv = document.createElement('div');
    this.inputdiv.className = 'input-group col-12';
    this.inputdiv.widget = this;
    if((typeof data != 'undefined')&&(typeof data.inline != 'undefined')&&data.inline&&(!this.label)) {
      this.node = this.inputdiv;
      this.node.classList.remove('col-12');
      this.node.classList.add('col');
    } else {
      this.node.appendChild(this.inputdiv);
    }
    this.setParent(parent);

    this.input = document.createElement('input');
    this.input.type = 'text';
    this.input.className = 'form-control';
    this.input.value = '192.168.0.0';
    this.input.pattern = '([0-9]{1,3}\\.){0,3}[0-9]{0,3}';
    this.input.onkeypress = this.inputKeypress;
    this.inputdiv.appendChild(this.input);

    this.select = document.createElement('select');
    this.select.className='custom-select';
    var items = [0,8,12,16,24,25,27,29,32,1,2,3,4,5,6,7,9,10,11,13,14,15,17,18,19,20,21,22,23,26,28,30];
    for(var i=0; i<items.length; i++) {
      var option = document.createElement('option');
      option.text=items[i];
      this.select.appendChild(option);
    }
    this.select.value=24;
    this.inputdiv.appendChild(this.select);

    this.addbtn = document.createElement('button');
    this.addbtn.className = 'btn btn-secondary oi oi-plus';
    this.addbtn.style.minWidth='2.4rem';
    this.addbtn.style.top='0px';
    this.addbtn.widget = this;
    this.addbtn.onclick=this.ipAdd;
    this.inputdiv.appendChild(this.addbtn);

    if((typeof data != 'undefined') && data ) this.setValue(data);
  }
  inputKeypress(sender) {
    var BACKSPACE = 8;
    var DELETE = 46;
    var TAB = 9;
    var LEFT = 37 ;
    var UP = 38 ;
    var RIGHT = 39 ;
    var DOWN = 40 ;
    var END = 35 ;
    var HOME = 35 ;
    var result = false;
    // Checking backspace and delete  
    if(sender.keyCode == BACKSPACE || sender.keyCode == DELETE || sender.keyCode == TAB 
        || sender.keyCode == LEFT || sender.keyCode == UP || sender.keyCode == RIGHT || sender.keyCode == DOWN)  {
        result = true;
    }
    if(sender.target.pattern) {
      var expr=RegExp('^'+sender.target.pattern+'$','g');
      result = expr.test(sender.target.value.substr(0,sender.target.selectionStart)+sender.key+sender.target.value.substr(sender.target.selectionEnd));
    } else result = true;
    return result;
  }
  ipRemove(sender) {
    var elem=sender.target.parentNode;
    elem.parentNode.removeChild(elem);
    return true;
  }
  ipAdd(sender) {
    sender=sender.target.widget;
    var val=sender.input.value;
    var option=sender.select.value;
    sender.setValue([{ip: val, prefix: option}]);
    sender.input.value='192.168.0.0';
    sender.select.value=24;
  }
  setValue(avalue) {
    if(typeof avalue == 'string') {
      var ip=avalue.split('/');
      if(typeof ip[1] == 'undefined') ip[1]=32;
      avalue = {value: [{ip: ip[0], prefix: ip[1]}]};
    } else if((typeof avalue == 'object') && (avalue instanceof Array)) {
      avalue = {value: avalue};
    } else if(avalue === null) {
      avalue = {value: []};
    }
    if(typeof avalue.value == 'string') {
      avalue.value = [avalue.value];
    }
    if(typeof avalue.value == 'undefined') avalue.value=[];
    if((typeof avalue.id == 'undefined')&&(this.inputdiv.id == '')) avalue.id='iplist-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.id != 'undefined') {
      this.inputdiv.id=avalue.id;
      if(this.label) this.label.htmlFor=this.inputdiv.id;
    }
    if(typeof avalue.clean == 'undefined') {
      avalue.clean=false;
    }
    if(typeof avalue.pairval != 'undefined') {
      this.pairval=avalue.pairval;
    }
    if(avalue.clean||avalue.value.length>1) {
      var elem=this.node.childNodes[0];
      while(elem&&(elem!=this.inputdiv)) {
        var current=elem;
        elem=elem.nextSibling;
        if(current.nodeName=='DIV') {
          this.node.removeChild(current);
        }
      }
    }
    for(var i=0; i<avalue.value.length; i++) {
      if(typeof avalue.value[i] == 'string') {
        var ip=avalue.value[i].split('/');
        if(typeof ip[1] == 'undefined') ip[1]=32;
        avalue.value[i] = {ip: ip[0], prefix: ip[1]};
      }
      var elem=this.node.childNodes[0];
      var canadd = true;
      while(elem&&(elem!=this.inputdiv)) {
        var current=elem;
        elem=elem.nextSibling;
        if(current.nodeName=='DIV') {
          var ip=current.childNodes[0].value.split('/');
          if((avalue.value[i].ip==ip[0])) {
            canadd=false;
            break;
          } 
        }
      }
      if(canadd) {
        var div=document.createElement('div');
        div.className = 'input-group col-12';
        this.node.insertBefore(div,this.inputdiv);

        var input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control';
        input.readOnly = true;
        input.value = avalue.value[i].ip+'/'+avalue.value[i].prefix;
        div.appendChild(input);

        var delbtn = document.createElement('button');
        delbtn.className = 'btn btn-secondary oi oi-minus';
        delbtn.style.minWidth='2.4rem';
        delbtn.style.top='0px';
        delbtn.widget = this;
        delbtn.onclick=this.ipRemove;
        div.appendChild(delbtn);
      }
    }
    return true;
  }
  disable() {
    var buttons=this.node.querySelectorAll('.btn');
    for(var i=0; i<buttons.length; i++) {
      buttons[i].disabled=true;
    }
    this.input.disabled=true;
    this.select.disabled=true;
    return true;
  }
  isDisabled() {
    var result = true;
    var buttons=this.node.querySelectorAll('.btn');
    for(var i=0; i<buttons.length; i++) {
      result &= buttons[i].disabled;
    }
    result &= this.input.disabled;
    result &= this.select.disabled;
    return result;
  }
  enable() {
    var buttons=this.node.querySelectorAll('.btn');
    for(var i=0; i<buttons.length; i++) {
      buttons[i].disabled=false;
    }
    this.input.disabled=false;
    this.select.disabled=false;
    return true;
  }
  getID() {
    return this.inputdiv.id;
  }
  getValue() {
    var value = [];
    var elem=this.node.childNodes[0];
    while(elem&&(elem!=this.inputdiv)) {
      var current=elem;
      elem=elem.nextSibling;
      if(current.nodeName=='DIV') {
        if(this.pairval) {
          var ip=current.childNodes[0].value.split('/');
          value.push({ip: ip[0], prefix: ip[1]});
        } else {
          value.push(current.childNodes[0].value);
        }
      }
    }
    return value;
  }
}

widgets.checkbox=class checkboxWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    if(typeof label == 'undefined') label='';
    super(parent,data,label,hint);
    this.checked=0;
    this.node = document.createElement('div');
    this.node.className = 'custom-control custom-checkbox';
    this.node.widget = this;
    this.input = document.createElement('input');
    this.input.type='checkbox';
    this.input.className='custom-control-input';
    this.input.widget=this;
    this.input.onchange=this.checkChange;
    this.onChange=null;
    if((typeof data != 'undefined') && data ) this.setValue(data);
    this.node.appendChild(this.input);
    if(this.label) {
      this.label.className='custom-control-label';
      this.node.appendChild(this.label);
    }
    this.spanbg = document.createElement('span');
    this.spanbg.className = 'bg';
    this.node.appendChild(this.spanbg);
    this.setParent(parent);
  }
  setValue(avalue) {
    if(typeof avalue == 'string') {
      avalue = {value: (avalue == 'yes')||(avalue == 'on')||(avalue == 'true')||(avalue == '1')};
    }
    if(typeof avalue == 'boolean') {
      avalue = {value: avalue};
    }
    if((typeof avalue.id == 'undefined')&&(this.input.id == '')) avalue.id='checkbox-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(avalue.value==null) {
      this.input.indeterminate=true;
      this.checked=1;
      if(typeof avalue.three == 'undefined') avalue.three=true;
    } else {
      this.input.indeterminate=false;
      this.input.checked=avalue.value;
      this.checked=avalue.value?2:0;
    }
    if(typeof avalue.id != 'undefined') {
      this.input.id=avalue.id;
      if(this.label) this.label.htmlFor=this.input.id;
    }
    if(typeof avalue.single != 'undefined') {
      if(avalue.single) {
        this.node.classList.add('form-group');
      } else {
        this.node.classList.add('form-remove');
      }
    }
    if(typeof avalue.three != 'undefined') {
      if(avalue.three) {
        this.input.oninput=this.checkboxInput;
      } else {
        this.input.oninput=null;
      }
    }
    if(typeof avalue.toggle != 'undefined') {
      if(avalue.toggle) {
        this.node.classList.add('toggle');
      } else {
        this.node.classList.remove('toggle');
      }
    }
    if(typeof avalue.large != 'undefined') {
      if(avalue.large) {
        this.node.classList.remove('small');
      } else {
        this.node.classList.add('small');
      }
    }
    if(typeof avalue.right != 'undefined') {
      if(avalue.right) {
        this.node.classList.add('float-right');
      } else {
        this.node.classList.remove('float-right');
      }
    }
    if(typeof avalue.inline != 'undefined') {
      if(avalue.inline) {
        this.node.classList.add('custom-control-inline');
      } else {
        this.node.classList.remove('custom-control-inline');
      }
    }
    return true;
  }
  checkboxInput(sender) {
    sender.preventDefault();
    sender.stopPropagation();
    var widget=sender.target.widget;
    if(widget.checked==2) {
      widget.checked=0;
      widget.input.checked=false;
      widget.input.indeterminate=false;
    } else {
      if(widget.checked==1) {
        widget.checked=2;
        widget.input.checked=true;
        widget.input.indeterminate=false;
      } else {
        widget.checked=1;
        widget.input.checked=false;
        widget.input.indeterminate=true;
      }
    }
  }
  checkChange(sender) {
    var result = true;
    var id = sender.target.id;
    if(sender.target.widget.onChange) result=sender.target.widget.onChange(sender.target.widget);
    return result;
  }
  disable() {
    this.input.disabled=true;
    return true;
  }
  isDisabled() {
    return this.input.disabled;
  }
  enable() {
    this.input.disabled=false;
    return true;
  }
  getID() {
    return this.input.id;
  }
  getValue() {
    if(this.input.indeterminate) return null;
    return this.input.checked;
  }
}

widgets.toggle=class toggleWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    if(typeof label == 'undefined') label='';
    super(parent,data,label,hint);
    this.buttons=false;
    this.checked=0;
    this.node = document.createElement('div');
    this.node.className = 'custom-control custom-switch';
    this.node.widget = this;
    this.input = document.createElement('input');
    this.input.type='checkbox';
    this.input.className='custom-control-input';
    this.input.widget=this;
    this.input.onchange=this.checkChange;
    this.onChange=null;
    if((typeof data != 'undefined') && data ) this.setValue(data);
    this.node.appendChild(this.input);
      if(this.label) {
      this.label.className='custom-control-label';
      this.node.appendChild(this.label);
    }
    this.setParent(parent);
  }
  setValue(avalue) {
    if(typeof avalue == 'string') {
      avalue = {value: (avalue == 'yes')||(avalue == 'on')||(avalue == 'true')||(avalue == '1')};
    }
    if(typeof avalue == 'boolean') {
      avalue = {value: avalue};
    }
    if((typeof avalue.id == 'undefined')&&(this.input.id == '')) avalue.id='toggle-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(avalue.value==null) {
      this.input.indeterminate=true;
      this.checked=1;
      avalue.three=true;
    } else {
      this.input.checked=avalue.value;
      this.checked=avalue.value?2:0;
    }
    if(typeof avalue.id != 'undefined') {
      this.input.id=avalue.id;
      if(this.label) this.label.htmlFor=this.input.id;
    }
    if(typeof avalue.buttons != 'undefined') {
      this.buttons=avalue.buttons;
    }
    if(typeof avalue.single != 'undefined') {
      if(avalue.single) {
        this.node.classList.add('form-group');
      } else {
        this.node.classList.add('form-remove');
      }
    }
    if(typeof avalue.inline != 'undefined') {
      if(avalue.inline) {
        this.node.classList.add('custom-control-inline');
      } else {
        this.node.classList.remove('custom-control-inline');
      }
    }
    if(typeof avalue.large != 'undefined') {
      if(avalue.large) {
        this.node.classList.add('large');
      } else {
        this.node.classList.remove('large');
      }
    }
    if(typeof avalue.three != 'undefined') {
      if(avalue.three) {
        this.input.oninput=this.checkboxInput;
      } else {
        this.input.oninput=null;
      }
    }
    if(typeof avalue.color != 'undefined') {
      if(avalue.color) {
        this.node.classList.add('color');
      } else {
        this.node.classList.remove('color');
      }
    }
    return true;
  }
  checkboxInput(sender) {
    sender.preventDefault();
    sender.stopPropagation();
    var widget=sender.target.widget;
    if(widget.checked==2) {
      widget.checked=0;
      widget.input.checked=false;
      widget.input.indeterminate=false;
    } else {
      if(widget.checked==1) {
        widget.checked=2;
        widget.input.checked=true;
        widget.input.indeterminate=false;
      } else {
        widget.checked=1;
        widget.input.checked=false;
        widget.input.indeterminate=true;
      }
    }
  }
  checkChange(sender) {
    var result = true;
    var id = sender.target.id;
    if(sender.target.widget.onChange) result=sender.target.widget.onChange(sender.target.widget);
    return result;
  }
  disable() {
    this.input.disabled=true;
    return true;
  }
  isDisabled() {
    return this.input.disabled;
  }
  enable() {
    this.input.disabled=false;
    return true;
  }
  getID() {
    return this.input.id;
  }
  getValue() {
    if(this.input.indeterminate) return null;
    return this.input.checked;
  }
}

widgets.radio=class radioWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    if(typeof label == 'undefined') label='';
    super(parent,data,label,hint);
    this.node = document.createElement('div');
    this.node.className = 'custom-control custom-radio';
    this.node.widget = this;
    this.input = document.createElement('input');
    this.input.type='radio';
    this.input.className='custom-control-input';
    this.input.widget=this;
    this.input.onchange=this.radioChange;
    this.onChange=null;
    if((typeof data != 'undefined') && data ) this.setValue(data);
    this.node.appendChild(this.input);
    if(this.label) {
      this.label.className='custom-control-label';
      this.node.appendChild(this.label);
    }
    this.setParent(parent);
  }
  setValue(avalue) {
    if(typeof avalue == 'string') {
      avalue = {value: (avalue == 'yes')||(avalue == 'on')||(avalue == 'true')||(avalue == '1')};
    }
    if(typeof avalue == 'boolean') {
      avalue = {value: avalue};
    }
    if((typeof avalue.id == 'undefined')&&(this.input.id == '')) avalue.id='radio-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    this.input.checked=avalue.value;
    if(typeof avalue.id != 'undefined') {
      this.input.id=avalue.id;
      if(this.label) this.label.htmlFor=this.input.id;
    }
    if(typeof avalue.single != 'undefined') {
      if(avalue.single) {
        this.node.classList.add('form-group');
      } else {
        this.node.classList.add('form-remove');
      }
    }
    if(typeof avalue.inline != 'undefined') {
      if(avalue.inline) {
        this.node.classList.add('custom-control-inline');
      } else {
        this.node.classList.remove('custom-control-inline');
      }
    }
    if(typeof avalue.group != 'undefined') {
      this.input.name=avalue.group;
    }
    return true;
  }
  radioChange(sender) {
    var result = true;
    var id = sender.target.id;
    if(sender.target.widget.onChange) result=sender.target.widget.onChange(sender.target.widget);
    return result;
  }
  disable() {
    this.input.disabled=true;
    return true;
  }
  isDisabled() {
    return this.input.disabled;
  }
  enable() {
    this.input.disabled=false;
    return true;
  }
  getID() {
    return this.input.id;
  }
  getValue() {
    return this.input.checked;
  }
}

widgets.button=class buttonWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label);
    this.node = document.createElement('button');
    this.node.className = 'btn';
    this.node.style.height = 'calc(1.5em + 0.75rem + 2px)';
    this.node.onclick=this.buttonClick;
    this.node.widget = this;
    this.onClick=null;
    this.icon = document.createElement('span');
    this.node.appendChild(this.icon);
    if(this.label) {
      this.node.appendChild(this.label);
    }
    this.setValue(data);
    this.setParent(parent);
  }
  setLabel(avalue) {
    this.labeltext = avalue;
    if(!this.label) {
      this.label = document.createElement('span');
    }
    this.label.textContent = this.labeltext;
    return true;
  }
  buttonClick(sender) {
    var result = true;
    var id = sender.target.id;
    if(sender.target.widget.onClick) result=sender.target.widget.onClick(sender.target.widget);
    return result;
  }
  setValue(avalue) {
    if(avalue==null) avalue = {};
    if(typeof avalue == 'string') {
      avalue = {};
    }
    if((typeof avalue.id == 'undefined')&&(this.node.id == '')) avalue.id='button-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.id != 'undefined') {
      this.node.id=avalue.id;
    }
    if(typeof avalue.onClick != 'undefined') {
      this.onClick=avalue.onClick;
    }
    if(typeof avalue.class != 'undefined') {
      var classname=avalue.class.toLowerCase();
      switch(classname) {
        case 'primary':
        case 'secondary':
        case 'success':
        case 'danger':
        case 'warning':
        case 'info':
        case 'light':
        case 'dark':
        case 'link': {
          this.node.className='btn';
          this.node.classList.add('btn-'+classname);
        } break;
      }
    }
    if(this.node.classList.length==1) {
      this.node.classList.add('btn-primary');
    }
    if(typeof avalue.icon != 'undefined') {
      this.icon.className='oi oi-'+avalue.icon;
      if(this.labeltext!='') this.icon.classList.add('pr-2');
    }
    if(typeof avalue.mobile != 'undefined') {
      if(avalue.mobile) {
        this.icon.classList.add('d-inline-block');
        this.icon.classList.add('d-md-none');
        this.label.classList.add('d-sm-none');
        this.label.classList.add('d-md-inline');
        if((this.labeltext!='')&&(this.icon.classList.contains('pr-2'))) {
          this.icon.classList.add('pr-md-2');
          this.icon.classList.remove('pr-2');
        }
      } else {
        this.icon.classList.remove('d-sm-inline-block');
        this.icon.classList.remove('d-md-none');
        this.label.classList.remove('d-sm-none');
        this.label.classList.remove('d-md-inline');
        if((this.labeltext!='')&&(this.icon.classList.contains('pr-md-2'))) {
          this.icon.classList.add('pr-2');
          this.icon.classList.remove('pr-md-2');
        }
      }
    }
    return true;
  }
  disable() {
    this.node.disabled=true;
    return true;
  }
  isDisabled() {
    return this.node.disabled;
  }
  enable() {
    this.node.disabled=false;
    return true;
  }
  getID() {
    return this.node.id;
  }
  getValue() {
    return null;
  }
}

widgets.buttons=class buttonsWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.multiple=false;
    this.small=true;
    this.container = document.createElement('div');
    this.container.className = 'btn-group btn-group-toggle';
    this.container.dataset.toggle = 'buttons';
    this.container.widget = this;
    if(this.label) {
      this.node = document.createElement('div');
      this.node.widget = this;
      this.node.className='form-group row';
      this.node.appendChild(this.label);
      this.subnode = document.createElement('div');
      this.subnode.className='col-12 col-md-7 text-center';
      this.node.appendChild(this.subnode);
      this.subnode.appendChild(this.container);
    } else {
      this.node=this.container;
    }
    this.class='secondary';
    this.onChange=null;
    if((typeof data != 'undefined') && data ) this.setValue(data);
    this.setParent(parent);
  }
  setValue(avalue) {
    if(typeof avalue == 'string') {
      avalue = {value: [avalue]};
    } else
    if(typeof avalue == 'boolean') {
      avalue = {value: [avalue]};
    } else
    if((typeof avalue == 'object')&&(avalue instanceof Array)) {
      avalue = {value: avalue};
    }
    if((typeof avalue.id == 'undefined')&&(this.container.id == '')) avalue.id='buttons-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.id != 'undefined') {
      this.container.id=avalue.id;
    }
    if(typeof avalue.multiple != 'undefined') {
      this.multiple=avalue.multiple;
    }
    if(typeof avalue.small != 'undefined') {
      this.small=avalue.small;
    }
    if(typeof avalue.class != 'undefined') {
      this.class=avalue.class;
    }
    if(typeof avalue.clean == 'undefined') {
      avalue.clean=false;
    }
    if(typeof avalue.value != 'undefined') {
      if(typeof avalue.value == 'string') {
        avalue.value = [avalue.value];
      } else
      if(typeof avalue.value == 'boolean') {
        avalue.value = [avalue.value];
      }
      var addnodes=false;
      if(this.multiple) {
        var btns=this.container.querySelectorAll('input');
        for(var j=0; j<btns.length; j++) {
          btns[j].checked=false;
          btns[j].parentNode.classList.remove('active');
        }
      }
      for(var i=0; i<avalue.value.length; i++) {
        if(typeof avalue.value[i] == 'string') avalue.value[i]={id: avalue.value[i], text: avalue.value[i], checked: true};
        if(typeof avalue.value[i] == 'boolean') avalue.value[i]={id: avalue.value[i]?'true':'false', text: String(avalue.value[i]), checked: true};
        if(typeof avalue.value[i].checked == 'undefined') avalue.value[i].checked=false;
        var obj = this.container.querySelector('input[value="'+avalue.value[i].id+'"]');
        if(obj) {
          obj.checked = avalue.value[i].checked;
          if(obj.checked) {
            if(!this.multiple) {
              var btns=this.container.querySelectorAll('input');
              for(var j=0; j<btns.length; j++) {
                btns[j].checked=false;
                btns[j].parentNode.classList.remove('active');
              }
            }
            obj.parentNode.classList.add('active');
          } else {
            obj.parentNode.classList.remove('active');
          }
        } else {
          addnodes = true;
        }
      }
      if(avalue.clean) this.container.textContent='';
      if(avalue.clean||addnodes) {
        for(var i=0; i<avalue.value.length; i++) {
          var obj = this.container.querySelector('input[value="'+avalue.value[i].id+'"]');
          if(obj) continue;
          var label = document.createElement('label');
          label.className='btn btn-'+this.class;
          if(this.small) label.style.padding = '0.37rem';
          if(avalue.value[i].checked) label.classList.add('active'); else label.classList.remove('active');
          if(typeof avalue.value[i].icon != 'undefined') {
            var icon = document.createElement('span');
            icon.className='pr-2 oi oi-'+avalue.value[i].icon;
            icon.classList.add('d-inline-block');
            icon.classList.add('d-md-none');
            label.appendChild(icon);
          }
          var text = document.createElement('span');
          text.textContent = avalue.value[i].text;
          label.appendChild(text);
          if(typeof avalue.value[i].shorttext != 'undefined') {
            var shorttext = document.createElement('span');
            shorttext.textContent = avalue.value[i].shorttext;
            label.appendChild(shorttext);
            text.classList.add('d-none');
            text.classList.add('d-lg-inline-block');
            shorttext.classList.add('d-inline-block');
            shorttext.classList.add('d-lg-none');
            if(typeof avalue.value[i].icon != 'undefined') {
              shorttext.classList.add('d-md-none');
            }
          } else {
            if(typeof avalue.value[i].icon != 'undefined') {
              text.classList.add('d-md-inline-block');
              text.classList.add('d-none');
            }
          }
          this.container.appendChild(label);
          var input = document.createElement('input');
          input.name=this.container.id;
          input.value=avalue.value[i].id;
          if(this.multiple) {
            input.type='checkbox';
          } else {
            input.type='radio';
          }
          input.autocomplete='off';
          input.checked=avalue.value[i].checked;
          input.onchange=this.checkChange;
          input.widget=this;
          label.appendChild(input);
        }
      }
    }
    return true;
  }
  checkChange(sender) {
    var result = true;
    if(sender.target.widget.onChange) setTimeout(function() {sender.target.widget.onChange(sender.target.widget)}, 100);
    return result;
  }
  disable() {
    var btns=this.container.querySelectorAll('input');
    for(var j=0; j<btns.length; j++) {
      btns[j].disabled=true;
    }
    return true;
  }
  isDisabled() {
    var result=false;
    var btns=this.container.querySelectorAll('input');
    for(var j=0; j<btns.length; j++) {
      result|=btns[j].disabled;
    }
    return result;
  }
  enable() {
    var btns=this.container.querySelectorAll('input');
    for(var j=0; j<btns.length; j++) {
      btns[j].disabled=false;
    }
    return true;
  }
  getID() {
    return this.container.id;
  }
  getValue() {
    var result=null;
    if(this.multiple) {
      result=[];
      var btns=this.container.querySelectorAll('input');
      for(var j=0; j<btns.length; j++) {
        if(btns[j].parentNode.classList.contains('active')) {
          var val=btns[j].value;
          if(val=='true') val=true;
          if(val=='false') val=false;
          result.push(val);
        };
      }
    } else {
      var btns=this.container.querySelectorAll('input');
      for(var j=0; j<btns.length; j++) {
        if(btns[j].parentNode.classList.contains('active')) {
          var val=btns[j].value;
          if(val=='true') val=true;
          if(val=='false') val=false;
          result=val;
        }
      }
    }
    return result;
  }
}

widgets.datetime=class datetimeWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.utc = false;
    this.node = document.createElement('div');
    this.node.widget = this;
    this.node.className='form-group row';
    if(this.label) this.node.appendChild(this.label);
    this.inputdiv = document.createElement('div');
    this.inputdiv.widget = this;
    if(this.label) {
      this.inputdiv.className='col-12 col-md-7 input-group date';
    } else {
      this.inputdiv.className='col-12 input-group date';
    }
    if((typeof data != 'undefined')&&(typeof data.inline != 'undefined')&&data.inline&&(!this.label)) {
      this.node = this.inputdiv;
      this.node.classList.remove('col-12');
      this.node.classList.add('col');
    } else {
      this.node.appendChild(this.inputdiv);
    }
    this.input = document.createElement('input');
    this.input.type='text';
    this.input.className='form-control';
    this.input.widget=this;
    this.inputdiv.appendChild(this.input);
    this.button = document.createElement('div');
    this.button.className='input-group-append';
    this.inputdiv.appendChild(this.button);
    this.btnlabel = document.createElement('span');
    this.btnlabel.className='datepickerbutton btn btn-secondary clock';
    this.button.appendChild(this.btnlabel);
    this.format = 'DD.MM.YYYY HH:mm:ss';
    this.setParent(parent);
    $(this.inputdiv).datetimepicker({useCurrent: false, locale: document.scrollingElement.lang, format: this.format});
    $(this.inputdiv).data("DateTimePicker").date(moment('1999/01/01 00:00:00'))
    $(this.inputdiv).on("dp.change", this.pickerChange);
    this.onChange=null;
    this.dateFor=null;
    this.dateFrom=null;
    if((typeof data != 'undefined') && data ) this.setValue(data);
  }
  pickerChange(sender) {
    var result = true;
    if(sender.target.widget.onChange) result=sender.target.widget.onChange(sender.target.widget);
    if(result) {
      if(sender.target.widget.dateFor) $(sender.target.widget.dateFor.inputdiv).data("DateTimePicker").minDate(sender.date);
      if(sender.target.widget.dateFrom) $(sender.target.widget.dateFrom.inputdiv).data("DateTimePicker").maxDate(sender.date);
    }
    return result;
  }
  setValue(avalue) {
    if((typeof avalue == 'object')&&(avalue._isAMomentObject)) {
      avalue = {value: avalue};
    } else if(typeof avalue == 'string') {
      avalue = {value: avalue};
    }
    if((typeof avalue.id == 'undefined')&&(this.input.id == '')) avalue.id='datetime-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.utc != 'undefined') {
      this.utc = avalue.utc;
    }
    if(typeof avalue.format != 'undefined') {
      this.format = avalue.format;
      $(this.inputdiv).data("DateTimePicker").format(this.format);
    }
    if((typeof avalue.from != 'undefined')&&(avalue.from instanceof datetimeWidget)) {
      avalue.from.dateFor=this;
      avalue.from.dateFrom=null;
      this.dateFrom=avalue.from;
      this.dateFor=null;
    }
    if((typeof avalue.from != 'undefined')&&(avalue.from._isAMomentObject)) {
      $(this.inputdiv).data("DateTimePicker").minDate(avalue.from);
    }
    if((typeof avalue.for != 'undefined')&&(avalue.for._isAMomentObject)) {
      $(this.inputdiv).data("DateTimePicker").minDate(avalue.for);
    }
    if(typeof avalue.value == 'string') avalue.value = moment(avalue.value, this.format);
    if((typeof avalue.value == 'object')&&(avalue.value._isAMomentObject)) {
      if(this.utc) avalue.value=new moment(avalue.value.utcOffset(avalue.value.utcOffset()*2).format('DD.MM.YYYY HH:mm:ss'), 'DD.MM.YYYY HH:mm:ss');
      $(this.inputdiv).data("DateTimePicker").date(avalue.value);
    }
    if(typeof avalue.id != 'undefined') {
      this.input.id=avalue.id;
      if(this.label) this.label.htmlFor=this.input.id;
    }
    return true;
  }
  disable() {
    $(this.inputdiv).data("DateTimePicker").disable();
    return true;
  }
  isDisabled() {
    return this.input.disabled;
  }
  enable() {
    $(this.inputdiv).data("DateTimePicker").enable();
    return true;
  }
  getID() {
    return this.input.id;
  }
  getMoment() {
    if(this.utc) {
      return $(this.inputdiv).data("DateTimePicker").date().utc();
    } else {
      return $(this.inputdiv).data("DateTimePicker").date();
    }
  }
  getValue() {
    if(this.utc) {
      return $(this.inputdiv).data("DateTimePicker").date().utc().format(this.format);
    } else {
      return $(this.inputdiv).data("DateTimePicker").date().format(this.format);
    }
  }
}

widgets.datetimefromto=class datetimefromtoWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.utc=false;
    this.node = document.createElement('div');
    this.node.widget = this;
    this.node.className='form-group row';
    if(this.label) this.node.appendChild(this.label);
    this.dateFrom=new widgets.datetime(this.node, {}, _('От'));
    this.dateFrom.widget = this;
    this.dateFrom.onChange=this.pickerChange;
    this.inputdiv=this.dateFrom.node;
    this.inputdiv.className='input-group col-12';
    this.inputdiv.widget = this;
    this.dateFor=new widgets.datetime(this.inputdiv, {from: this.dateFrom}, _('по'));
    this.dateFor.widget = this;
    this.dateFor.onChange=this.pickerChange;
    this.inputdiv.appendChild(this.dateFor.label);
    this.inputdiv.appendChild(this.dateFor.inputdiv);
    this.inputdiv.removeChild(this.dateFor.node);
    this.dateFrom.label.classList.remove('col');
    this.dateFrom.label.classList.remove('mb-md-0');
    this.dateFrom.label.classList.add('mb-0');
    this.dateFrom.label.classList.add('pr-1');
    this.dateFrom.inputdiv.classList.remove('col-12');
    this.dateFrom.inputdiv.classList.remove('col-md-7');
    this.dateFrom.inputdiv.classList.add('custom-file');
    this.dateFor.label.classList.remove('col');
    this.dateFor.label.classList.remove('mb-md-0');
    this.dateFor.label.classList.add('mb-0');
    this.dateFor.label.classList.add('pr-1');
    this.dateFor.label.classList.add('pl-1');
    this.dateFor.inputdiv.classList.remove('col-12');
    this.dateFor.inputdiv.classList.remove('col-md-7');
    this.dateFor.inputdiv.classList.add('custom-file');
    this.format = 'DD.MM.YYYY HH:mm:ss';
    this.onChange=null;
    if((typeof data != 'undefined') && data ) this.setValue(data);
    this.setParent(parent);
  }
  pickerChange(sender) {
    var result = true;
    if(sender.widget.onChange) result=sender.widget.onChange(sender.widget);
    return result;
  }
  setValue(avalue) {
    if((typeof avalue == 'object')&&(avalue._isAMomentObject)) {
      avalue = {value: {from: avalue, for: avalue}};
    } else if(typeof avalue == 'string') {
      avalue = {value: {from: avalue, for: avalue}};
    } else if((typeof avalue == 'object')&&((typeof avalue.from != 'undefined')||(typeof avalue.for != 'undefined'))) {
      avalue = {value: avalue};
    }
    if((typeof avalue.value == 'object')&&(avalue.value._isAMomentObject)) avalue.value={from: avalue.value, for: avalue.value};
    if((typeof avalue.id == 'undefined')&&(this.inputdiv.id == '')) avalue.id='datetimefromto-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.utc != 'undefined') {
      this.utc = avalue.utc;
      this.dateFor.setValue({utc: this.utc});
      this.dateFrom.setValue({utc: this.utc});
    }
    if(typeof avalue.format != 'undefined') {
      this.format = avalue.format;
      this.dateFor.setValue({format: this.format});
      this.dateFrom.setValue({format: this.format});
    }
    if(typeof avalue.value == 'object') {
      if((typeof avalue.value.from != 'undefined')&&(typeof avalue.value.for != 'undefined')) {
        if(typeof avalue.value.from == 'string') avalue.value.from = new moment(avalue.value.from, this.format);
        if(typeof avalue.value.for == 'string') avalue.value.for = new moment(avalue.value.for, this.format);
        if((typeof avalue.value.from == 'object')&&(avalue.value.from._isAMomentObject)&&(typeof avalue.value.for == 'object')&&(avalue.value.for._isAMomentObject)) {
          if(avalue.value.from.diff(avalue.value.for)>0) {
            avalue.value.for.add(1,'day');
          }
        }
        if((this.dateFor.getMoment().diff(avalue.value.from)<0)||(this.dateFrom.getMoment().diff(avalue.value.for)>0)) {
          this.dateFor.setValue(new moment(avalue.value.for));
          this.dateFrom.setValue(new moment(avalue.value.from));
          this.dateFor.setValue(avalue.value.for);
          this.dateFrom.setValue(avalue.value.from);
        } else {
          this.dateFrom.setValue(new moment(avalue.value.from));
          this.dateFor.setValue(new moment(avalue.value.for));
          this.dateFrom.setValue(avalue.value.from);
          this.dateFor.setValue(avalue.value.for);
        }
      } else if(typeof avalue.value.from != 'undefined') {
        this.dateFrom.setValue(avalue.value.from);
      } else if(typeof avalue.value.for != 'undefined') {
        this.dateFor.setValue(avalue.value.for);
      }
    }
    if(typeof avalue.id != 'undefined') {
      this.inputdiv.id=avalue.id;
      if(this.label) this.label.htmlFor=this.inputdiv.id;
    }
    return true;
  }
  disable() {
    this.dateFrom.disable();
    this.dateFor.disable();
    return true;
  }
  isDisabled() {
    return this.dateFor.isDisabled()||this.dateFrom.isDisabled();
  }
  enable() {
    this.dateFrom.enable();
    this.dateFor.enable();
    return true;
  }
  getID() {
    return this.inputdiv.id;
  }
  getValue() {
    return {from: this.dateFrom.getValue(), for: this.dateFor.getValue()};
  }
}

widgets.table=class tableWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.node = document.createElement('div');
    this.node.widget = this;
    this.node.className='form-group';
    if(this.label) this.node.appendChild(this.label);
    this.inputdiv = document.createElement('table');
    this.inputdiv.className='table table-sm table-hover';
    this.thead = document.createElement('thead');
    this.thead.className='thread-inverse bg-white p-sticky';
    this.thead.style.zIndex=10;
    this.inputdiv.appendChild(this.thead);
    this.head = document.createElement('tr');
    this.thead.appendChild(this.head);
    this.tbody = document.createElement('tbody');
    this.tbody.className='thread-inverse';
    this.inputdiv.appendChild(this.tbody);
    this.value=[];
    this.header={};
    this.sorted=false;
    this.sortedby = '';
    this.sortasc = true;
    if((typeof data != 'undefined') && data ) this.setValue(data);

    this.node.appendChild(this.inputdiv);
    this.setParent(parent);
  }
  addEntry(parent, level, data) {
    var tr = document.createElement('tr');
    tr.group = null;
    tr.widget = this;
    if(level>1) tr.style.display='none';
    var first=true;
    for(var entry in this.header) {
      var td = document.createElement('td');
      var celltext = (typeof data[entry] != 'undefined')?data[entry]:'';
      if(this.header[entry].filter) {
        celltext=this.header[entry].filter(celltext);
      }
      if(this.header[entry].control) {
        if((celltext!='')||((typeof this.header[entry].control.novalue != 'undefined')&&this.header[entry].control.novalue)) {
          var obj = new widgets[this.header[entry].control.class](td,this.header[entry].control.initval,(typeof this.header[entry].control.title != 'undefined')?this.header[entry].control.title:null);
          obj.row = tr;
          obj.rowdata = data;
          obj.setValue(celltext);
        }
      } else {
        td.textContent = celltext;
      }
      if(first) {
        if((typeof data.value == 'object') && (data.value instanceof Array)||level!=1) {
          for(var j=0; j<level; j++) {
            var spacer = document.createElement('div');
            spacer.className="spacer";
            td.insertBefore(spacer,td.childNodes[0]);
            if((j==0)&&(typeof data.value == 'object') && (data.value instanceof Array)) {
              spacer.className='spacer oi oi-plus';
              spacer.row=tr;
              spacer.onclick=this.spacerClick;
            }
          }
        }
      }

      td.style['text-align']=this.header[entry].align;
      if(this.header[entry].width) {
        td.style.width = this.header[entry].width;
      }
      tr.appendChild(td);
      first=false;
    }
    if(parent) {
      parent.group.push(tr);
    }
    this.tbody.appendChild(tr);
    if((typeof data.value == 'object') && (data.value instanceof Array)) {
      tr.group=[];
      for(var i=0; i<data.value.length; i++) {
        this.addEntry(tr, level+1, data.value[i]);
      }
    }
    return true;
  }
  collapseNode(sender) {
    var result=false;
    for(var i=0; i<sender.group.length; i++) {
      sender.group[i].style.display='none';
      result=true;
    }
    return result;
  }
  expandNode(sender) {
    var result=false;
    for(var i=0; i<sender.group.length; i++) {
      sender.group[i].style.display='table-row';
      result=true;
    }
    return result;
  }
  spacerClick(sender) {
    var widget = sender.target.row.widget;
    if(sender.target.classList.contains('oi-plus')) {
      if(widget.expandNode(sender.target.row)) {
        sender.target.classList.remove('oi-plus');
        sender.target.classList.add('oi-minus');
      }
    } else {
      if(widget.collapseNode(sender.target.row)) {
        sender.target.classList.remove('oi-minus');
        sender.target.classList.add('oi-plus');
      }
    }
  }
  setValue(avalue) {
    if(typeof avalue == 'string') {
      avalue = {id: avalue};
    } else if((typeof avalue == 'object') && (avalue instanceof Array)) {
      avalue = {value: avalue};
    }
    if((typeof avalue.id == 'undefined')&&(this.inputdiv.id == '')) avalue.id='table-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.clean == 'undefined') {
      avalue.clean=false;
    }
    if(typeof avalue.sorted != 'undefined') {
      this.sorted=avalue.sorted;
    }
    if(typeof avalue.head == 'object') {
      avalue.clean=true;
      this.header={};
      this.head.innerHtml='';
      var th=null;
      var i = 0;
      for(var entry in avalue.head) {
        if(this.sorted&&(i==0)) this.sortedby = entry;
        i++;
        this.header[entry]={text: avalue.head[entry], width: null, hint: null, control: null, filter: null, sortfunc: null, sortbadge: null, align: 'left'};
        if(avalue.head[entry]) {
          th = document.createElement('th');
          th.textContent=avalue.head[entry];
          if(this.sorted&&(avalue.head[entry].trim()!='')) {
            th.style.cursor = 'default';
            this.header[entry].sortbadge=document.createElement('span');
            this.header[entry].sortbadge.className='oi oi-chevron-bottom mr-2 text-primary';
            this.header[entry].sortbadge.style.opacity = '0';
            this.header[entry].sortbadge.style.transition = 'opacity 0.3s';
            th.insertAdjacentElement('afterbegin', this.header[entry].sortbadge);
            th.widget = this;
            th.onclick = function(sender) {
              if(sender.currentTarget.widget.sortedby == sender.currentTarget.id) {
                sender.currentTarget.widget.sortasc = !sender.currentTarget.widget.sortasc;
              } else {
                sender.currentTarget.widget.sortedby = sender.currentTarget.id;
                sender.currentTarget.widget.sortasc = true;
              }
              sender.currentTarget.widget.sortValues();
              sender.currentTarget.widget.setValue({value: sender.currentTarget.widget.value, clean: true});
            }
            th.onmouseenter = function(sender) {
              if(sender.currentTarget.widget.sortedby!=sender.currentTarget.id) {
                let badge = sender.currentTarget.widget.header[sender.currentTarget.id].sortbadge
                if(badge) {
                  badge.classList.remove('oi-chevron-top');
                  badge.classList.add('oi-chevron-bottom');
                  badge.style.opacity = '0.2';
                }
              }
            }
            th.onmouseleave = function(sender) {
              if(sender.currentTarget.widget.sortedby!=sender.currentTarget.id) {
                let badge = sender.currentTarget.widget.header[sender.currentTarget.id].sortbadge
                if(badge) {
                  badge.style.opacity = '0';
                }
              }
            }
          }
          th.colSpan=1;
          th.id=entry;
          this.head.appendChild(th);
        } else {
          th.colSpan=th.colSpan+1;
        }
      }
    }
    if(typeof avalue.value == 'object') {
      if(avalue.clean) {
        this.value=avalue.value;
      } else {
        this.value=this.value.concat(avalue.value);
      }
      let values = avalue.value;
      if(this.sorted||avalue.clean) {
        this.tbody.innerHTML='';
        values = this.value;
      }
      if(this.sorted) this.sortValues();
      for(var i=0; i<values.length; i++) {
        this.addEntry(null, 1, values[i]);
      }
    }
    if(typeof avalue.id != 'undefined') {
      this.inputdiv.id=avalue.id;
      if(this.label) this.label.htmlFor=this.inputdiv.id;
    }
    return true;
  }

  sortValues() {
    if(this.sortedby=='') return;
    let widget = this;
    this.value.sort(function(a, b) {
      if((typeof a[widget.sortedby] !== 'undefined')&&(typeof b[widget.sortedby] !== 'undefined')) {
        if(widget.header[widget.sortedby].sortfunc) {
          return (widget.sortasc?1:-1)*widget.header[widget.sortedby].sortfunc(a[widget.sortedby], b[widget.sortedby]);
        } else {
          return (widget.sortasc?1:-1)*a[widget.sortedby].toString().localeCompare(b[widget.sortedby].toString(), [], {numeric: true});
        }
      } else {
        return (widget.sortasc?1:-1)*a.toString().localeCompare(b.toString(), [], {numeric: true});
      }
    });
    for(let entry in this.header) {
      if(this.header[entry].sortbadge) {
        this.header[entry].sortbadge.style.opacity = 0;
      }
    }
    if(this.header[this.sortedby].sortbadge) {
      this.header[this.sortedby].sortbadge.style.opacity = 1;
      if(this.sortasc) {
        this.header[this.sortedby].sortbadge.classList.add('oi-chevron-bottom');
        this.header[this.sortedby].sortbadge.classList.remove('oi-chevron-top');
      } else {
        this.header[this.sortedby].sortbadge.classList.remove('oi-chevron-bottom');
        this.header[this.sortedby].sortbadge.classList.add('oi-chevron-top');
      }
    }
  }

  /**
  * \fn bool setHeadHint(String acolumn, String ahint)
  * Устанавливает текст всплывающей подсказки заголовка, может принимать форматированный HTML текст в качестве значения.
  * \tparam String acolumn Наименование столбца
  * \tparam String ahint Текст подсказки
  * \return Истину при успешной смене текста подсказки
  */
  setHeadHint(acolumn, ahint) {
    if(typeof this.header[acolumn] != 'undefined') {
      if(!this.header[acolumn].hint) {
        this.header[acolumn].hint=document.createElement('span');
        this.header[acolumn].hint.className='badge badge-pill badge-info badge-help';
        this.header[acolumn].hint.setAttribute('data-toggle','popover');
        this.header[acolumn].hint.setAttribute('data-placement','top');
        this.header[acolumn].hint.setAttribute('data-trigger','hover');
        this.header[acolumn].hint.setAttribute('data-html',true);
        $(this.header[acolumn].hint).popover();
        var col = this.head.querySelector('[id=\''+acolumn+'\'');
        if(col.childNodes.length>0) {
          let textnode = col.childNodes[0];
          while(textnode&&(textnode.nodeType!=3)) textnode = textnode.nextSibling;
          this.header[acolumn].hint.setAttribute('data-original-title',textnode.textContent);
          textnode.textContent+=' ';
          col.appendChild(this.header[acolumn].hint);
        }
      }
      this.header[acolumn].hint.setAttribute('data-content',ahint);
      return true;
    }
    return false;
  }

  /**
  * \fn bool showColumn(String acolumn)
  * Показывает столбец таблицы
  * \tparam String acolumn Наименование столбца
  * \return Истину при успешной смене отображения столбца
  */
  showColumn(acolumn) {
    if(typeof this.header[acolumn] != 'undefined') {
      var col = this.head.querySelector('[id=\''+acolumn+'\'');
      if(col) {
        col.classList.remove('d-none');
        return true;
      }
    }
    return false;
  }

  /**
  * \fn bool hideColumn(String acolumn)
  * Скрывает столбец таблицы
  * \tparam String acolumn Наименование столбца
  * \return Истину при успешной смене отображения столбца
  */
  hideColumn(acolumn) {
    if(typeof this.header[acolumn] != 'undefined') {
      var col = this.head.querySelector('[id=\''+acolumn+'\'');
      if(col) {
        col.classList.add('d-none');
        return true;
      }
    }
    return false;
  }

  /**
  * \fn bool setHeadWidth(String acolumn, String awidth)
  * Устанавливает ширину загловка таблицы.
  * \tparam String acolumn Наименование столбца
  * \tparam String awidth Ширина столбца
  * \return Истину при успешной смене ширины столбца
  */
  setHeadWidth(acolumn, awidth) {
    if(typeof this.header[acolumn] != 'undefined') {
      this.header[acolumn].width = awidth;
      var col = this.head.querySelector('[id=\''+acolumn+'\'');
      if(col) col.style.width = awidth;
      return true;
    }
    return false;
  }

    /**
  * \fn bool setCellAlign(String acolumn, String aalign)
  * Устанавливает выравнивание текста в ячейках таблицы.
  * \tparam String acolumn Наименование столбца
  * \tparam String aalign Выравнивание ячеек
  * \return Истину при успешной смене выравнивния ячеек
  */
  setCellAlign(acolumn, aalign) {
    if(typeof this.header[acolumn] != 'undefined') {
      this.header[acolumn].align = aalign;
      return true;
    }
    return false;
  }

  setCellSortFunc(acolumn, asortfunc) {
    if(typeof this.header[acolumn] != 'undefined') {
      this.header[acolumn].sortfunc=asortfunc;
      return true;
    }
  }

  setCellFilter(acolumn, afilter) {
    if(typeof this.header[acolumn] != 'undefined') {
      this.header[acolumn].filter=afilter;
      return true;
    }
  }

  setCellControl(acolumn, acontrol) {
    if(typeof this.header[acolumn] != 'undefined') {
      this.header[acolumn].control=acontrol;
      return true;
    }
    return false;
  }
  disable() {
    var nodes = this.node.querySelectorAll('input');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=true;
    }
    nodes = this.node.querySelectorAll('select');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=true;
    }
    nodes = this.node.querySelectorAll('button');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=true;
    }
    this.inputdiv.disabled = true;
    return true;
  }
  disabled() {
    return this.inputdiv.disabled;
  }
  enable() {
    var nodes = this.node.querySelectorAll('input');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=false;
    }
    nodes = this.node.querySelectorAll('select');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=false;
    }
    nodes = this.node.querySelectorAll('button');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=false;
    }
    this.inputdiv.disabled = false;
    return true;
  }
  getID() {
    return this.inputdiv.id;
  }
  getValue() {
    return this.value;
  }
}

widgets.audio=class audioWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label);
    if(typeof widgets.audio.collection == 'undefined') widgets.audio.collection=[];
    widgets.audio.collection.push(this);
    this.node = document.createElement('div');
    this.node.widget = this;
    this.onPlay=null;
    this.onPause=null;
    this.audio = document.createElement('audio');
    this.audio.onerror=this.playError;
    this.audio.onplay=this.onAudioPlay;
    this.audio.onpause=this.onAudioPause;
    this.audio.widget = this;
    this.node.appendChild(this.audio);
    this.playbtn = new widgets.button(this.node, {class: 'light', icon: 'media-play'});
    this.playbtn.onClick = this.playClick;
    this.playbtn.node.classList.add('w-50');
    this.playbtn.node.classList.add('btn-sm');
    this.playbtn.icon.classList.remove('pr-2');
    this.downloadbtn = new widgets.button(this.node, {class: 'secondary', icon: 'data-transfer-download'});
    this.downloadbtn.onClick = this.downloadClick;
    this.downloadbtn.node.classList.add('w-50');
    this.downloadbtn.node.classList.add('btn-sm');
    this.downloadbtn.icon.classList.remove('pr-2');
    if(this.label) {
      this.node.appendChild(this.label);
    }
    this.setValue(data);
    this.setParent(parent);
  }
  playpause(sender) {
    if(!sender.audio.paused) {
      sender.audio.pause();
      sender.playbtn.icon.classList.remove("oi-media-pause");
      sender.playbtn.icon.classList.add("oi-media-play");
    } else {
      for(var i=0; i<widgets.audio.collection.length; i++) {
        if(!widgets.audio.collection[i].audio.paused) {
          widgets.audio.collection[i].audio.pause();
          sender.playbtn.icon.classList.remove("oi-media-pause");
          sender.playbtn.icon.classList.add("oi-media-play");
        }
      }
      sender.audio.play();
      sender.playbtn.icon.classList.remove("oi-media-play");
      sender.playbtn.icon.classList.add("oi-media-pause");
    }
  }
  playError(sender) {
    var result = true;
    var widget = sender.target.widget;
    widget.playpause(widget);
    showalert('danger',_('Невозможно воспроизвести запись'));
    return result;
  }
  playClick(sender) {
    var result = true;
    var widget = sender.node.parentNode.widget;
    widget.playpause(widget);
    return result;
  }
  downloadClick(sender) {
    var result = true;
    var widget = sender.node.parentNode.widget;
    window.location=widget.audio.src;
    return result;
  }
  onAudioPlay(sender) {
    let widget = sender.currentTarget.widget;
    if(widget.onPlay) return widget.onPlay(widget);
    return false;
  }
  onAudioPause(sender) {
    let widget = sender.currentTarget.widget;
    if(widget.onPause) return widget.onPause(widget);
    return false;
  }
  setValue(avalue) {
    if(avalue==null) avalue = {};
    if(typeof avalue == 'string') {
      avalue = {value: avalue};
    }
    if((typeof avalue.id == 'undefined')&&(this.node.id == '')) avalue.id='audio-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.id != 'undefined') {
      this.node.id=avalue.id;
    }
    if(typeof avalue.value == 'string') {
      this.audio.src = avalue.value;
    }
    return true;
  }
  disable() {
    this.node.disabled=true;
    return true;
  }
  isDisabled() {
    return this.node.disabled;
  }
  enable() {
    this.node.disabled=false;
    return true;
  }
  getID() {
    return this.node.id;
  }
  getValue() {
    return this.audio.src;
  }
}

widgets.colorpicker=class colorpickerWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.node = document.createElement('div');
    this.node.widget = this;
    this.node.className='custom-control color-container';
    this.node.style.textAlign='center';
    this.node.dataset['color']='rgb(0,0,0)';
    this.input = document.createElement('div');
    this.node.appendChild(this.input);
    if(this.label) this.node.appendChild(this.label);
    this.input.className='color-box rounded-circle';
    this.input.widget=this;
    this.onChange=null;
    this.setParent(parent);
    $(this.node).colorpicker({input: '.color-box', hasAlpha: true});
    if((typeof data != 'undefined') && data ) this.setValue(data);
    $(this.node).on('colorpickerChange', this.inputChange);
  }
  inputChange(sender) {
    sender.target.widget.input.style.backgroundColor = sender.target.widget.getValue().toString();   
    if(sender.target.widget.onChange) {
      sender.target.widget.onChange(sender.target.widget, {id: sender.target.widget.getID(), data: sender.target.widget.getValue()});
      return true;
    }
    return false;
  }
  setValue(avalue) {
    if((typeof avalue=='undefined') || (avalue===null)) avalue = {};
    if(typeof avalue == 'string') avalue = {value: avalue};
    if((typeof avalue.id == 'undefined')&&(this.node.id == '')) avalue.id='colorpicker-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.id != 'undefined') {
      this.node.id=avalue.id;
      if(this.label) this.label.htmlFor=this.node.id;
    }
    if(typeof avalue.value != 'undefined') {
      $(this.node).data('colorpicker').setValue(avalue.value.trim());
      this.input.style.backgroundColor = this.getValue().toString();
    }
    return true;
  }
  disable() {
    this.node.disabled=true;
    return true;
  }
  disabled() {
    return this.node.disabled;
  }
  enable() {
    this.node.disabled=false;
    return true;
  }
  getID() {
    return this.node.id;
  }
  getValue() {
    return $(this.node).colorpicker().data('color');
  }
}

widgets.diallist=class diallistWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.node = document.createElement('div');
    this.node.widget = this;
    this.node.className='form-control';
    this.defaultchantype = null;
    this.users = [];
    this.chantypes = [];
    this.chans = [];
    this.inputdiv = document.createElement('div');
    this.inputdiv.className='w-100';
    this.node.appendChild(this.inputdiv);
    this.setParent(parent);
    if((typeof data != 'undefined') && data ) { 
      this.setValue(data); 
    } else {
      var entry=this.createEntry('/');
      entry.widget=this;
      this.inputdiv.appendChild(entry);
      entry.btn.setValue({icon: 'oi oi-plus', onClick: this.newEntry});
    }
  }
  onDriverChange(sender) {
    sender.entry.widget.chans.splice(0);
    var chantype = sender.getValue();
    for(var i in sender.entry.widget.users) {
      if(sender.entry.widget.users[i].type == chantype) sender.entry.widget.chans.push({id: sender.entry.widget.users[i].login, text: sender.entry.widget.users[i].name});
    }
    var chanid = null;
    if(sender.entry.widget.chans.length>0) chanid = sender.entry.widget.chans[0].id;
    sender.entry.chan.setValue({value: sender.entry.widget.chans, newvalue: chanid, clean: true});
    sender.entry.widget.onUserChange(sender.entry.chan);
  }
  onUserChange(sender) {
    var widget = sender.entry.widget;
    for(var i in widget.users) {
      if((widget.users[i].type == sender.entry.chantype.getValue()) && (widget.users[i].login == sender.entry.chan.getValue())) {
        if(widget.users[i].mode=='trunk') {
          sender.entry.trunknum.show();
        } else {
          sender.entry.trunknum.hide();
        }
        break;
      }
    }
  }
  createEntry(chan) {
    var inputdiv = document.createElement('div');
    inputdiv.className = 'input-group';
    inputdiv.widget = this;
    inputdiv.chantype = new widgets.select(inputdiv, {id: 'chantype', value: this.chantypes, clean: true, search: false, inline: true});
    inputdiv.chantype.widget=this;
    inputdiv.chantype.entry=inputdiv;
    inputdiv.chantype.onChange = this.onDriverChange;
    inputdiv.chantype.input.classList.remove('col');
    inputdiv.chantype.input.classList.add('col-3');
    inputdiv.chan = new widgets.select(inputdiv, {id: 'chan', value: this.chans, clean: true, inline: true});
    inputdiv.chan.widget=this;
    inputdiv.chan.entry=inputdiv;
    inputdiv.chan.onChange = this.onUserChange;
    inputdiv.trunknum = new widgets.input(inputdiv, {id: 'trunknum', value: '', inline: true, placeholder: _('Номер'), pattern: '[+]{0,1}[0-9]*|[a-z]{1}[a-z0-9._-]*'});
    inputdiv.trunknum.hide();
    inputdiv.btn = new widgets.button(inputdiv, {id: 'btn', class: 'secondary', icon: 'oi oi-minus'}, '');
    inputdiv.btn.entry = inputdiv;
    inputdiv.btn.onClick=this.removeEntry;
    if(chan!='') {
      var chandata=chan.split('/');
      var chantype = chandata[0];
      if(chantype=='') {
        if(this.defaultchantype) chantype = this.defaultchantype;
        else if(this.chantypes.length>0) chantype = this.chantypes[0].id;
      }
      if(chantype!='') inputdiv.chantype.setValue(chantype);
      this.onDriverChange(inputdiv.chantype);
      if((typeof chandata[1] != 'undefined')&&(chandata[1]!='')) {
        var chanuser = chandata[1];
        inputdiv.chan.setValue(chanuser);
        if(chandata.length>2) {
          inputdiv.trunknum.setValue(chandata[2]);
          inputdiv.trunknum.show();
        } else {
          inputdiv.trunknum.hide();
        }
      }
    } else {
      inputdiv.trunknum.hide();
    }
    return inputdiv;
  }
  getEntry(sender) {
    var actiondata = sender.chantype.getValue()+'/'+sender.chan.getValue();
    if(!sender.trunknum.node.classList.contains('d-none')) actiondata += '/'+sender.trunknum.getValue();
    return actiondata;
  }
  removeEntry(sender) {
    var result = true;
    var parent = sender.entry.parentNode;
    var widget = sender.entry.widget;
    parent.removeChild(sender.entry);
    return true;
  }
  newEntry(sender) {
    var result = true;
    var entry = sender.entry.widget.createEntry('/');
    var parent = sender.entry.parentNode;
    var widget = sender.entry.widget;
    entry.widget = widget;
    parent.appendChild(entry);
    return result;
  }
  setValue(avalue) {
    if(typeof avalue == 'string') {
      avalue = {value: [avalue]};
    } else if((typeof avalue == 'object') && (avalue instanceof Array)) {
      avalue = {value: avalue};
    }
    if((typeof avalue.id == 'undefined')&&(this.inputdiv.id == '')) avalue.id='diallist-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.id != 'undefined') {
      this.inputdiv.id=avalue.id;
    }
    if(typeof avalue.users != 'undefined') {
      this.users.splice(0);
      this.users.push.apply(this.users, avalue.users);
      this.chantypes.splice(0);
      for(var i in this.users) {
        var hastype = false;
        for(var j in this.chantypes) {
          if(this.chantypes[j].id == this.users[i].type) {
            hastype = true;
            break;
          }
        }
        if(!hastype) {
          this.chantypes.push({id: this.users[i].type, text: this.users[i].type});
        }
      }
      if(typeof avalue.value == 'undefined') avalue.value = this.getValue();
    }
    if(typeof avalue.value != 'undefined') {
      this.inputdiv.textContent='';
      if(avalue.value.length==0) {
        var entry=this.createEntry('/');
        entry.widget=this;
        this.inputdiv.appendChild(entry);
        entry.btn.setValue({icon: 'oi oi-plus', onClick: this.newEntry});
      } else {
        for(var i=0; i<avalue.value.length; i++) {
          var entry=this.createEntry(avalue.value[i]);
          entry.widget=this;
          this.inputdiv.appendChild(entry);
          if(this.inputdiv.childNodes.length==1) entry.btn.setValue({icon: 'oi oi-plus', onClick: this.newEntry});
        }
      }
    }
    return true;
  }
  disable() {
    var nodes = this.node.querySelectorAll('input');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=true;
    }
    nodes = this.node.querySelectorAll('select');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=true;
    }
    nodes = this.node.querySelectorAll('button');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=true;
    }
    return true;
  }
  disabled() {
    return this.inputdiv.btn.disabled;
  }
  enable() {
    var nodes = this.node.querySelectorAll('input');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=false;
    }
    nodes = this.node.querySelectorAll('select');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=false;
    }
    nodes = this.node.querySelectorAll('button');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=false;
    }
    return true;
  }
  getID() {
    return this.inputdiv.id;
  }
  getValue() {
    var result=[];
    for(var i=0; i<this.inputdiv.childNodes.length; i++) {
      let actiondata = this.getEntry(this.inputdiv.childNodes[i]);
      if(actiondata!='/') result.push(actiondata);
    }
    return result;
  }
}

widgets.contactactions=class useractionWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.node = document.createElement('div');
    this.node.widget = this;
    this.node.className='form-group row';
    if(this.label) {
      this.label.className = 'col-12 form-label';
      this.node.appendChild(this.label);
    }
    this.applications = [];
    this.users = [];
    this.inputdiv = document.createElement('div');
    this.inputdiv.className='form-group col-12';
    this.node.appendChild(this.inputdiv);
    this.sortlist = new Sortable(this.inputdiv, {animation: 150, touchStartThreshold: 10, preventOnFilter: false, filter: function(e) {
      return false;
    }, draggable: 'div.input-group', onMove: function(e) {
      var result=true;
      return result;
    }, onUpdate: function(e) {
      var content=this.el;
      for(var i=0; i<content.childNodes.length; i++) {
        content.childNodes[i].label.setLabel(i+1);
        if(i==0) content.childNodes[i].btn.setValue({onClick: content.childNodes[i].widget.newAction, class: 'success', icon: 'oi oi-plus'});
        else content.childNodes[i].btn.setValue({onClick: content.childNodes[i].widget.removeAction, class: 'danger', icon: 'oi oi-minus'});
      }
    } });
    this.setParent(parent);
    if((typeof data != 'undefined') && data ) this.setValue(data);
  }
  onTypeChange(sender) {
    switch(sender.getValue()) {
      case 'dial': {
        sender.entry.dial.show();
        sender.entry.dialtimeout.show();
        sender.entry.dialoptions.show();
        sender.entry.customaction.hide();
        sender.entry.app.hide();
        sender.entry.appdata.hide();
      } break;
      case 'wait': {
        sender.entry.dial.hide();
        sender.entry.dialtimeout.show();
        sender.entry.dialoptions.hide();
        sender.entry.customaction.hide();
        sender.entry.app.hide();
        sender.entry.appdata.hide();
      } break;
      case 'custom': {
        sender.entry.dial.hide();
        sender.entry.dialtimeout.hide();
        sender.entry.dialoptions.hide();
        sender.entry.customaction.hide();
        sender.entry.app.show();
        sender.entry.appdata.show();
      } break;
      case 'other': {
        sender.entry.dial.hide();
        sender.entry.dialtimeout.hide();
        sender.entry.dialoptions.hide();
        sender.entry.customaction.show();
        sender.entry.app.hide();
        sender.entry.appdata.hide();
      } break;
      default: {
        sender.entry.dial.hide();
        sender.entry.dialtimeout.hide();
        sender.entry.dialoptions.hide();
        sender.entry.customaction.hide();
        sender.entry.app.hide();
        sender.entry.appdata.hide();
      } break;
    }
  }
  createAction(type, application, appdata, timeout, options) {
    var inputdiv = document.createElement('div');
    inputdiv.className = 'input-group';
    inputdiv.widget = this;
    inputdiv.label = new widgets.label(inputdiv, null, '');
    inputdiv.label.node.className='pl-0 pr-2';
    inputdiv.label.node.style.display = 'flex';
    inputdiv.label.node.style.minWidth = '1.5rem';
    inputdiv.label.node.style.textAlign = 'center';
    inputdiv.label.node.style.height = 'calc(1.5em + 0.75rem + 2px)';
    inputdiv.label.label.classList.remove('col');
    inputdiv.label.label.classList.add('w-100');
    var typeoptions=[
            {id: 'dial', text: _('Позвонить')},
            {id: 'wait', text: _('Пауза')},
            {id: 'custom', text: _('Действие')},
            {id: 'other', text: _('Иное')}
    ];
    inputdiv.type = new widgets.select(inputdiv, {id: 'type', value: typeoptions, search: false, inline: true, clean: true});
    inputdiv.type.setValue(type);
    inputdiv.type.widget=this;
    inputdiv.type.entry=inputdiv;
    inputdiv.type.onChange = this.onTypeChange;
    inputdiv.dial = new widgets.diallist(inputdiv, {id: 'dials', users: this.users});
    inputdiv.dial.node.className='col-6';
    inputdiv.dial.node.style.padding = '0';
    inputdiv.dial.hide();
    if(type=='dial') {
      if(typeof appdata == 'string') {
        inputdiv.dial.setValue(appdata.split('&'));
      } else {
        inputdiv.dial.setValue(appdata);
      }
    }
    inputdiv.dialtimeout = new widgets.input(inputdiv, {id: 'timeout', value: '', inline: true, placeholder: _('Таймаут')});
    inputdiv.dialtimeout.setValue(timeout);
    inputdiv.dialtimeout.hide();
    inputdiv.dialoptions = new widgets.input(inputdiv, {id: 'options', value: '', inline: true, placeholder: _('Опции')});
    inputdiv.dialoptions.setValue(options);
    inputdiv.dialoptions.hide();
    inputdiv.customaction = new widgets.input(inputdiv, {id: 'value', inline: true, placeholder: _('Действие')});
    inputdiv.customaction.hide();
    inputdiv.customaction.setValue(application+appdata?('('+appdata+')'):'');
    inputdiv.app = new widgets.select(inputdiv, {id: 'app', value: this.applications, inline: true, search: false, clean: true});
    inputdiv.app.hide();
    if(application) inputdiv.app.setValue(application);
    inputdiv.appdata = new widgets.input(inputdiv, {id: 'appdata', value: '', inline: true, placeholder: _('Аргументы')});
    inputdiv.appdata.hide();
    if(typeof appdata == 'string') {
      inputdiv.appdata.setValue(appdata);
    } else {
      inputdiv.appdata.setValue(appdata.join('&')+','+timeout+','+options);
    }
    inputdiv.btn = new widgets.button(inputdiv, {id: 'btn', class: 'danger', icon: 'oi oi-minus'}, '');
    inputdiv.btn.entry = inputdiv;
    inputdiv.btn.onClick=this.removeAction;
    this.onTypeChange(inputdiv.type);
    return inputdiv;
  }
  getAction(sender) {
    var actiondata = {type: '', app: '', appdata: '', timeout: '', options: ''};
    actiondata.type = sender.type.getValue();
    switch(actiondata.type) {
      case 'dial': {
        actiondata.app = 'Dial';
        actiondata.appdata = sender.dial.getValue().join('&');
        actiondata.timeout = sender.dialtimeout.getValue();
        actiondata.options = sender.dialoptions.getValue();
      } break;
      case 'wait': {
        actiondata.app = 'Wait';
        actiondata.appdata = sender.dialtimeout.getValue();
        actiondata.timeout = sender.dialtimeout.getValue();
      } break;
      case 'custom': {
        actiondata.app = sender.app.getValue();
        actiondata.appdata = sender.appdata.getValue();
      } break;
      default: {
        actiondata.app = sender.appdata.getValue();
      }
    }
    return actiondata;
  }
  removeAction(sender) {
    var parent = sender.entry.parentNode;
    parent.removeChild(sender.entry);
    for(var i=0; i<parent.childNodes.length; i++) {
      parent.childNodes[i].label.setLabel(i+1);
    }
    return false;
  }
  newAction(sender) {
    var result = true;
    var entry = sender.entry.widget.createAction('dial', 'Dial', '/', '', '');
    entry.widget=sender.entry.widget;
    entry.label.setLabel(sender.entry.widget.inputdiv.childNodes.length+1);
    sender.entry.widget.inputdiv.appendChild(entry);
    return result;
  }
  setValue(avalue) {
    if(typeof avalue == 'string') {
      avalue = {value: [avalue]};
    } else if((typeof avalue == 'object') && (avalue instanceof Array)) {
      avalue = {value: avalue};
    }
    if((typeof avalue.id == 'undefined')&&(this.inputdiv.id == '')) avalue.id='useraction-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.id != 'undefined') {
      this.inputdiv.id=avalue.id;
      if(this.label) this.label.htmlFor=this.inputdiv.id;
    }
    if(typeof avalue.users != 'undefined') {
      this.users.splice(0);
      this.users.push.apply(this.users, avalue.users);
    }
    if(typeof avalue.applications != 'undefined') {
      this.applications.splice(0);
      this.applications.push.apply(this.applications, avalue.applications);
    }
    if(typeof avalue.value != 'undefined') {
      this.inputdiv.textContent='';
      for(var i=0; i<avalue.value.length; i++) {
        if(typeof avalue.value[i] == 'string') {
          var actiondata = {type: '', app: '', data: '', timeout: '', options: ''};
          data=avalue.value[i].split('(');
          if(typeof data[1] != 'undefined') data[1] = data[1].split(')')[0]; else data[1]='';
          var app=data[0].trim();
          var appdata=data[1].trim();
          switch(app.toLowerCase()) {
            case 'dial': {
              var args = appdata.split(',');
              actiondata.type = 'dial';
              actiondata.app = 'Dial';
              actiondata.data = appdata;
              actiondata.dials = args[0].trim().split('&');
              if(typeof args[1] != 'undefined') actiondata.timeout = args[1].trim();
              if(typeof args[2] != 'undefined') actiondata.options = args[2].trim();
            } break;
            case 'wait': {
              actiondata.type = 'wait';
              actiondata.app = 'Wait';
              actiondata.data = appdata;
              actiondata.timeout = appdata;
            } break;
            default: {
              if(appdata=='') actiondata.type='other';
              else actiondata.type = 'custom';
              actiondata.app = app;
              actiondata.data = appdata;
            } break;
          }
          avalue.value[i]=actiondata;
        }
        switch(avalue.value[i].type) {
          case 'dial': {
            avalue.value[i].app = 'Dial';
            avalue.value[i].data = avalue.value[i].dials;
          } break;
          case 'wait': {
            avalue.value[i].app = 'Wait';
          }
          default: {
            if(typeof avalue.value[i].app == 'undefined') avalue.value[i].app = '';
            if(typeof avalue.value[i].data == 'undefined') avalue.value[i].data = '';
            if(typeof avalue.value[i].timeout == 'undefined') avalue.value[i].timeout = '';
            if(typeof avalue.value[i].options == 'undefined') avalue.value[i].options = '';
          }               
        }
        var entry=this.createAction(avalue.value[i].type, avalue.value[i].app, avalue.value[i].data, avalue.value[i].timeout, avalue.value[i].options);
        entry.widget=this;
        entry.label.setLabel(i+1);
        this.inputdiv.appendChild(entry);
      }
      if(avalue.value.length==0) {
        var entry=this.createAction('dial', 'Dial', '/', '', '');
        entry.widget=this;
        entry.label.setLabel(1);
        this.inputdiv.appendChild(entry);
      }
      this.inputdiv.childNodes[0].btn.setValue({onClick: this.newAction, class: 'success', icon: 'oi oi-plus'});
    }
    return true;
  }
  disable() {
    var nodes = this.node.querySelectorAll('input');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=true;
    }
    nodes = this.node.querySelectorAll('select');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=true;
    }
    nodes = this.node.querySelectorAll('button');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=true;
    }
    return true;
  }
  disabled() {
    return this.inputdiv.btn.disabled;
  }
  enable() {
    var nodes = this.node.querySelectorAll('input');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=false;
    }
    nodes = this.node.querySelectorAll('select');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=false;
    }
    nodes = this.node.querySelectorAll('button');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=false;
    }
    return true;
  }
  getID() {
    return this.inputdiv.id;
  }
  getValue() {
    var result=[];
    for(var i=0; i<this.inputdiv.childNodes.length; i++) {
      result.push(this.getAction(this.inputdiv.childNodes[i]));
    }
    return result;
  }
}

widgets.multiplecollection=class multiplecollectionWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.node = document.createElement('div');
    this.node.widget = this;
    this.node.className='form-group row';
    if(this.label) {
      this.label.className = 'col-12 form-label';
      this.node.appendChild(this.label);
    }
    this.options = [];
    this.onAdd = null;
    this.onRemove = null;
    this.onChange = null;
    this.placeholder = '';
    this.inputdiv = document.createElement('div');
    this.inputdiv.className='form-group col-12';
    this.node.appendChild(this.inputdiv);
    this.setParent(parent);
    if((typeof data != 'undefined') && data ) this.setValue(data);
  }
  createAction(values) {
    var inputdiv = new widgets.collection(null, {value: this.options});
    inputdiv.addbtn.className = 'btn btn-secondary';
    inputdiv.setValue(values);
    inputdiv.onAdd = this.addEvent;
    inputdiv.onRemove = this.removeEvent;
    inputdiv.onChange = this.changeEvent;
    inputdiv.widget = this;
    inputdiv.label = document.createElement('input');
    inputdiv.label.placeholder = this.placeholder;
    inputdiv.label.className = 'form-control col';
    inputdiv.select.inputdiv.insertBefore(inputdiv.label, inputdiv.select.input);
    inputdiv.btn = new widgets.button(inputdiv.select.inputdiv, {id: 'btn', class: 'danger', icon: 'oi oi-minus'}, '');
    inputdiv.btn.entry = inputdiv;
    inputdiv.btn.onClick=this.removeAction;
    return inputdiv;
  }
  getAction(sender) {
    var actiondata = sender.widget.getValue();
    return actiondata;
  }
  removeAction(sender) {
    var parent = sender.entry.node.parentNode;
    parent.removeChild(sender.entry.node);
    return false;
  }
  newAction(sender) {
    var result = true;
    var entry = sender.entry.widget.createAction([]);
    entry.widget=sender.entry.widget;
    entry.setParent(sender.entry.widget.inputdiv);
    return result;
  }
  addEvent(sender, item) {
    if(sender.widget.onAdd) return sender.widget.onAdd(sender, item);
    return true;
  }
  removeEvent(sender, item) {
    if(sender.widget.onRemove) return sender.widget.onRemove(sender, item);
    return true;
  }
  changeEvent(sender) {
    if(sender.widget.onChange) return sender.widget.onChange(sender);
    return true;
  }
  setValue(avalue) {
    if(typeof avalue == 'string') {
      avalue = {value: {}};
    }
    if((typeof avalue.id == 'undefined')&&(this.inputdiv.id == '')) avalue.id='multiplecollections-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.id != 'undefined') {
      this.inputdiv.id=avalue.id;
      if(this.label) this.label.htmlFor=this.inputdiv.id;
    }
    if(typeof avalue.placeholder != 'undefined') {
      this.placeholder = avalue.placeholder;
    }
    if(typeof avalue.options != 'undefined') {
      this.options = avalue.options;
    }
    if(typeof avalue.value != 'undefined') {
      this.inputdiv.textContent='';
      let count = 0;
      for(var i in avalue.value) {
        if(typeof avalue.value[i] == 'string') {
          avalue.value[i]=[avalue.value[i]];
        }
        var entry=this.createAction(avalue.value[i]);
        entry.label.value = i;
        entry.widget=this;
        entry.setParent(this.inputdiv);
        count++;
      }
      if(count==0) {
        var entry=this.createAction([]);
        entry.widget=this;
        entry.setParent(this.inputdiv);
      }
      this.inputdiv.childNodes[0].widget.btn.setValue({onClick: this.newAction, class: 'success', icon: 'oi oi-plus'});
    }
    return true;
  }
  disable() {
    var nodes = this.node.querySelectorAll('input');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=true;
    }
    nodes = this.node.querySelectorAll('select');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=true;
    }
    nodes = this.node.querySelectorAll('button');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=true;
    }
    return true;
  }
  disabled() {
    return this.inputdiv.btn.disabled;
  }
  enable() {
    var nodes = this.node.querySelectorAll('input');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=false;
    }
    nodes = this.node.querySelectorAll('select');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=false;
    }
    nodes = this.node.querySelectorAll('button');
    for(var i=0; i<nodes.length; i++) {
      nodes[i].disabled=false;
    }
    return true;
  }
  getID() {
    return this.inputdiv.id;
  }
  getValue() {
    var result={};
    for(var i=0; i<this.inputdiv.childNodes.length; i++) {
      if(this.inputdiv.childNodes[i].widget.label.value) result[this.inputdiv.childNodes[i].widget.label.value] = this.getAction(this.inputdiv.childNodes[i]);
    }
    return result;
  }
  resize() {
    for(var i=0; i<this.inputdiv.childNodes.length; i++) {
      this.inputdiv.childNodes[i].widget.resize();
    }
    return true;
  }
}

widgets.chart=class chartWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.node = document.createElement('div');
    this.node.widget = this;
    this.node.className='form-group row';
    if(this.label) this.node.appendChild(this.label);
    this.inputdiv = document.createElement('div');
    this.inputdiv.widget = this;
    this.inputdiv.className='row col-12';
    this.input = document.createElement('canvas');
    this.input.widget=this;
    let chartdata = {type: 'bar', options: {responsive: true, aspectRatio: 2.3, legend: {position: 'bottom', labels: {boxWidth: 12}}, plugins: {colorschemes: {scheme: 'tableau.Tableau20'}}}};
    if((typeof data != 'undefined') && data ) {
      if(typeof data.type != 'undefined') {
        switch(data.type) {
          case "line": {
            chartdata.type = data.type
          } break;
          case "radar": {
            chartdata.type = data.type
          } break;
          case "polarArea": {
            chartdata.type = data.type
          } break;
          case "bubble": {
            chartdata.type = data.type
          } break;
          case "scatter": {
            chartdata.type = data.type
          } break;
          case "area": {
            chartdata.type = data.type
          } break;
          case "pie": {
            chartdata.type = data.type
          } break;
          case "doughnut": {
            chartdata.type = data.type
            chartdata.options.cutoutPercentage = 70;
          } break;
        }
      }
    }
    this.chart = new Chart(this.input, chartdata);
    this.chart.widget = this;
    this.chart.options.onClick = function(obj, data) {
      if(typeof data[0] == 'undefined') return;
      let widget = data[0]._chart.widget;
      if(widget.onClick) widget.onClick(widget, data[0]._datasetIndex, data[0]._index);
    }
    this.chart.options.legend.onClick = function(obj, data) {
      let widget = obj.currentTarget.widget;
      if(widget.onLegendClick) widget.onLegendClick(widget, data.index);
    }
    let widget = this;
    this.chart.options.legend.labels.generateLabels = function(chart) {
      var data = chart.data;
      if(data.labels.length && data.datasets.length) {
        let theHelp = Chart.helpers;
        return data.labels.map(function(label, i) {
          let meta = chart.getDatasetMeta(0);
          let arc = meta.data[i];
          let custom = arc && arc.custom || {};
          let getValueAtIndexOrDefault = theHelp.getValueAtIndexOrDefault;
          let arcOpts = chart.options.elements.arc;
          let fill = custom.backgroundColor ? custom.backgroundColor : getValueAtIndexOrDefault(data.datasets[0].backgroundColor[i], i, arcOpts.backgroundColor);
          let stroke = custom.borderColor ? custom.borderColor : getValueAtIndexOrDefault('white', i, arcOpts.borderColor);
          let bw = custom.borderWidth ? custom.borderWidth : getValueAtIndexOrDefault(3, i, arcOpts.borderWidth);
          let newText = label;
          let hidden = false;
          for(let j in data.datasets) {
            hidden |= isNaN(data.datasets[j].data[i]) || meta.data[i].hidden;
          } 
          if(widget.onLegendText) newText = widget.onLegendText(widget, label, i);
          return {
            text: newText,
            fillStyle: fill,
            strokeStyle: stroke,
            lineWidth: bw,
            hidden: hidden,
            index: i
          };
        });
      }
      return [];
    }
    this.chart.options.tooltips.callbacks.label = function(obj, data) {
      if(widget.onHintLabel) return widget.onHintLabel(widget, obj.datasetIndex, obj.index, data);
      else return data.labels[obj.index]+": "+data.datasets[obj.datasetIndex].data[obj.index];
    }
    this.chart.options.tooltips.callbacks.title = function(obj, data) {
      if(!isNaN(parseInt(data.datasets[obj[0].datasetIndex].label))) return null;
      else return data.datasets[obj[0].datasetIndex].label;
    }
    this.onClick=null;
    this.onLegendClick=null;
    this.onHintLabel=null;
    this.inputdiv.appendChild(this.input);
    this.node.appendChild(this.inputdiv);
    this.setParent(parent);
    if((typeof data != 'undefined') && data ) {
      this.setValue(data);
    }
  }
  selectData(dataset, index) {
    if(typeof this.chart.data.datasets[dataset].selected == 'undefined') this.chart.data.datasets[dataset].selected = [];
    this.chart.data.datasets[dataset].selected[index] = {color: this.chart.data.datasets[dataset]._meta[0].data[index]._model.backgroundColor, radius: this.chart.data.datasets[dataset]._meta[0].data[index]._model.outerRadius, lw: this.chart.legend.legendItems[index].lineWidth};
    let color=new Color(this.chart.data.datasets[dataset]._meta[0].data[index]._model.backgroundColor);
    color.lightness(color.lightness()*0.8);
    this.chart.data.datasets[dataset]._meta[0].data[index]._model.backgroundColor = color.rgbString();
    this.chart.data.datasets[dataset]._meta[0].data[index]._model.outerRadius += 10;
    this.chart.legend.legendItems[index].lineWidth = 0;
    this.chart.render(150);
  }
  unselectData(dataset, index) {
    if(typeof this.chart.data.datasets[dataset].selected == 'undefined') return;
    if(typeof this.chart.data.datasets[dataset].selected[index] != 'undefined') {
      this.chart.data.datasets[dataset]._meta[0].data[index]._model.backgroundColor = this.chart.data.datasets[dataset].selected[index].color;
      this.chart.data.datasets[dataset]._meta[0].data[index]._model.outerRadius = this.chart.data.datasets[dataset].selected[index].radius;
      this.chart.legend.legendItems[index].lineWidth = this.chart.data.datasets[dataset].selected[index].lw;
      delete this.chart.data.datasets[dataset].selected[index];
    }
    this.chart.render(150);
  }
  unselectAll() {
    for(let dataset in this.chart.data.datasets) {
      if(typeof this.chart.data.datasets[dataset].selected != 'undefined') delete this.chart.data.datasets[dataset].selected;
    }
    this.chart.update();
  }
  getSelected() {
    let selected = [];
    for(let dataset in this.chart.data.datasets) {
      if(typeof this.chart.data.datasets[dataset].selected != 'undefined') {
        for(let index in this.chart.data.datasets[dataset].selected) {
          selected.push({dataset: dataset, index: index});
        }
      }
    }
    return selected;
  }
  isSelected(dataset, index) {
    if(typeof this.chart.data.datasets[dataset].selected == 'undefined') return false;
    if(typeof this.chart.data.datasets[dataset].selected[index] != 'undefined') return true;
    return false;
  }
  setTitle(avalue) {
    if(avalue == "") {
      this.chart.titleBlock.options.display = false;
      this.chart.titleBlock.options.text = "";
    } else {
      this.chart.titleBlock.options.display = true;
      this.chart.titleBlock.options.text = avalue;
    }
    this.chart.update();
  }
  setValue(avalue) {
    if(typeof avalue == 'undefined') avalue = {};
    if(typeof avalue == 'string') {
      avalue = {value: avalue};
    } else if(typeof avalue == 'number') {
      avalue = {value: String(avalue)};
    }
    if((typeof avalue.id == 'undefined')&&(this.input.id == '')) avalue.id='chart-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(typeof avalue.pattern == 'undefined') avalue.pattern=null;
    let initindex = this.chart.data.labels.length;
    let initdsindex = this.chart.data.datasets.length;
    if((typeof avalue.clean != 'undefined')&&(avalue.clean)) {
      initindex = 0;
      initdsindex = 0;
    }
    if(typeof avalue.legend == 'object') {
      let index = initindex;
      for(let i in avalue.legend) {
        this.chart.data.labels[index++]=avalue.legend[i];
      }
      if(typeof avalue.value != 'object') {
        initindex = index;
      }
    }
    if(typeof avalue.value == 'object') {
      let dsindex = initdsindex;
      let index = initindex;
      for(let i in avalue.value) {
        if(avalue.value[i] instanceof Array) {
          if(typeof this.chart.data.datasets[dsindex] == 'undefined') this.chart.data.datasets[dsindex] = {};
          this.chart.data.datasets[dsindex].label = i;
          if(typeof this.chart.data.datasets[dsindex].data == 'undefined') this.chart.data.datasets[dsindex].data = [];
          index = initindex;
          for(let j in avalue.value[i]) {
            this.chart.data.datasets[dsindex].data[index++] = avalue.value[i][j];
          }
          dsindex++;
        } else {
          if(typeof this.chart.data.datasets[dsindex] == 'undefined') this.chart.data.datasets[dsindex] = {};
          if(typeof this.chart.data.datasets[dsindex].data == 'undefined') this.chart.data.datasets[dsindex].data = [];
          this.chart.data.datasets[dsindex].data[index++] = avalue.value[i];
        }
      }
      initdsindex = dsindex;
      initindex = index;
      this.unselectAll();
    }
    let len = this.chart.data.labels.length;
    for(let i=initindex; i < len; i++) {
      this.chart.data.labels.pop();
    }
    len = this.chart.data.datasets.length;
    for(let i=initdsindex; i < len; i++) {
      this.chart.data.datasets.pop();
    }
    len = this.chart.data.datasets.length;
    for(let i=0; i < len; i++) {
      let dslen = this.chart.data.datasets[i].data.length;
      for(let j=initindex; j < dslen; j++) {
        this.chart.data.datasets[i].data.pop();
      }
    }
    if(typeof avalue.id != 'undefined') {
      this.input.id=avalue.id;
      if(this.label) this.label.htmlFor=this.input.id;
    }
    return true;
  }
  disable() {
    this.input.disabled=true;
    return true;
  }
  disabled() {
    return this.input.disabled;
  }
  enable() {
    this.input.disabled=false;
    return true;
  }
  getID() {
    return this.input.id;
  }
  getValue() {
    return this.chart.data;
  }
  resize() {
    this.chart.resize();
  }
}
