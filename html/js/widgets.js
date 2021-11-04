/**
 * Коллекция вьюпортов содержащая наследников класса #viewport
 * \var views
 * \type HashTable
*/
var views = {};

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

var maintheme = createMuiTheme({
  palette: {
    primary: {
      main: '#689f38',
    },
    secondary: {
      main: '#4e342e',
    },
  },
});

var darktheme = createMuiTheme({
  palette: {
    primary: {
      main: '#4e342e',
    },
    secondary: {
      main: '#689f38',
    },
  },
});

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
  if(!isSet(defval)) defval = id;
  return defval;
}

function cloneArray(array) {
  let items = [];
  for(i in array) {
    items.push(Object.assign({}, array[i]));
  }
  return items;
}

class Binder {}

Binder.getAllMethods = function(instance, cls) {
  return Object.getOwnPropertyNames(Object.getPrototypeOf(instance))  
    .filter(name => {
      let method = instance[name];
      return !(!(method instanceof Function) || method instanceof cls);
    });
}

Binder.bind = function(instance, cls) {
  Binder.getAllMethods(instance, cls)
    .forEach(mtd => {
      instance[mtd] = instance[mtd].bind(instance);
    })
}

const {
  Drawer,
  Box,
  AppBar,
  Toolbar,
  List,
  ListItem,
  ListItemIcon,
  ListItemText,
  ListItemAvatar,
  ListItemSecondaryAction,
  ListSubheader,
  Container,
  ResponsiveContainer,
  Paper,
  Link,
  Grid,
  Button,
  ButtonGroup,
  IconButton,
  ToggleButton,
  ToggleButtonGroup,
  Badge,
  Divider,
  Collapse,
  Avatar,
  Table,
  TableHead,
  TableRow,
  TableCell,
  TableBody,
  TableContainer,
  TablePagination,
  TableSortLabel,
  Tooltip,
  Chip,
  Popover,
  Skeleton,
  Zoom,
  Fade,
  TransitionRight,
  TransitionLeft,
  TransitionUp,
  TransitionDown,
  Fab,
  Card,
  CardHeader,
  CardContent,
  Tab,
  Tabs,
  TabPanel,
  SwipeableViews,
  Dialog,
  DialogTitle,
  DialogActions,
  DialogContent,
  TextField,
  InputLabel,
  FormControl,
  FormControlLabel,
  FormHelperText,
  MenuItem,
  ListSubHeader,
  Select,
  InputBase,
  InputAdornment,
  Input,
  Checkbox,
  Switch,
  Radio,
  Hidden,
  Slide,
  Alert,
  AlertTitle,
  Snackbar,
  Autocomplete,
} = MaterialUI;

const {
  MuiPickersUtilsProvider,
  KeyboardDatePicker,
  KeyboardDateTimePicker,
  KeyboardTimePicker,
  Calendar,
  ClockView,
} = MaterialUIPickers;

var viewMode = 'basic';

function setViewMode(mode) {
  switch(mode) {
    case 'basic':
    case 'advanced':
    case 'expert': break;
    default: mode = 'basic';
  }
  sendRequest('setviewmode', {mode: mode}, 'rest/core').success(function() { return false;});
  viewMode = mode;
  appbar.render();
  appbar.mainmenu.render();
  let collectViewports = (objects) => {
    let result = [];
    for(let i in objects) {
      if(isSet(objects[i].view)) result.push(objects[i].view);
      if(isSet(objects[i].children)&&(objects[i].children.length>0)) result = result.concat(collectViewports(objects[i].children));
    }
    return result;
  }
  let viewports = collectViewports([rootcontent, dialogcontent]);
  viewports.forEach(view => {
    if(isSet(view.init)&&(view.init.done)) view.setMode(mode);
  }); 
}

class viewport {

  static defaultapi = null;

  constructor(parent, data) {
    Binder.bind(this, viewport);
    this.init.done = false;
    this.onUpdate = null; //Если успешно отправили данные на сервер (добавление, изменение, удаление)
    this.onRefreshActions = null; //Если нужно обновить состояние кнопок Сохранить/Удалить
    this.hasSave = false;
    this.hasAdd = false;
    this.showSave = true;
    this.showReset = true;
    this.showAdd = true;
    this.data = {};
    this.currentItems = [];
    this.parent = parent;
    this.send = this.send.bind(this);
    this.reset = this.reset.bind(this);
    if(parent instanceof baseWidget) {
      parent.clear();
      parent.view = this;
    }
  }

  static async create(parent, data) {
    let o = new this(parent, data);
    parent.renderLock();
    await o.init(parent, data);
    o.setValue(data);
    o.setMode(viewMode);
    parent.renderUnlock();
    o.init.done = true;
    parent.redraw();
    await o.preload();
    parent.endload();
    return o;
  }

  setValue(data) {
    this.data = data;
    if(this.parent instanceof baseWidget) {
      this.parent.setValue(data);
    }
  }

  getValue() {
    if(this.parent instanceof baseWidget) {
      return this.parent.getValue();
    } else {
      return null;
    }
  }

  setMode(mode) {
  }

  sendRequest(action, data, uri) {
    if(!isSet(uri)) uri = this.__proto__.constructor.defaultapi;
    return sendRequest(action, data, uri);
  }

  asyncRequest(action, data, uri) {
    if(!isSet(uri)) uri = this.__proto__.constructor.defaultapi;
    return asyncRequest(action, data, uri);
  }

  async add() {
    this.showAdd = false;
    this.parent.renderLock();
    this.reset();
    this.parent.renderUnlock();
    if(this.onRefreshActions) this.onRefreshActions();
    if(this.showSave && this.hasSave) {
      this.parent.setApply(this.send);
    } else {
      this.parent.setApply(null);
    }
    return false;
  }

  async reset() {
    return this.parent.reset();
  }

  /**
   * Если передан идентификатор и есть обработчик onDelete - вызываем обработчик. Иначе сразу удаляем субъект
   */
  async remove(id, title) {
    if((isSet(this.data.id))||(isSet(id))) {
      if(!isSet(id)) {
        id = this.data.id;
      }
      if(id) {
        let data = await this.asyncRequest('remove', {id: id})
        this.data.old_id = id;
        this.data.id = null;
        if(this.onUpdate != null) return this.onUpdate(this, 'remove', this.data);
        if(this.onRefreshActions) this.onRefreshActions();
        return data;
      }
    } else {
      return this.reset();
    }
  }

  async clear() {

  }

  async send() {
    let data = this.getValue();
    let action = 'set';
    if(isSet(this.data.id)&&((this.data.id=='')||(this.data.id==0)||(this.data.id==false))) action = 'add';
    data = await this.asyncRequest(action, data);
    if((isSet(data)) && data && (isSet(data.id))) this.data.id = data.id;
    this.showAdd = true;
    if(this.onRefreshActions) this.onRefreshActions();
    if(this.onUpdate != null) return this.onUpdate(this, action, data);
    return data;
  }

  async load(id) {
    this.parent.preload();
    let data = null;
    if(isSet(id)) data = {id: id};
    data = await this.asyncRequest('get', data);
    this.parent.renderLock();
    if((isSet(data.readonly))&&data.readonly) {
      this.parent.disable();
      this.showSave = false;
      this.showRemove = false;
      if(this.onRefreshActions) this.onRefreshActions();
    } else {
      this.parent.enable();
      this.showSave = true;
      this.showRemove = true;
      if(this.onRefreshActions) this.onRefreshActions();
    }
    this.setValue(data);
    this.parent.show();
    if(this.showSave && this.hasSave) {
      this.parent.setApply(this.send);
    } else {
      this.parent.setApply(null);
    }
    if(this.showReset && this.onReset) {
      this.parent.setReset(this.reset);
    } else {
      this.parent.setReset(null);
    }
    if(this.showAdd && this.hasAdd) {
      this.parent.setAppend(this.add);
    } else {
      this.parent.setAppend(null);
    }
    this.parent.renderUnlock();
    this.parent.endload();
    this.parent.redraw();
  }

  async preload() {
    this.parent.preload();
  }

  async getItems(id) {
    let data = null;
    if(isSet(id)) {
      data = await this.asyncRequest('menuItems', {id: id});
    } else {
      data = await this.asyncRequest('menuItems', {});
    }
    // let newid = (isSet(this.data.id))?this.data.id:null;
    // let found = false;
    // for(let i in data) {
    //   if(data[i].id ==  newid) {
    //     data[i].current = true;
    //     found = true;
    //     break;
    //   }
    // }
    this.currentItems = data;
    return data;
  }
  
}

class dummyviewport extends viewport {

  async init(parent) {    
    console.warn('Dummy viewport loaded!');
  }

}

function require(path, parent, data) {
  if(!isSet(data)) data = {};
  if(!isSet(views[path])) {
    let s = document.createElement('script');
    s.src = '/view/'+path.replace('\\','/');
    return Promise.resolve({then: function(resolve) {
      s.onload = function() {
        if(!isSet(views[path])) resolve(dummyviewport.create(parent, data));
        else if(!views[path] instanceof viewport) resolve(dummyviewport.create(parent, data));
        else {
          if((parent instanceof baseWidget) && (path!='collection')) {
            parent.setLabel(views[path].title);
          }
          resolve(views[path].create(parent, data));
        }
      }
      s.onerror = function() {
        resolve(dummyviewport.create(parent, data));
      }
      document.head.append(s);
    }});
  } else {
    if(!views[path] instanceof viewport) return Promise.resolve(dummyviewport.create(parent, data));
    else {
      if((parent instanceof baseWidget) && (path!='collection')) {
        parent.setLabel(views[path].title);
      }
      return Promise.resolve(views[path].create(parent, data));
    }
  }
}

class MomentUtils {

  constructor({locale, formats, instance} = option) {
    this.moment = instance || this.__proto__.defaultMoment;
    this.locale = locale;

    this.formats = Object.assign({}, this.__proto__.defaultFormats, formats);
  }

  is12HourCycleInCurrentLocale() {
    return /A|a/.test(this.moment().localeData().longDateFormat("LT"));
  };

  getFormatHelperText(format) {
    // @see https://github.com/moment/moment/blob/develop/src/lib/format/format.js#L6
    const localFormattingTokens = /(\[[^\[]*\])|(\\)?(LTS|LT|LL?L?L?|l{1,4})|./g;
    return format
      .match(localFormattingTokens)
      .map((token) => {
        const firstCharacter = token[0];
        if (firstCharacter === "L" || firstCharacter === ";") {
          return this.moment.localeData().longDateFormat(token);
        }

        return token;
      })
      .join("")
      .replace(/a/gi, "(a|p)m")
      .toLocaleLowerCase();
  };

  getCurrentLocaleCode() {
    return this.locale || this.moment.locale();
  };

  parse(value, format) {
    if (value === "") {
      return null;
    }

    if (this.locale) {
      return this.moment(value, format, this.locale, true);
    }

    return this.moment(value, format, true);
  };

  date(value) {
    if (value === null) {
      return null;
    }

    const moment = this.moment(value);
    moment.locale(this.locale);

    return moment;
  };

  toJsDate(value) {
    return value.toDate();
  };

  isValid(value) {
    return this.moment(value).isValid();
  };

  isNull(date) {
    return date === null;
  };

  getDiff(date, comparing, unit) {
    return date.diff(comparing, unit);
  };

  isAfter(date, value) {
    return date.isAfter(value);
  };

  isBefore(date, value) {
    return date.isBefore(value);
  };

  isAfterDay(date, value) {
    return date.isAfter(value, "day");
  };

  isBeforeDay(date, value) {
    return date.isBefore(value, "day");
  };

  isBeforeYear(date, value) {
    return date.isBefore(value, "year");
  };

  isAfterYear(date, value) {
    return date.isAfter(value, "year");
  };

  startOfDay(date) {
    return date.clone().startOf("day");
  };

  endOfDay(date) {
    return date.clone().endOf("day");
  };

  format(date, formatKey) {
    if(isSet(this.formats[formatKey])) {
      return this.formatByString(date, this.formats[formatKey]);
    } else {
      return date.format(formatKey);
    }
  };

  formatByString(date, formatString) {
    const clonedDate = date.clone();
    clonedDate.locale(this.locale);
    return clonedDate.format(formatString);
  };

  formatNumber(numberToFormat) {
    return numberToFormat;
  };

  getHours(date) {
    return date.get("hours");
  };

  getHourText(date) {
    return date.get("hours").zeroPad(10);
  };

  addSeconds(date, count) {
    return count < 0
      ? date.clone().subtract(Math.abs(count), "seconds")
      : date.clone().add(count, "seconds");
  };

  addMinutes(date, count) {
    return count < 0
      ? date.clone().subtract(Math.abs(count), "minutes")
      : date.clone().add(count, "minutes");
  };

  addHours(date, count) {
    return count < 0
      ? date.clone().subtract(Math.abs(count), "hours")
      : date.clone().add(count, "hours");
  };

  addDays(date, count) {
    return count < 0
      ? date.clone().subtract(Math.abs(count), "days")
      : date.clone().add(count, "days");
  };

  addWeeks(date, count) {
    return count < 0
      ? date.clone().subtract(Math.abs(count), "weeks")
      : date.clone().add(count, "weeks");
  };

  addMonths(date, count) {
    return count < 0
      ? date.clone().subtract(Math.abs(count), "months")
      : date.clone().add(count, "months");
  };

  setHours(date, count) {
    return date.clone().hours(count);
  };

  getMinutes(date) {
    return date.get("minutes");
  };

  getDayText(date) {
    return date.get("date").zeroPad(10);
  };

  getMinuteText(date) {
    return date.get("minutes").zeroPad(10);
  };

  setMinutes(date, count) {
    return date.clone().minutes(count);
  };

  getSeconds(date) {
    return date.get("seconds");
  };

  getSecondText(date) {
    return date.get("seconds").zeroPad(10);
  };

  setSeconds(date, count) {
    return date.clone().seconds(count);
  };

  getMonth(date) {
    return date.get("month");
  };

  getMonthText(date) {
    return date.get("month").zeroPad(10);
  };

  getDaysInMonth(date) {
    return date.daysInMonth();
  };

  isSameDay(date, comparing) {
    return date.isSame(comparing, "day");
  };

  isSameMonth(date, comparing) {
    return date.isSame(comparing, "month");
  };

  isSameYear(date, comparing) {
    return date.isSame(comparing, "year");
  };

  isSameHour(date, comparing) {
    return date.isSame(comparing, "hour");
  };

  setMonth(date, count) {
    return date.clone().month(count);
  };

  getMeridiemText(ampm) {
    if (this.is12HourCycleInCurrentLocale()) {
      // AM/PM translation only possible in those who have 12 hour cycle in locale.
      return this.moment.localeData().meridiem(ampm === "am" ? 0 : 13, 0, false);
    }

    return ampm === "am" ? "AM" : "PM"; // fallback for de, ru, ...etc
  };

  startOfMonth(date) {
    return date.clone().startOf("month");
  };

  endOfMonth(date) {
    return date.clone().endOf("month");
  };

  startOfWeek(date) {
    return date.clone().startOf("week");
  };

  endOfWeek(date) {
    return date.clone().endOf("week");
  };

  getNextMonth(date) {
    return date.clone().add(1, "month");
  };

  getPreviousMonth(date) {
    return date.clone().subtract(1, "month");
  };

  getMonthArray(date) {
    const firstMonth = date.clone().startOf("year");
    const monthArray = [firstMonth];

    while (monthArray.length < 12) {
      const prevMonth = monthArray[monthArray.length - 1];
      monthArray.push(this.getNextMonth(prevMonth));
    }

    return monthArray;
  };

  getYear(date) {
    return date.get("year");
  };

  getYearText(date) {
    return date.get("year").toString();
  };

  getDateTimePickerHeaderText(date) {
    return date.format('MMM DD');
  };

  getCalendarHeaderText(date) {
    return date.format('MMMM, YYYY');
  };

  setYear(date, year) {
    return date.clone().set("year", year);
  };

  mergeDateAndTime(date, time) {
    return date.hour(time.hour()).minute(time.minute()).second(time.second());
  };

  getWeekdays() {
    return this.moment.weekdaysShort(true);
  };

  isEqual(value, comparing) {
    if (value === null && comparing === null) {
      return true;
    }

    return this.moment(value).isSame(comparing);
  };

  getWeekArray(date) {
    const start = date.clone().startOf("month").startOf("week");
    const end = date.clone().endOf("month").endOf("week");

    let count = 0;
    let current = start;
    const nestedWeeks = [];

    while (current.isBefore(end)) {
      const weekNumber = Math.floor(count / 7);
      nestedWeeks[weekNumber] = nestedWeeks[weekNumber] || [];
      nestedWeeks[weekNumber].push(current);

      current = current.clone().add(1, "day");
      count += 1;
    }

    return nestedWeeks;
  };

  getYearRange(start, end) {
    const startDate = this.moment(start).startOf("year");
    const endDate = this.moment(end).endOf("year");
    const years = [];

    let current = startDate;
    while (current.isBefore(endDate)) {
      years.push(current);
      current = current.clone().add(1, "year");
    }

    return years;
  };

  isWithinRange(date, start, end) {
    return date.isBetween(start, end, null, "[]");
  };
}

MomentUtils.prototype.defaultMoment = moment;
MomentUtils.prototype.lib = "moment";
MomentUtils.prototype.locale = '';1
MomentUtils.prototype.formats = {};;

MomentUtils.prototype.defaultFormats = {
  normalDateWithWeekday: "ddd, MMM D",
  normalDate: "D MMMM",
  shortDate: "MMM D",
  monthAndDate: "MMMM D",
  dayOfMonth: "D",
  year: "YYYY",
  month: "MMMM",
  monthShort: "MMM",
  monthAndYear: "MMMM YYYY",
  weekday: "dddd",
  weekdayShort: "ddd",
  minutes: "mm",
  hours12h: "hh",
  hours24h: "HH",
  seconds: "ss",
  fullTime: "LT",
  fullTime12h: "hh:mm A",
  fullTime24h: "HH:mm",
  fullDate: "ll",
  fullDateWithWeekday: "dddd, LL",
  fullDateTime: "lll",
  fullDateTime12h: "ll hh:mm A",
  fullDateTime24h: "ll HH:mm",
  keyboardDate: "L",
  keyboardDateTime: "L LT",
  keyboardDateTime12h: "L hh:mm A",
  keyboardDateTime24h: "L HH:mm",
  ISO_8601: "YYYY-MM-DDTHH:mm:ss'",
  ISO_8601_Full: "YYYY-MM-DDTHH:mm:ss.SSSZ'",
};

const useMediaQuery = (mediaQuery) => {
  if(!isSet('matchMedia')) {
    // eslint-disable-next-line no-console
    console.warn(errorMessage);
    return null;
  }
  mediaQuery = mediaQuery.replace('@media ', '');

  const [isVerified, setIsVerified] = React.useState(!!window.matchMedia(mediaQuery).matches);

  React.useEffect(() => {
    const mediaQueryList = window.matchMedia(mediaQuery);
    const documentChangeHandler = () => {
      setIsVerified(!!mediaQueryList.matches);
    }

    mediaQueryList.onchange = documentChangeHandler;

    documentChangeHandler();

    return () => {
      mediaQueryList.onchange = null;
    };
  }, [mediaQuery]);

  return isVerified;
};

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
    Binder.bind(this, baseWidget);
    this.parent = parent;    
    this.createChipHint = this.createChipHint.bind(this);
    this.createSimpleHint = this.createSimpleHint.bind(this);
    this.prepare = this.prepare.bind(this);

    this.itemsalign = null;
    this.selfalign = null;
    this.hidesteps = null;
    this.drawingstate = false;
    this.setdrawingstate = null;

    this.id = null;

    this.locked = 0;
    this.loading = false;

    this.disabled = false;

    if(isSet(data) && data && isSet(data.id)) {
      this.id = data.id;
    } else {
      this.id=this.__proto__.constructor.name.replace('Widget', '')+'-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    }

    this.hidden = false;

    /**
    * \var node
    * Ссылка на основной DOM элемент виджета
    * \type Object
    */
    this.node = null; //widget DOM node

    this.container  = null;

    this.children = [];
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
     this.hinttext = null;

     if((isSet(label))&&(label!=null)) {
      this.labeltext = label;
      if(isSet(hint) && hint) {
        this.setHint(hint);
      }
      this.setLabel(label);
    }

    this.onResize = null;
    this.onReturn = null;
  }

  renderLock() {
    this.locked++;
  }

  renderUnlock() {
    if(this.locked) this.locked--;
  }

  createChipHint(props) {
    const [hintopen, setOpen] = React.useState(false);
    const [anchorel, setAnchor] = React.useState(null);
    const useStyles = makeStyles((theme) => ({
      hint: {
        pointerEvents: 'none',
        maxWidth: '70vw'
      },
      paper: {
        padding: theme.spacing(2),
        textAlign: 'justify'
      },
      chip: {
        width: '1.4em !important',
        height: '1.4em !important'
      },
      icon: {
        marginLeft: '8px !important',
        marginRight: '-8px !important'
      }
    }));
    const classes = useStyles();
    if(typeof props.children != 'string') {
      return React.createElement(React.Fragment, {key: this.getKey('chiproot')}, [' ',
        React.createElement(Chip, {
          key: this.getKey('chip'),
          theme: props.theme,
          className: classes.chip,
          color: 'secondary',
          size: 'small',
          onMouseEnter: (e) => { 
            setAnchor(e.currentTarget);
            setOpen(true);
          },
          onMouseLeave: (e) => { 
            setOpen(false);
          },
          icon: React.createElement(HelpIcon, {className: classes.icon}),
        }),
        React.createElement(Popover, {
          key: this.getKey('popover'),
          theme: props.theme,
          className: classes.hint,
          classes: {paper: classes.paper},
          open: hintopen,
          anchorEl: anchorel,
          anchorOrigin: {
            vertical: 'bottom',
            horizontal: 'center',
          },
          transformOrigin: {
            vertical: 'top',
            horizontal: 'center',
          }
        }, React.createElement(Typography, {theme: props.theme}, props.children))
      ]);
    } else {
      return React.createElement(React.Fragment, {key: this.getKey('chiproot')}, [' ',
        React.createElement(Chip, {
          key: this.getKey('chip'),
          theme: props.theme,
          className: classes.chip,
          color: 'secondary',
          size: 'small',
          onMouseEnter: (e) => { 
            setAnchor(e.currentTarget);
            setOpen(true);
          },
          onMouseLeave: (e) => { 
            setOpen(false);
          },
          icon: React.createElement(HelpIcon, {className: classes.icon}),
        }),
        React.createElement(Popover, {
          key: this.getKey('popover'),
          theme: props.theme,
          className: classes.hint,
          classes: {paper: classes.paper},
          open: hintopen,
          anchorEl: anchorel,
          anchorOrigin: {
            vertical: 'bottom',
            horizontal: 'center',
          },
          transformOrigin: {
            vertical: 'top',
            horizontal: 'center',
          }
        }, React.createElement(Typography, {theme: props.theme, dangerouslySetInnerHTML: {__html: props.children}}))
      ]);
    }
  }

  createSimpleHint(props) {
    return React.createElement(Tooltip, {
      key: this.getKey('tooltip'), 
      theme: props.theme,
      title: props.hint,
    }, props.children);
  }

  /**
  * \fn bool setHint(String ahint)
  * Устанавливает новый текст всплывающей подсказки, может принимать форматированный HTML текст в качестве значения.
  * \tparam String ahint Текст подсказки
  * \return Истину при успешной смене текста подсказки
  */
  setHint(ahint) {
    this.hinttext = ahint;
    if(this.hint) {
      this.redraw();
    }
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
    if(this.label) {
      this.redraw();
    }
    return true;
  }

  setReset(resetfunc) {
    return false;
  }
  
  setApply(applyfunc) {
    return false;
  }

  setAppend(appendfunc) {
    return false;
  }
  
  setElement(elementlabel) {
    return false;
  }
  
  /**
  * \fn bool setValue(String avalue)
  * Абстрактный метод для установки нового значения виджета.
  * \tparam String avalue Принимает текстовое значение, структуру или массив. Режим обработки зависит от виджета. Общий ключ <i>id</i> определяет идентификатор виджета.
  * \return Истину при успешной установке значения
  */
  setValue(avalue) {
    if(!isSet(avalue) || !avalue) return false;
    this.renderLock();
    for(let i in this.children) {
      let id = this.children[i].getID();
      this.children[i].renderLock();
      if(id=='' || (id.indexOf(this.children[i].__proto__.constructor.name.replace('Widget', '')+'-')==0)) {
        this.children[i].setValue(avalue);
      } else {
        if(isSet(avalue[id])) {
          if((this.children[i] instanceof widgets.section) || (this.children[i] instanceof widgets.dialog) || isSet(avalue[id].value)) {
            this.children[i].setValue(avalue[id]);
          } else {
            this.children[i].setValue({value: avalue[id]});
          }
        }
      }
      this.children[i].renderUnlock();
    }
    this.renderUnlock();
    this.loading = false;
    this.redraw();
    return true;
  }

  preload() {
    this.loading = true;
    this.renderLock();
    for(let i in this.children) {
      this.children[i].preload();
    }
    this.renderUnlock();
    this.redraw();
  }

  endload() {
    this.loading = false;
    this.renderLock();
    for(let i in this.children) {
      this.children[i].endload();
    }
    this.renderUnlock();
    this.redraw();
  }

  /**
  * \fn bool getValue()
  * Абстрактный метод для получения текущего значения виджета.
  * \return Текущее значение элемента управления
  */
  getValue() {
    let result = {};
    for(let i in this.children) {
      if(this.children[i].id.indexOf(this.children[i].__proto__.constructor.name.replace('Widget', '')+'-')==0) {
        result = _extends(result, this.children[i].getValue());
      } else {
        result[this.children[i].getID()] = this.children[i].getValue();
      }
    }
    return result;
  }

  /**
  * \fn bool disable()
  * Абстрактный метод для отключения виджета.
  * \return Истину при успешном отключении элемента управления
  */
  disable() {
    this.disabled = true;
    this.renderLock();
    for(let i in this.children) {
      this.children[i].disable();
    }
    this.renderUnlock();
    this.redraw();
    return true;
  }

  /**
  * \fn bool enable()
  * Абстрактный метод для включения виджета.
  * \return Истину при успешном включении элемента управления
  */
  enable() {
    this.disabled = false;
    this.renderLock();
    for(let i in this.children) {
      this.children[i].enable();
    }
    this.renderUnlock()
    this.redraw();
    return true;
  }

  /**
  * \fn bool show()
  * Метод для сокрытия элмента
  * \return Истину при успешном скрытии элемента
  */
  hide() {
    this.hidden = true;
    this.render();
  }

  /**
  * \fn bool show()
  * Метод для показа элемента
  * \return Истину при успешном показе элемента
  */
  show() {
    this.hidden = false;
    this.render();
  }

  /**
  * \fn bool resize()
  * Команда для масштабирования элемента
  * \return Истину при успешном масштабировании элемента
  */
  resize() {
    if(this.onResize) this.onResize(this);
    return true;
  }

  /**
  * \fn bool getID()
  * Абстрактный метод для получения текущего значения виджета.
  * \return Текущее значение элемента управления
  */
  getID() {
    return this.id;
  }

  getKey(item, id) {
    if((isSet(id))&&id) {
      return this.id+'_'+item+'_'+id;
    } else {
      return this.id+'_'+item;
    }
  }

  redraw() {
    if(this.locked>0) return;
    if((this.parent instanceof baseWidget) && (this.parent.locked>0)) return;
    if(this.setdrawingstate) this.setdrawingstate(!this.drawingstate); else this.render();
  }

  render() {
    if(this.parent == null) return;
    if(!((this.node instanceof Node)||(this.locked>0))) {
      if(this.parent instanceof baseWidget) {
        // if(this.parent.container && this.parent.container.current) {
        //   let childNodes = [];
        //   for(let i in this.parent.children) {
        //     if((isSet(this.parent.children[i].node))&&this.parent.children[i].node) childNodes.push(this.parent.children[i].node);
        //   }
        //   ReactDOM.render(React.createElement(React.Fragment, {key: this.parent.getKey('container')}, childNodes), this.parent.container.current);
        // } else {
        //   this.parent.render();
        // }
        let locked = false;
        let parent = this;
        while(parent.parent instanceof baseWidget) {
          if(parent.parent.locked>0) {
            locked = true;
            break;
          }
          parent = parent.parent;
        }
        if(!locked) {
          parent.redraw();
        }
      } else {
        if(this.hidden) return;
        if(isSet(this.timeout)) {
          clearTimeout(this.timeout);
        }
        this.timeout = setTimeout(() => {
          if((this.parent instanceof baseWidget)&&(this.parent.itemsalign!==null)) {
            if(this.selfalign !== null) {
              this.node = React.createElement(Grid, _extends({key: this.getID(), item: true}, this.selfalign), React.createElement(this.prepare, {theme: colorscheme}));
            } else {
              this.node = React.createElement(Grid, _extends({key: this.getID(), item: true}, this.parent.itemsalign), React.createElement(this.prepare, {theme: colorscheme}));
            }
          } else {
            this.node = React.createElement(this.prepare, {key: this.getID(), flexGrow: 1, theme: colorscheme});
          }
          if(this.hidesteps) this.node = React.createElement(Hidden, _extends({key: this.getKey('hidden'), theme: props.theme}, this.hidesteps), this.node);
          ReactDOM.render(this.node, this.parent);
          this.timeout = null;
        }, 100);
      }
    }
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    let childNodes = [];
    for(let i in this.children) {
      if(!this.children[i].hidden) {
        childNodes.push(React.createElement(this.children[i].prepare, {theme: props.theme, key: this.children.getID()}));
      }
    }
    return React.createElement(React.Fragment, {key: this.getKey('container')}, childNodes);
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
    this.parent = aobject;
    if(this.parent instanceof baseWidget) {
      this.parent.children.push(this);
      this.parent.redraw();
    } else {
      this.render();
    }
  }

  clear() {
    this.renderLock();
    for(let i in this.children) {
      this.children[i].clear();
    }
    this.renderUnlock();
    this.children = [];
    this.redraw();
  }

  reset() {
    this.renderLock();
    if(isSet(this.defaultvalue)) {
      this.setValue({value: false});
    }
    for(let i in this.children) {
      this.children[i].reset();
    }
    this.renderUnlock();
    this.redraw();
    return true;
  }
}

widgets.appbar = class appbarWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent, data, label, hint);
    this.basestyle = {
      grow: {
        flexGrow: 1,
      },
      appBar: {
        zIndex: maintheme.zIndex.drawer + 1 + ' !important',
        transition: maintheme.transitions.create('all', {
          easing: maintheme.transitions.easing.sharp,
          duration: maintheme.transitions.duration.leavingScreen,
        })+' !important',
      },
      menuButton: {
        marginRight: maintheme.spacing(2),
      },
      menurow: {
        display: 'flex',
        flexDirection: 'row',
      },
      largeonly: {
        [maintheme.breakpoints.down('md')]: {
          display: 'none',
        },
      },
      smallonly: {
        [maintheme.breakpoints.up('md')]: {
          display: 'none',
        },
      }
    };
    this.selected = [];
    this.onReturn = null;
    this.elementlabel = null;
    this.appendfunc = null;
    this.applyfunc = null;
    this.resetfunc = null;
    this.mainmenu = null;
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const useStyles = makeStyles(this.basestyle);
    const classes = useStyles();
    if(this.hinttext) {
      this.hint = React.createElement(this.createChipHint, {theme: props.theme, key: this.getKey('hint')}, this.hinttext);
      this.label = React.createElement(Typography, {key: this.getKey('label'), className: classes.largeonly, variant: 'h6', noWrap: true}, [(this.elementlabel?(this.labeltext+': '+this.elementlabel):this.labeltext), this.hint]);
    } else {
      this.label = React.createElement(Typography, {key: this.getKey('label'), className: classes.largeonly, variant: 'h6', noWrap: true}, [(this.elementlabel?(this.labeltext+': '+this.elementlabel):this.labeltext)]);
    }
    this.mlabel = React.createElement(Typography, {key: this.getKey('mlabel'), className: classes.smallonly, variant: 'h6', noWrap: true}, [(this.elementlabel?this.elementlabel:this.labeltext)]);
    this.growbox = React.createElement(Box, {key: this.getKey('growbox'), className: classes.grow})
    if(this.elementlabel) {
      this.menubutton = React.createElement(IconButton, {key: this.getKey('menubtn'), className: classes.menuButton, edge: 'start', color: 'inherit', 'aria-label': 'return', onClick: this.returnClick}, React.createElement(ArrowBackIcon));  
    } else {
      this.menubutton = React.createElement(IconButton, {key: this.getKey('menubtn'), className: classes.menuButton, edge: 'start', color: 'inherit', 'aria-label': 'menu', onClick: this.menuClick}, React.createElement(MenuIcon));  
    }
    this.fullscreen = React.createElement(Tooltip, {key: this.getKey('fullscreen'), theme: props.theme, arrow: true, title: (document.fullscreenElement?_('Свернуть в оконный режим'):_('Развернуть на весь экран')), 'aria-label': 'fullscreen'}, React.createElement(IconButton, {color: 'inherit', 'aria-label': 'fullscreen', onClick: this.fullscreenClick}, (document.fullscreenElement?React.createElement(FullscreenExitIcon):React.createElement(FullscreenIcon))));
    this.viewmode = React.createElement(Tooltip, {key: this.getKey('viewmode'), theme: props.theme, arrow: true, title: (viewMode=='advanced'?_('Базовое представление'):_('Расширенное представление')), 'aria-label': 'viewmode'}, React.createElement(IconButton, {color: 'inherit', 'aria-label': 'viewmode', onClick: this.viewmodeClick}, (viewMode=='advanced'?React.createElement(VisibilityOffIcon):React.createElement(VisibilityIcon))));
    this.settings = React.createElement(Tooltip, {key: this.getKey('settings'), theme: props.theme, arrow: true, title: ((urilocation.split('/')[0]=='settings')?_('Управление'):_('Настройки')), 'aria-label': 'settings'}, React.createElement(IconButton, {color: 'inherit', 'aria-label': 'settings', onClick: this.settingsClick}, ((urilocation.split('/')[0]=='settings')?React.createElement(WebIcon):React.createElement(SettingsIcon))));
    this.container = React.createRef();
    if(viewMode != 'expert') {
      if(document.fullscreenEnabled) {
        this.menubox = React.createElement(Box, {key: this.getKey('box'), ref: this.container, className: classes.menurow}, React.createElement(React.Fragment, {key: this.getKey('container')}, [this.fullscreen, this.viewmode, this.settings]));
      } else {
        this.menubox = React.createElement(Box, {key: this.getKey('box'), ref: this.container, className: classes.menurow}, React.createElement(React.Fragment, {key: this.getKey('container')}, [this.viewmode, this.settings]));
      }
    } else {
      if(document.fullscreenEnabled) {
        this.menubox = React.createElement(Box, {key: this.getKey('box'), ref: this.container, className: classes.menurow}, React.createElement(React.Fragment, {key: this.getKey('container')}, [this.fullscreen, this.settings]));
      } else {
        this.menubox = React.createElement(Box, {key: this.getKey('box'), ref: this.container, className: classes.menurow}, React.createElement(React.Fragment, {key: this.getKey('container')}, [this.settings]));
      }
    }
    this.addfab = React.createElement(Tooltip, {key: this.getKey('addfab'), theme: props.theme, arrow: true, title: _('Добавить'), 'aria-label': 'add'}, React.createElement(Zoom, {in: ((this.appendfunc!==null)&&(this.applyfunc===null))}, React.createElement(Fab, {theme: props.theme, color: 'secondary', style: {
      position: 'fixed',
      zIndex: 100,
      bottom: maintheme.spacing(2),
      right: maintheme.spacing(2),
    }, onClick: this.appendfunc}, React.createElement(AddIcon))));
    this.applyfab = React.createElement(Tooltip, {key: this.getKey('applyfab'), theme: props.theme, arrow: true, title: _('Применить'), 'aria-label': 'apply'}, React.createElement(Zoom, {in: (this.applyfunc!==null)}, React.createElement(Fab, {theme: props.theme, color: 'secondary', style: {
      position: 'fixed',
      zIndex: 100,
      bottom: maintheme.spacing(2),
      right: maintheme.spacing(2),
    }, onClick: this.applyfunc}, React.createElement(DoneIcon))));
    this.resetfab = React.createElement(Tooltip, {key: this.getKey('resetfab'), theme: props.theme, arrow: true, title: _('Сбросить'), 'aria-label': 'reset'}, React.createElement(Zoom, {in: (((this.applyfunc!==null)||(this.appendfunc!==null))&&(this.resetfunc!==null))}, React.createElement(Fab, {theme: props.theme, color: 'secondary', style: {
      position: 'fixed',
      zIndex: 100,
      bottom: maintheme.spacing(2),
      right: maintheme.spacing(10),
      'backgroundColor': maintheme.palette.error.dark,
    }, onClick: this.resetfunc}, React.createElement(RotateLeftIcon))));
    this.toolbar = React.createElement(Toolbar, {}, [this.menubutton, this.label, this.mlabel, this.growbox, this.menubox]);
    return React.createElement(React.Fragment, {key: this.getKey('root')}, [React.createElement(AppBar, {key: this.getKey('appbar'), className: classes.appBar, theme: props.theme, position: 'fixed'}, this.toolbar), this.addfab, this.applyfab, this.resetfab]);
  }

  menuClick(event) {
    this.mainmenu.setValue({minimized: !this.mainmenu.minimized, closed: !this.mainmenu.closed});
  }

  returnClick(event) {
    if(this.onReturn) this.onReturn(this);
  }

  async fullscreenClick(event) {
    if(document.fullscreenElement) {
      await document.exitFullscreen();  
    } else {
      await document.body.requestFullscreen();
    }
    this.render();
  }

  viewmodeClick(event) {
    if(viewMode=='advanced') {
      setViewMode('basic');
    } else {
      setViewMode('advanced');
    }
  }

  settingsClick(event) {
    if((urilocation.split('/')[0]=='settings')) {
      setLocation('');
    } else {
      setLocation('settings');
    }
  }

  setLabel(avalue) {
    document.querySelector('title').innerText = avalue;
    this.elementlabel = null;
    return super.setLabel(avalue);
  }

  setReset(resetfunc) {
    this.resetfunc = resetfunc;
    this.redraw();
    return true;
  }
  
  setApply(applyfunc) {
    this.applyfunc = applyfunc;
    this.redraw();
    return true;
  }

  setAppend(appendfunc) {
    this.appendfunc = appendfunc;
    this.redraw();
    return true;
  }
  
  setElement(elementlabel) {
    this.elementlabel = elementlabel;
    this.redraw();
    return true;
  }

}

widgets.mainmenu = class mainmenuWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent, data, label, hint);
    let drawerWidth = 300;
    this.basestyle = {
      icontext: {
        fontFamily: 'monospace',
        fontSize: '0.85rem'
      },
      drawer: {
        width: drawerWidth,
        flexShrink: 0,
        whiteSpace: 'nowrap',
      },
      drawerMinimizedOpen: {
        transition: maintheme.transitions.create('width', {
          easing: maintheme.transitions.easing.sharp,
          duration: maintheme.transitions.duration.leavingScreen,
        })+' !important',
        overflowX: 'hidden',
        width: maintheme.spacing(9),
        [maintheme.breakpoints.down('md')]: {
          width: drawerWidth,
          position: 'absolute',
          left: 0,
          top: 0,
          height: '100%',
          overflowX: 'hidden',
        },
        [maintheme.breakpoints.down('sm')]: {
          width: '100%',
        },
      },
      drawerMinimizedClose: {
        transition: maintheme.transitions.create('width', {
          easing: maintheme.transitions.easing.sharp,
          duration: maintheme.transitions.duration.leavingScreen,
        })+' !important',
        overflowX: 'hidden',
        width: maintheme.spacing(9),
        [maintheme.breakpoints.down('md')]: {
          width: drawerWidth,
          display: 'none',
        },
      },      
      drawerOpen: {
        width: drawerWidth,
        transition: maintheme.transitions.create('width', {
          easing: maintheme.transitions.easing.sharp,
          duration: maintheme.transitions.duration.enteringScreen,
        })+' !important',
        [maintheme.breakpoints.down('md')]: {
          position: 'absolute',
          left: 0,
          top: 0,
          height: '100%',
          overflowX: 'hidden',
        },
        [maintheme.breakpoints.down('sm')]: {
          width: '100%',
        },
      },
      drawerClose: {
        width: drawerWidth,
        transition: maintheme.transitions.create('width', {
          easing: maintheme.transitions.easing.sharp,
          duration: maintheme.transitions.duration.enteringScreen,
        })+' !important',
        [maintheme.breakpoints.down('md')]: {
          display: 'none',
        },
      },      
      nested: {
        paddingLeft: maintheme.spacing(4) + ' !important',
      },
      arrow: {
        position: 'absolute',
        right: 0,
      },
      largeonly: {
        [maintheme.breakpoints.down('md')]: {
          display: 'none !important',
        },
      }
    };
    this.minimized = localStorage.getItem('menu_minimized')=='true';
    this.closed = true;
    this.items = [];
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const useStyles = makeStyles(this.basestyle);
    const classes = useStyles();
    if(this.hint) {
      this.label = React.createElement(Typography, {key: this.getKey('label'), variant: 'h6', noWrap: true}, [`${this.labeltext}`, this.hint]);
    } else {
      this.label = React.createElement(Typography, {key: this.getKey('label'), variant: 'h6', noWrap: true}, [`${this.labeltext}`]);
    }
    this.header = React.createElement(Box, {key: this.getKey('header')}, this.label);
    let currentscope = urilocation.split('/');

    let processitems = (items) => {
      let listitems = [];
      let hasActive = false;
      for(let i in items) {
        if((items[i].mode=='advanced') && (viewMode == 'basic')) continue;
        if((items[i].mode=='expert') && (viewMode != 'expert')) continue;
        let canShow = false;
        let subitems = [];
        let isActive = false;
        if(!items[i].link) {
          if(!isSet(items[i].open)) items[i].open = false;
          if(!isSet(items[i].id)) items[i].id = Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
          [subitems, isActive] = processitems(items[i].value, true);
          if(subitems.length) canShow = true;
        } else {
          if(items[i].link.substr(0,1) == '/') items[i].link = items[i].link.substr(1);
          if(!isSet(items[i].id)) items[i].id = items[i].link;
          isActive = items[i].link == urilocation;
          canShow = items[i].link.indexOf(currentscope[0])===0;
        }
        if(canShow) {
          if(isActive) hasActive = true;
          let icon = null;
          if((!isSet(items[i].icon)) || (!isSet(window[items[i].icon]))) {
            icon = React.createElement(createSvgIcon(
              React.createElement("text", {x: 0, y: 18, className: classes.icontext}, [items[i].title.substr(0,3).toUpperCase()])
            , ('MainMenuItem'+items[i].title.substr(0,3).toUpperCase()) ));
          } else {
            icon = React.createElement(window[items[i].icon]);
          }
          let itemicon = React.createElement(ListItemIcon, {key: this.getKey('itemicon', items[i].id), theme: props.theme}, icon);
          let itemtext = React.createElement(ListItemText, {key: this.getKey('itemtext', items[i].id), theme: props.theme, primary: items[i].title});
          if(subitems.length) {
            let expand = null;
            if(isActive) items[i].open = true;
            if(items[i].open) {
              expand = React.createElement(ExpandLessIcon, {key: this.getKey('itemexpand', items[i].id), className: classes.arrow});
            } else {
              expand = React.createElement(ExpandMoreIcon, {key: this.getKey('itemexpand', items[i].id), className: classes.arrow});
            }
            listitems.push(React.createElement(ListItem, {key: this.getKey('item', items[i].id), theme: props.theme, button: true, selected: isActive, key: items[i].id, onClick: (e) => {this.itemClick(e, items[i])}}, [itemicon, itemtext, expand]));
          } else {
            listitems.push(React.createElement(ListItem, {key: this.getKey('item', items[i].id), theme: props.theme, button: true, selected: isActive, key: items[i].id, onClick: (e) => {this.itemClick(e, items[i])}}, [itemicon, itemtext]));
          }
        }
        if(subitems.length>0) {
          let list = React.createElement(List, {theme: props.theme, component: "div", className: classes.nested, disabledPadding: true}, [subitems]);
          listitems.push(React.createElement(Collapse, {key: this.getKey('subitems', items[i].id), in: items[i].open, timeout: 'auto', unmountOnExit: true}, list));
        }

      }
      return [listitems, hasActive];
    }
    let [listitems, hasActive] = processitems(this.items);
    if(this.items.length == 0) {
      for(let i=0; i<10; i++) {
        listitems.push(React.createElement(ListItem, {key: this.getKey('item', i), theme: props.theme, button: true}, [React.createElement(ListItemIcon, {key: this.getKey('itemicon', i)}, React.createElement(Skeleton, {variant: 'circle', width: 24, height: 24})), React.createElement(ListItemText, {key: this.getKey('itemtext', i)}, React.createElement(Skeleton, {variant: 'text'})) ]));
      }
    }

    if(this.minimized) {
      listitems.push(React.createElement(Divider, {key: this.getKey('divider')}));
      listitems.push(React.createElement(ListItem, {key: this.getKey('exit'), theme: props.theme, className: classes.largeonly, button: true, onClick: this.logoutClick}, [
        React.createElement(Tooltip, {key: this.getKey('exithint'), theme: props.theme, arrow: true, title: _('Выход'), 'aria-label': 'Logout'}, 
          React.createElement(ListItemIcon, {theme: props.theme}, [React.createElement(MeetingRoomIcon)]),
        ),
        React.createElement(ListItemText, {key: this.getKey('exittext'), theme: props.theme}, [''])
      ]));
    }
    this.list = React.createElement(List, {key: this.getKey('menulist'), theme: props.theme}, listitems);
    this.toolbar = React.createElement(Toolbar, {key: this.getKey('toolbar'), theme: props.theme});
    this.useravatar = React.createElement(ListItemAvatar, {key: this.getKey('avatar'), theme: props.theme}, 
      React.createElement(Tooltip, {theme: props.theme, arrow: true, title: _('Изменить профиль пользователя'), 'aria-label': 'Edit profile'}, 
        React.createElement(Avatar, {theme: props.theme, onClick: this.avatarClick}, 
          React.createElement(Button, {color: 'inherit', theme: props.theme}, 
            React.createElement(PersonIcon)
          )
        )
      )
    );
    this.userlabel = React.createElement(ListItemText, {key: this.getKey('userlabel'), theme: props.theme, primary: (((isSet(userdata)) && userdata && (isSet(userdata.group)))?userdata.name:(React.createElement(Skeleton, {variant: 'text'}))), secondary: (((isSet(userdata)) && userdata && (isSet(userdata.group)))?userdata.group.name:(React.createElement(Skeleton, {variant: 'text'})))});
    this.useraction = React.createElement(Tooltip, {key: this.getKey('useraction'), theme: props.theme, arrow: true, title: _('Выход'), 'aria-label': 'Logout'}, 
      React.createElement(IconButton, {theme: props.theme, edge: 'end', onClick: this.logoutClick, 'aria-label': 'logout'}, React.createElement(MeetingRoomIcon))
    );
    this.userinfo = React.createElement(ListItem, {key: this.getKey('userinfo'), theme: props.theme}, [this.useravatar, this.userlabel, this.useraction]);
    
    this.divider = React.createElement(Divider, {key: this.getKey('menudivider')});
    if(this.closed) {
      if(!this.minimized) {
        return React.createElement(Drawer, {key: this.getKey('root'), theme: props.theme, classes: {paper: classes.drawerClose}, className: clsx(classes.drawer, classes.drawerClose), variant: 'permanent'}, [this.toolbar, this.userinfo, this.divider, this.list]);
      } else {
        return React.createElement(Drawer, {key: this.getKey('root'), theme: props.theme, classes: {paper: classes.drawerMinimizedClose}, className: clsx(classes.drawer, classes.drawerMinimizedClose), variant: 'permanent'}, [this.toolbar, this.userinfo, this.divider, this.list]);
      }
    } else {
      if(!this.minimized) {
        return React.createElement(Drawer, {key: this.getKey('root'), theme: props.theme, classes: {paper: classes.drawerOpen}, className: clsx(classes.drawer, classes.drawerOpen), variant: 'permanent'}, [this.toolbar, this.userinfo, this.divider, this.list]);
      } else {
        return React.createElement(Drawer, {key: this.getKey('root'), theme: props.theme, classes: {paper: classes.drawerMinimizedOpen}, className: clsx(classes.drawer, classes.drawerMinimizedOpen), variant: 'permanent'}, [this.toolbar, this.userinfo, this.divider, this.list]);
      }
    }
  }

  avatarClick(event) {
    if(userdata) {
      setLocation('settings/security/user', {id: userdata.id});
    }
  }

  itemClick(event, item) {
    if(!item.link) {
      item.open = !item.open;
      this.redraw();
    } else {
      setLocation(item.link);
    }
  }

  logoutClick(event) {
    postRPCJson('/?json=logout', {}).done(function(data) {
      location.reload();
    });
    return false;
  }

  setValue(data) {
    if(!data) return;
    if(isSet(data.items)) {
      this.items = data.items;
    }
    if(isSet(data.minimized)) {
      this.minimized = data.minimized;
      localStorage.setItem('menu_minimized', this.minimized);
    }
    if(isSet(data.closed)) {
      this.closed = data.closed;
    }
    this.redraw();
  }
}

widgets.section = class sectionWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    if(typeof data == 'string') data = {id: data};
    super(parent,data,label,hint);
    this.nogap = false;
    this.height = null;
    this.paper = false;
    this.greyscaled = false;
    this.inlined = false;
    this.small = false;
    if(parent) {
      if((parent instanceof baseWidget)&&(parent.itemsalign!==null)) {
        this.itemsalign = {
          xs: 12,
        }; 
      } else {
        this.itemsalign = {
          xs: 12,
          lg: 6,
        };
      }
    }
    this.basestyle = {
      offset: maintheme.mixins.toolbar,
      container: {
        padding: maintheme.spacing(2),
        paddingTop: maintheme.spacing(3),
        paddingBottom: maintheme.spacing(8),
        overflowY: 'auto',
        height: 'calc( 100vh - 24px - '+maintheme.mixins.toolbar.minHeight+'px - '+maintheme.spacing(10)+')',
        '@media (min-width:0px) and (orientation: landscape)': {
          height: 'calc( 100vh - 24px - '+maintheme.mixins.toolbar['@media (min-width:0px) and (orientation: landscape)'].minHeight+'px - '+maintheme.spacing(10)+')',
        },
        '@media (min-width:600px)': {
          height: 'calc( 100vh - 24px - '+maintheme.mixins.toolbar['@media (min-width:600px)'].minHeight+'px - '+maintheme.spacing(10)+')',
        }
      },
      greyscaled: {
        padding: maintheme.spacing(2),
        paddingTop: maintheme.spacing(3),
        paddingBottom: maintheme.spacing(8),
        overflowY: 'auto',
        backgroundColor: '#f5f5f5',
        height: 'calc( 100vh - 24px - '+maintheme.mixins.toolbar.minHeight+'px - '+maintheme.spacing(10)+')',
        '@media (min-width:0px) and (orientation: landscape)': {
          height: 'calc( 100vh - 24px - '+maintheme.mixins.toolbar['@media (min-width:0px) and (orientation: landscape)'].minHeight+'px - '+maintheme.spacing(10)+')',
        },
        '@media (min-width:600px)': {
          height: 'calc( 100vh - 24px - '+maintheme.mixins.toolbar['@media (min-width:600px)'].minHeight+'px - '+maintheme.spacing(10)+')',
        },
      },
      paper: {
        padding: maintheme.spacing(2),
      }
    };
    this.setParent(parent);
  }

  prepareChildren(props, object, childNodes) {
    for(let i in object.children) {
      if(!object.children[i].hidden) {
        let node = null;
        if(this.itemsalign!==null) {
          if((object.children[i] instanceof widgets.section) && (object.children[i].inlined)) {
            this.prepareChildren(props, object.children[i], childNodes);
          } else {
            if(object.children[i].selfalign!==null) {
              node = React.createElement(Grid, _extends({key: object.children[i].getID(), item: true}, object.children[i].selfalign), React.createElement(object.children[i].prepare, {theme: props.theme}));
            } else {
              node = React.createElement(Grid, _extends({key: object.children[i].getID(), item: true}, this.itemsalign), React.createElement(object.children[i].prepare, {theme: props.theme}));
            }
          }
        } else {
          if((object.children[i] instanceof widgets.section) && (object.children[i].inlined)) {
            this.prepareChildren(props, object.children[i], childNodes);
          } else {
            node = React.createElement(object.children[i].prepare, {theme: props.theme, flexGrow: 1, key: object.children[i].getID()});
          }
        }
        if(node) {
          if(object.children[i].hidesteps!==null) node = React.createElement(Hidden, _extends({key: object.children[i].getKey('hidden'), theme: props.theme}, object.children[i].hidesteps), node);
          childNodes.push(node);
        }
      }
    }
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const useStyles = makeStyles(this.basestyle);
    const classes = useStyles();
    let childNodes = [];
    this.prepareChildren(props, this, childNodes);
    if(this.labeltext && !(this.parent instanceof widgets.tabs)) { //If not Tab and has label text - draw a card header
      if(this.hinttext) {
        this.hint = React.createElement(this.createChipHint, {theme: props.theme, key: this.getKey('hint')}, this.hinttext);
      } else {
        this.hint = null;
      }
      if(this.hint) {
        this.label = React.createElement(Typography, {key: this.getKey('label'), theme: props.theme, variant: this.small?'subtitle1':'h6', component: 'h6', gutterBottom: true}, [this.labeltext, this.hint]);
      } else {
        this.label = React.createElement(Typography, {key: this.getKey('label'), theme: props.theme, variant: this.small?'subtitle1':'h6', component: 'h6', gutterBottom: true}, [this.labeltext]);
      }
      this.container = React.createRef();
      let node = null;
      if(this.itemsalign!==null) {
        node = React.createElement(Card, {key: this.getKey('card'), theme: props.theme, elevation: 1}, [
          React.createElement(CardHeader, {key: this.getKey('header'), theme: props.theme, subheader: this.label}),
          React.createElement(CardContent, {key: this.getKey('content'), theme: props.theme, ref: this.container}, React.createElement(React.Fragment, {key: this.getKey('container')}, React.createElement(Grid, {container: true, justifyContent: 'center', alignContent: 'center', spacing: 3}, childNodes)))
        ]);
      } else {
        node = React.createElement(Card, {key: this.getKey('card'), theme: props.theme, elevation: 1}, [
          React.createElement(CardHeader, {key: this.getKey('header'), theme: props.theme, subheader: this.label}),
          React.createElement(CardContent, {key: this.getKey('content'), theme: props.theme, ref: this.container}, React.createElement(React.Fragment, {key: this.getKey('container')}, childNodes))
        ]);
      }
      if(this==rootcontent) {
        if(this.nogap) {
          return React.createElement(React.Fragment, {key: this.getKey('root')}, node);
        } else {
          return React.createElement(React.Fragment, {key: this.getKey('root')}, [React.createElement(Box, {key: this.getKey('pad'), className: classes.offset}), React.createElement(Box, {key: this.getKey('main'), className: this.greyscaled?classes.greyscaled:classes.container}, node)]);
        }
      } else {
        return node;
      }
    } else {
      if((this==rootcontent)&&!this.nogap) {
        this.container = React.createRef();
        if(this.itemsalign!==null) {
          return React.createElement(React.Fragment, {key: this.getKey('root')}, [React.createElement(Box, {key: this.getKey('pad'), className: classes.offset}), React.createElement(Box, {key: this.getKey('main'), className: this.greyscaled?classes.greyscaled:classes.container, ref: this.container}, React.createElement(Grid, {container: true, justifyContent: 'center', alignContent: 'center', spacing: 3}, React.createElement(React.Fragment, {key: this.getKey('container')}, childNodes)))]);
        } else {
          return React.createElement(React.Fragment, {key: this.getKey('root')}, [React.createElement(Box, {key: this.getKey('pad'), className: classes.offset}), React.createElement(Box, {key: this.getKey('main'), className: this.greyscaled?classes.greyscaled:classes.container, ref: this.container}, React.createElement(React.Fragment, {key: this.getKey('container')}, childNodes))]);
        }
      } else {
        if(this.paper) {
          if(this.itemsalign!==null) {
            return React.createElement(Paper, {key: this.getKey('container'), className: classes.paper}, React.createElement(Grid, {container: true, height: this.height, justifyContent: 'center', alignContent: 'center', spacing: 3}, childNodes));
          } else {
            return React.createElement(Paper, {key: this.getKey('container'), className: classes.paper}, childNodes);
          }
        } else {
          if(this.itemsalign!==null) {
            return React.createElement(React.Fragment, {key: this.getKey('container')}, React.createElement(Grid, {container: true, height: this.height, justifyContent: 'center', alignContent: 'center', spacing: 3}, childNodes));
          } else {
            return React.createElement(React.Fragment, {key: this.getKey('container')}, childNodes);
          }
        }
      }
    }
  }

  setValue(data) {
    if(!isSet(data)) data = {};
    if(isSet(data.greyscaled)) {
      if(data.greyscaled) {
        this.greyscaled = true;
      } else {
        this.greyscaled = false;
      }
    }
    if(isSet(data.paper)) {
      if(data.paper) {
        this.paper = true;
      } else {
        this.paper = false;
      }
    }
    if(isSet(data.small)) {
      if(data.small) {
        this.small = true;
      } else {
        this.small = false;
      }
    }
    super.setValue(data);
  }
}

widgets.tabs=class tabsWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,null,null);
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const [tab, setTab] = React.useState(0);
    let tabs = [];
    for(let i in this.children) {
      if(!this.children[i].hidden) {
        if(this.children[i].hinttext) {
          this.children[i].hint = this.children[i].createChipHint(props);
        } else {
          this.children[i].hint = null;
        }
        if(this.children[i].hint) {
          this.children[i].label = React.createElement(React.Fragment, {key: this.getKey('label')}, [this.children[i].labeltext, this.children[i].hint]);
        } else {
          this.children[i].label = React.createElement(React.Fragment, {key: this.getKey('label')}, [this.children[i].labeltext]);
        }
        tabs.push(React.createElement(Tab, {key: this.getKey('tab', i), label: this.children[i].label, id: (this.getID()+'-'+i), 'aria-controls': (this.getID()+'-'+i)}));
      }
    }
    this.tabs = React.createElement(Tabs, {theme: props.theme, value: tab, onChange: (e, index) => {setTab(index);}, indicatorColor: 'primary', textColor: 'primary', variant: 'fullwidth', 'aria-label': this.getID()}, tabs);
    this.appbar = React.createElement(AppBar, {key: this.getKey('appbar'), theme: props.theme, position: 'static', color: 'default'}, this.tabs);
    tabs = [];
    for(let i in this.children) {
      if(!this.children[i].hidden) {
        tabs.push(React.createElement(TabPanel, {key: this.getKey('panel', this.children[i].getID()), value: tab, index: i}, React.createElement(this.children[i].prepare, props)));
      }
    }
    this.swipeable = React.createElement(SwipeableViews, {key: this.getKey('swipeable'), axis: (props.theme.direction === 'rtl' ? 'x-reverse' : 'x'), index: tab, inChangeIndex: (index) => {setTab(index);}}, tabs);
    return React.createElement(React.Fragment, {key: this.getKey('root')}, [this.appbar, this.swipeable]);
  }

}

widgets.dialog=class dialogWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.itemsalign = {
      xs: 12,
      xl: 6,
    }; 
    this.buttons = [];
    this.maxWidth = null;
    this.fullWidth = false;
    this.hidden = true;
    this.onOpened = null;
    this.onOpen = null;
    this.onClosed = null;
    this.onClose = null;
    this.onSave = null;
    this.onReset = null;
    this.onReturn = null;
    this.onDelete = null;
    this.hasclose = true;
    this.color = null;
    this.elementlabel = null;
    this.appendfunc = null;
    this.applyfunc = null;
    this.resetfunc = null;
    this.renderLock();
    if((isSet(data)) && data) this.setValue(data);
    this.renderUnlock();
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const fullScreen = useMediaQuery(maintheme.breakpoints.down('sm'));
    this.container = React.createRef();
    if(this.hinttext) {
      this.hint = React.createElement(this.createChipHint, {theme: props.theme, key: this.getKey('hint')}, this.hinttext);
      this.label = React.createElement(Typography, {key: this.getKey('label'), variant: 'h6', noWrap: true}, [(this.elementlabel?(this.labeltext+': '+this.elementlabel):this.labeltext), this.hint]);
    } else {
      this.label = React.createElement(Typography, {key: this.getKey('label'), variant: 'h6', noWrap: true}, (this.elementlabel?(this.labeltext+': '+this.elementlabel):this.labeltext));
    }
    if(this.color!==null) {
      this.title = React.createElement(DialogTitle, {key: this.getKey('title'), theme: props.theme, style: {backgroundColor: props.theme.palette[this.color].light}}, this.label);
    } else {
      this.title = React.createElement(DialogTitle, {key: this.getKey('title'), theme: props.theme}, this.label);
    }
    let children = [];
    for(let i in this.children) {
      if(!this.children[i].hidden) {
        let node = null;
        if(this.itemsalign!==null) {
          if(this.children[i].selfalign!==null) {
            node = React.createElement(Grid, _extends({key: this.children[i].getID(), item: true}, this.children[i].selfalign), React.createElement(this.children[i].prepare, {theme: props.theme}));
          } else {
            node = React.createElement(Grid, _extends({key: this.children[i].getID(), item: true}, this.itemsalign), React.createElement(this.children[i].prepare, {theme: props.theme}));
          }
        } else {
          node = React.createElement(this.children[i].prepare, {theme: props.theme, flexGrow: 1, key: this.children[i].getID()});
        }
        if(this.children[i].hidesteps!==null) node = React.createElement(Hidden, _extends({key: this.children[i].getKey('hidden'), theme: props.theme}, this.children[i].hidesteps), node);
        children.push(node);
      }
    }
    if(this.itemsalign!==null) {
      this.content = React.createElement(DialogContent, {key: this.getKey('dialogcontent'), theme: props.theme, ref: this.container}, React.createElement(Grid, {container: true, justifyContent: 'center', alignContent: 'center', spacing: 3}, children));
    } else {
      this.content = React.createElement(DialogContent, {key: this.getKey('dialogcontent'), theme: props.theme, ref: this.container}, children);
    }
    let buttons = [];

    if(isSet(this.view)) buttons.push(React.createElement(Tooltip, {key: this.getKey('removebtn'), theme: props.theme, arrow: true, title: _('Удалить'), 'aria-label': 'reset'}, React.createElement(Zoom, {in: (this.appendfunc!==null)}, React.createElement(Button, {theme: props.theme, color: 'error', variant: 'contained', onClick: async (e) => {
      if(await this.view.remove(this.view.data.id, this.view.data.title) !== false) {
        if(this.onDelete) this.onDelete(this, this.view.data);
        this.hide();
      }
    }, startIcon: React.createElement(DeleteSharpIcon)}, _('Удалить')))));
    // buttons.push(React.createElement(Tooltip, {key: this.getKey('addbtn'), theme: props.theme, arrow: true, title: _('Добавить'), 'aria-label': 'add'}, React.createElement(Zoom, {in: ((this.appendfunc!==null)&&(this.applyfunc===null))}, React.createElement(Button, {theme: props.theme, color: 'secondary', variant: 'contained', onClick: this.appendfunc, startIcon: React.createElement(AddIcon)}, _('Добавить')))));
    buttons.push(React.createElement(Tooltip, {key: this.getKey('applybtn'), theme: props.theme, arrow: true, title: _('Применить'), 'aria-label': 'apply'}, React.createElement(Zoom, {in: (this.applyfunc!==null)}, React.createElement(Button, {theme: props.theme, color: 'secondary', variant: 'contained', onClick: async (e) => {this.applyfunc(e); if(this.onSave) this.onSave(this, this.view.data); this.hide(); }, startIcon: React.createElement(DoneIcon)}, _('Применить')))));
    buttons.push(React.createElement(Tooltip, {key: this.getKey('resetbtn'), theme: props.theme, arrow: true, title: _('Сбросить'), 'aria-label': 'reset'}, React.createElement(Zoom, {in: (((this.applyfunc!==null)||(this.appendfunc!==null))&&(this.resetfunc!==null))}, React.createElement(Button, {theme: props.theme, color: 'error', variant: 'contained', onClick: async (e) => {this.resetfunc(e); if(this.onReset) this.onReset(this); }, startIcon: React.createElement(RotateLeftIcon)}, _('Сбросить')))));

    for(let i in this.buttons) {
      if(!this.buttons[i].hidden) {
        let node = React.createElement(this.buttons[i].prepare, {theme: props.theme, flexGrow: 1, key: this.buttons[i].getID()});
        if(this.buttons[i].hidesteps!==null) node = React.createElement(Hidden, _extends({key: this.buttons[i].getKey('hidden'), theme: props.theme}, this.buttons[i].hidesteps), node);
        buttons.push(node);
      }
    }

    if(this.hasclose) buttons.push(React.createElement(Tooltip, {key: this.getKey('closebtn'), theme: props.theme, arrow: true, title: _('Закрыть'), 'aria-label': 'reset'}, React.createElement(Button, {theme: props.theme, color: 'primary', onClick: () => { this.hide(); }}, _('Закрыть'))));
    this.actions = React.createElement(DialogActions, {key: this.getKey('actions'), theme: props.theme}, buttons);
    return React.createElement(Dialog, {
      key: this.getKey('root'),
      ref: this.dialog,
      theme: props.theme,
      fullScreen: fullScreen,
      TransitionComponent: Slide,
      fullWidth: this.fullWidth,
      maxWidth: this.maxWidth,
      keepMounted: true,
      container: () => this.getContainer(),
      open: !this.hidden,
      onClose: () => {
        if(this.onClose) {
          if(this.onClose(this)) this.hide();
        } else {
          this.hide();
        }
      },
    }, [this.title, this.content, this.actions]);
  }

  setValue(avalue) {
    if((!isSet(avalue)) || !avalue) avalue = {};
    if(typeof avalue.maxWidth == 'string') {
      this.maxWidth = avalue.maxWidth;
    }
    if(typeof avalue.color == 'string') {
      this.color = avalue.color;
    }
    if(isSet(avalue.fullWidth)) {
      if(avalue.fullWidth) {
        this.fullWidth = true;
      } else {
        this.fullWidth = false;
      }
    }
    if(isSet(avalue.hasclose)) {
      if(avalue.hasclose) {
        this.hasclose = true;
      } else {
        this.hasclose = false;
      }
    }
    super.setValue(avalue);
  }

  setReset(resetfunc) {
    this.resetfunc = resetfunc;
    this.redraw();
    return true;
  }
  
  setApply(applyfunc) {
    this.applyfunc = async (e) => {
      await applyfunc(e);
    }
    this.redraw();
    return true;
  }

  setAppend(appendfunc) {
    this.appendfunc = appendfunc;
    this.redraw();
    return true;
  }
  
  setElement(elementlabel) {
    this.elementlabel = elementlabel;
    this.redraw();
    return true;
  }

  getContainer() {
    let parent = this;
    while(parent.parent instanceof baseWidget) {
      if(parent.parent.locked>0) {
        locked = true;
        break;
      }
      parent = parent.parent;
    }
    return parent.parent;
  }

  show() {
    super.show();
    if(this.onOpen) this.onOpen(this);
  }

}

widgets.alert=class alertWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.severity = 'none';
    this.onClose = null;
    this.renderLock();
    if((isSet(data)) && data) this.setValue(data);
    this.renderUnlock();
    this.header = null;
    if(this.severity !== 'none') {
      switch(this.severity) {
        case 'error': this.header = _('Ошибка выполнения!'); break;
        case 'warning': this.header = _('Внимание!'); break;
        case 'info': this.header = _('Информационное собщение'); break;
        case 'success': this.header = _('Успешная операция'); break;
        default: this.header = _("Уведомление");
      }
    }
    if (Notification.permission === "default") {
      Notification.requestPermission(function (permission) {});
    }
    if(!window.focused) {
      if (("Notification" in window)) {
        // Проверка разрешения на отправку уведомлений
        if (Notification.permission === "granted") {
          // Если разрешено, то создаём уведомление
          let notification = new Notification(this.header, {
            body: this.labeltext,
            icon: '/favicon.svg'
          });
        }      
        // В противном случае, запрашиваем разрешение
        else if (Notification.permission !== 'denied') {
          Notification.requestPermission(function (permission) {
            // Если пользователь разрешил, то создаём уведомление
            if (permission === "granted") {
              let notification = new Notification(this.header, {
                body: this.labeltext,
                icon: '/favicon.svg'
              });
            }
          });
        }    
      }
    }
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const [open, setOpen] = React.useState(true);
    let duration = 2000;
    if(this.severity !== 'none') {
      if(this.severity == 'error') duration = 6000;
      if(this.severity == 'warning') duration = 4000;
    }
    if(this.severity !== 'none') {
      return React.createElement(ThemeProvider, {key: this.getKey('root'), theme: props.theme}, React.createElement(Snackbar, {key: this.getKey('snack'), TransitionComponent: TransitionRight, open: open, autoHideDuration: duration, onClose: () => {setOpen(false); if(this.onClose) this.onClose(this);}, anchorOrigin: {vertical: 'top', horizontal: 'right'}}, React.createElement(Alert, {onClose: () => {setOpen(false); if(this.onClose) this.onClose(this);}, severity: this.severity, variant: 'filled'}, [React.createElement(AlertTitle, {}, this.header), this.labeltext])));
    } else {
      return React.createElement(ThemeProvider, {key: this.getKey('root'), theme: props.theme}, React.createElement(Snackbar, {key: this.getKey('snack'), TransitionComponent: TransitionRight, open: open, autoHideDuration: duration, onClose: () => {setOpen(false); if(this.onClose) this.onClose(this);}, anchorOrigin: {vertical: 'top', horizontal: 'right'}, message: this.labeltext}));
    }
  }

  setValue(avalue) {
    if((!isSet(avalue)) || !avalue) avalue = {};
    if(typeof avalue.severity == 'string') {
      switch(avalue.severity) {
        case 'error':
        case 'warning':
        case 'info':
        case 'success': {
          this.severity = avalue.severity
        } break;
        case 'danger' : {
          this.severity = 'error';
        } break;
        default: {
          this.severity = 'none';
        }
      }
    }
    if(typeof avalue.onClose == 'function') {
      this.onClose = avalue.onClose;
    }
    this.redraw();
  }

  getValue() {
    return null;
  }

}

widgets.label=class labelWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.asHTML = false;
    this.variant = 'body1';
    this.renderLock();
    if((isSet(data)) && data) this.setValue(data);
    this.renderUnlock();
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    if(this.hinttext) {
      this.hint = React.createElement(this.createChipHint, {key: this.getKey('hint'), theme: props.theme}, this.hinttext);
    } else {
      this.hint = null;
    }
    if(this.hint) {
      this.label = React.createElement(Typography, {key: this.getKey('label'), theme: props.theme, variant: this.variant}, [this.labeltext, this.hint]);
    } else {
      if(this.asHTML) {
        this.label = React.createElement(Typography, {key: this.getKey('label'), theme: props.theme, variant: this.variant, dangerouslySetInnerHTML: { __html: this.labeltext}});
      } else {
        this.label = React.createElement(Typography, {key: this.getKey('label'), theme: props.theme, variant: this.variant}, this.labeltext);
      }
    }
    return this.label;
  }

  setValue(avalue) {
    if((!isSet(avalue)) || !avalue) avalue = {};
    if(isSet(avalue.asHTML)) {
      if(avalue.asHTML) {
        this.asHTML = true;
      } else {
        this.asHTML = false;
      }
    }
    if(typeof avalue.variant == 'string') {
      this.variant = avalue.variant;
    }
  }

}

widgets.divider=class dividerWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.vertical = false;
    this.variant = 'middle';
    this.renderLock();
    if((isSet(data)) && data) this.setValue(data);
    this.renderUnlock();
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    this.label = React.createElement(Divider, {key: this.getKey('root'), theme: props.theme, variant: this.variant, orientation: this.vertical?'vertical':'horizontal'}, [this.labeltext]);
    return this.label;
  }

  setValue(avalue) {
    if((!isSet(avalue)) || !avalue) avalue = {};
    if(isSet(avalue.vertical)) {
      if(avalue.asHTML) {
        this.vertical = true;
      } else {
        this.vertical = false;
      }
    }
    if(typeof avalue.variant == 'string') {
      this.variant = avalue.variant;
    }
  }

}

widgets.input=class inputWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.basestyle = (theme) => ({
      input: {
        flex: 1,
      },
      startButton: {
        padding: 10,
      },
      divider: {
        height: 28,
        margin: 4,
      },
    });
    this.mode = 'standard';
    this.lines = 1;
    this.value = '';
    this.error = false
    this.small = false
    this.expand = false;
    this.password = false;
    this.secure = false;
    this.color = 'secondary';
    this.icon = null;
    this.prefix = null;
    this.readonly = false;
    this.defaultvalue = null;
    this.placeholder = null;
    this.pattern = null;
    this.required = false;
    this.onInput=null;
    this.onChange=null;
    this.onEnter=null;
    this.renderLock();
    if((isSet(data)) && data) this.setValue(data);
    this.renderUnlock();
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const [showPassword, setShowPassword] = React.useState(false);
    const useStyles = makeStyles(this.basestyle(colorscheme));
    const classes = useStyles();
    const [value, setValue] = React.useState(this.value);
    if(value != this.value) setValue(this.value);
    let flexGrow = null;
    if(isSet(props.flexGrow)) flexGrow = props.flexGrow;
    if(this.loading) {
      this.label = React.createElement(InputLabel, {key: this.getKey('label'), color: this.color, htmlFor: this.getID()}, this.labeltext);
      if(this.hinttext) {
        this.hint = React.createElement(FormHelperText, {key: this.getKey('hint')}, this.hinttext);
      } else {
        this.hint = null;
      }
      this.input = React.createElement(Select, {
        key: this.getKey('select'), 
        color: this.color,
        value: React.createElement(Skeleton, {variant: 'text'}),
        open: false,
        renderValue: (value) => {
          return React.createElement(Skeleton, {variant: 'text'});
        },
        disabled: this.disabled,
        IconComponent: null,
        displayEmpty: false,
      }, []);
      return React.createElement(ThemeProvider, {key: this.getKey('root'), theme: props.theme}, React.createElement(FormControl, {className: ((this.parent instanceof widgets.section) && (this.parent.itemsalign==null))?classes.controlinline:classes.control, style: {flexGrow: flexGrow, width: isSet(props.width)?props.width:null}, fullWidth: ((this.parent instanceof baseWidget) && (this.parent.itemsalign!==null))}, [this.label, this.input, this.hint]));
    } else {   
      this.label = React.createElement(InputLabel, {key: this.getKey('label'), color: this.color, required: this.required, htmlFor: this.getID()}, this.labeltext);
      if(this.hinttext) {
        this.hint = React.createElement(FormHelperText, {key: this.getKey('hint')}, this.hinttext);
      } else {
        this.hint = null;
      }
      let items = [];
      let subitems = [];
      items.push(this.label);
      let icon = null;
      if(this.icon && (isSet(window[this.icon]))) icon = React.createElement(InputAdornment, {key: this.getKey('icon'), position: 'start'}, React.createElement(window[this.icon], {className: classes.startButton}));
      if(this.prefix) subitems.push(this.prefix);
      if(this.password&&!this.secure) subitems.push(React.createElement(IconButton, {
                                                        key: this.getKey('passwdbtn'),
                                                        theme: props.theme,
                                                        onClick: () => {setShowPassword(!showPassword);},
                                                        onMouseDown: (e) => {e.preventDefault();}
                                                      }, showPassword?(React.createElement(VisibilityOffIcon)):(React.createElement(VisibilityIcon)))
                                                    );
      if(this.children.length>0) subitems.push(React.createElement(Divider, {key: this.getKey('divider'), className: classes.divider, orientation: 'vertical'}));
      for(let i in this.children) {
        if(!this.children[i].hidden) {
          subitems.push(React.createElement(this.children[i].prepare, {key: this.children[i].getID(), theme: props.theme}));
        }
      }
      let endandor = null;
      if(subitems.length) {
        endandor = React.createElement(InputAdornment, {key: this.getKey('endandor'), position: 'end'}, subitems);
      }
      this.input = React.createElement(ReactInputMask, {
        key: this.getKey('mask'),
        value: value,
        mask: this.pattern,
        disabled: this.disabled,
        readOnly: this.readonly,
        onChange: (e) => {
          this.value = e.target.value;
          if(this.onChange) this.onChange(this, props);
          setValue(this.value);
        },
        onInput: (e) => {
          if(this.onInput) this.onInput(this, props);
        },
        onKeyDown: (e) => {
          if (e.key === "Enter") {
            if(this.onEnter) this.onEnter(this, props);
          }
        },
      }, React.createElement(Input, {
        key: this.getKey('input'),
        color: this.color,
        className: classes.input,
        startAdornment: icon,
        endAdornment: endandor,
        placeholder: this.placeholder,
        error: this.error,
        multiline: this.lines>1,
        rowsMax: this.lines,
        inputProps: {
          disabled: this.disabled,
          autocomplete: this.password?"new-password":null,
          type: (this.password && !showPassword)?'password':'text'
        }
      }) );
      items.push(this.input);
      if(this.hint) items.push(this.hint);
      return React.createElement(ThemeProvider, {key: this.getKey('root'), theme: props.theme}, React.createElement(FormControl, {error: this.error, style: {flexGrow: flexGrow, alignSelf: ((this.parent instanceof widgets.section)&&(this.parent.itemsalign==null))?'flex-start':'auto'}, fullWidth: ((this.parent instanceof baseWidget) && (this.parent.itemsalign!==null)), disabled: this.disabled}, items));
    }
  }

  setValue(avalue) {
    if((!isSet(avalue))) avalue = {};
    if(typeof avalue == 'string') {
      avalue = {value: avalue};
    } else if(typeof avalue == 'number') {
      avalue = {value: String(avalue)};
    }
    if(typeof avalue.lines == 'number') {
      this.lines = avalue.lines;
    }
    if(typeof avalue.default != 'undefined') {
      this.defaultvalue = avalue.default;
      if(!isSet(avalue.value)) avalue.value = false;
    }
    if((this.defaultvalue!==null)&&isSet(avalue.value)&&(avalue.value === false)) {
      avalue.value = this.defaultvalue;
    }
    if(typeof avalue.value == 'string') {
      this.value = avalue.value;
    }
    if(typeof avalue.icon == 'string') {
      this.icon = avalue.icon;
    }
    if(typeof avalue.color == 'string') {
      this.color = avalue.color;
    }
    if(typeof avalue.pattern == 'string' || avalue.pattern instanceof RegExp || avalue.pattern instanceof Array) {
      this.pattern = avalue.pattern;
    }
    if(typeof avalue.placeholder == 'string') {
      this.placeholder = avalue.placeholder;
    }
    if(typeof avalue.prefix == 'string') {
      this.prefix = avalue.prefix;
    }
    if(typeof avalue.mode == 'string') {
      if(avalue.mode == 'outlined') {
        this.mode = 'outlined';
      } else 
      if(avalue.mode == 'filled') {
        this.mode = 'filled';
      } else this.mode = 'standard';
    }
    if(isSet(avalue.password)) {
      if(avalue.password) {
        this.password = true;
      } else {
        this.password = false;
      }
    }
    if(isSet(avalue.secure)) {
      if(avalue.secure) {
        this.secure = true;
      } else {
        this.secure = false;
      }
    }
    if(isSet(avalue.required)) {
      if(avalue.required) {
        this.required = true;
      } else {
        this.required = false;
      }
    }
    if(isSet(avalue.readonly)) {
      if(avalue.readonly) {
        this.readonly = true;
      } else {
        this.readonly = false;
      }
    }
    if(isSet(avalue.error)) {
      if(avalue.error) {
        this.error = true;
      } else {
        this.error = false;
      }
    }
    if(isSet(avalue.small)) {
      if(avalue.small) {
        this.small = true;
      } else {
        this.small = false;
      }
    }
    if(isSet(avalue.expand)) {
      if(avalue.expand) {
        this.expand = true;
      } else {
        this.expand = false;
      }
    }
    this.redraw();
    return true;
  }

  getValue() {
    if((this.default!==null)&&(this.value==='')) return this.default;
    return this.value;
  }
}

widgets.file=class fileWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.multiple = false;
    this.value = null;
    this.accept = null;
    this.color = 'primary';
    this.onChange = null;
    this.btnhidden = false;
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    this.inputref = React.createRef();
    this.input = React.createElement('input', {key: this.getKey('input'), ref: this.inputref, accept: this.accept, style: {display: 'none'}, id: this.getID(), multiple: this.multiple, type: 'file', onChange: (e) => {this.value = e.target.files; if(this.onChange) this.onChange(this, props);}});
    if(this.children.length>0) {
      let children = [];
      for(let i in this.children) {
        if(!this.children[i].hidden) {
          children.push(React.createElement(this.children[i].prepare, {key: this.children[i].getID(), theme: props.theme}));
        }
      }
      this.label = React.createElement('label', {key: this.getKey('label'), htmlFor: this.getID()}, children);
    } else {
      this.label = React.createElement('label', {key: this.getKey('label'), htmlFor: this.getID()}, 
        React.createElement(Button, {theme: props.theme, variant: 'contained', color: this.color, component: 'span'}, this.labeltext?this.labeltext:_('Обзор...'))
      );
    }
    return React.createElement(ThemeProvider, {key: this.getKey('root'), theme: props.theme}, React.createElement(React.Fragment, {}, [this.input, this.label]));
  }

  setValue(avalue) {
    if((!isSet(avalue)) || !avalue) avalue = {};
    if(typeof avalue.accept == 'string') {
      this.accept = avalue.accept;
    }
    if(typeof avalue.color == 'string') {
      this.color = avalue.color;
    }
    if(isSet(avalue.multiple)) {
      if(avalue.multiple) {
        this.multiple = true;
      } else {
        this.multiple = false;
      }
    }
    this.redraw();
  }

  getValue() {
    let value = [];
    for(let i in this.value) {
      if(this.value[i] instanceof File) value.push(this.value[i]);
    }
    return value;
  }

  open() {
    this.inputref.current.click();
  }

  hide() {
    this.selfalign = {style: {display: 'none'}};
    this.btnhidden = true;
    this.render();
  }

  show() {
    this.selfalign = null;
    this.btnhidden = false;
    this.render();
  }

  reset() {
    this.value = null;
  }

}

widgets.select=class selectWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.basestyle = {
      control: {
        margin: maintheme.spacing(1),
        minWidth: '120px !important',
      },      
      controlinline: {
        margin: maintheme.spacing(1),
        minWidth: '120px !important',
        alignSelf: 'flex-start',
      },      
      popper: {
        width: 'max-content',
      },
      fullscreen: {
        width: '100vw !important',
        height: '100vh !important',
        position: 'fixed',
        display: 'block',
        left: '0',
        top: '0',
        zIndex: 4000,
        backgroundColor: 'white',
      },
      fullscreenPaper: {
        height: 'calc( 100% - '+maintheme.spacing(6)+' ) !important',
        width: '100% !important',
      },
      fullscreenList: {
        display: 'block !important',
        position: 'absolute',
        maxHeight: '100% !important',
        height: '100% !important',
        width: '100% !important',
      },
      fullscreenClose: {
        display: 'block !important',
        position: 'absolute',
        bottom: '0',
        left: '0',
        height: maintheme.spacing(6)+' !important',
        width: 'calc( 100% - '+maintheme.spacing(4)+' ) !important',
        paddingLeft: maintheme.spacing(2),
        paddingRight: maintheme.spacing(2),
      },
      masterLabel: {
        display: 'flex',
        lineHeight: '1rem !important',
      },
      subLabel: {
        display: 'flex',
        lineHeight: '0.7rem !important',
        fontSize: '0.7rem !important',
      }
    };
    this.value = '';
    this.mode = 'standard';
    this.color = 'primary';
    this.minlines = 2;
    this.icon = null;
    this.multiple = false;
    if(!isSet(this.__proto__.views)) this.__proto__.views = {};
    this.dialogview = null;
    this.minidialog = new widgets.section(null);
    this.minidialog.hide();
    this.minidialog.setApply(() => {
      if(this.onApply) {
        this.onApply(this, this.minidialog.view.getValue());
      } else {
        this.minidialog.view.send();
      }
    });
    this.prefix = null;
    this.readonly = true;
    this.defaultvalue = null;
    this.placeholder = null;
    this.onSelect=null;
    this.onChipText=null;
    this.onOptionText=null;
    this.onOptionVisible=null;
    this.onChange = null;
    this.onEdit = null;
    this.onDelete = null;
    this.onAppend = null;
    this.onApply = null;
    this.options = [];
    this.hiddenreal = false;
    this.renderLock();
    if(isSet(data)) this.setValue(data);
    this.renderUnlock();
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const useStyles = makeStyles(this.basestyle);
    const classes = useStyles();
    const fullScreen =  useMediaQuery(maintheme.breakpoints.down('sm'));
    const [updated, setUpdated] = React.useState(false);
    const [opened, setOpened] = React.useState(false);
    this.updated = updated;
    this.setUpdated = setUpdated;
    const popperWidth = (props) => {
      if(fullScreen) {
        return React.createElement(Box, {className: classes.fullscreen, placement: "bottom-start"}, [
          React.createElement(Box, {style: {}}, props.children),
          React.createElement(Box, {className: classes.fullscreenClose}, [
            React.createElement(Button, {variant: 'contained', color: 'primary', fullWidth: true, startIcon: React.createElement(ArrowBackSharpIcon), onClick: () => {
              this.minidialog.hide();
            }}, _('Закрыть')),
          ]),
        ]);
      } else {
        return React.createElement(MaterialUI.Popper, _extends(props, {style: this.basestyle.popper, placement: "bottom-start"}));
      }
    }
    const embeddedForm = (aprops) => {
      return React.createElement(React.Fragment, {}, [
        React.createElement(Box, {className: classes.fullscreenPaper}, React.createElement(this.minidialog, {theme: props.theme})),
        React.createElement(Box, {className: classes.fullscreenClose}, [
          React.createElement(Button, {variant: 'contained', color: 'secondary', fullWidth: true, startIcon: React.createElement(DoneSharpIcon), onClick: () => {
            if(this.minidialog.applyfunc) this.minidialog.applyfunc(this.minidialog);
            this.minidialog.hide();
          }}, _('Сохранить')),
        ]),
      ]);
    }
    let flexGrow = null;
    if(isSet(props.flexGrow)) flexGrow = props.flexGrow;
    if(this.loading) {
      this.label = React.createElement(InputLabel, {key: this.getKey('label'), color: this.color, htmlFor: this.getID()}, this.labeltext);
      if(this.hinttext) {
        this.hint = React.createElement(FormHelperText, {key: this.getKey('hint')}, this.hinttext);
      } else {
        this.hint = null;
      }
      this.select = React.createElement(Select, {
        key: this.getKey('select'), 
        color: this.color,
        value: React.createElement(Skeleton, {variant: 'text'}),
        open: opened,
        renderValue: (value) => {
          return React.createElement(Skeleton, {variant: 'text'});
        },
        onOpen: (e) => {
          // if(e.target==e.currentTarget || (e.target.nodeName == 'SPAN')) {
          //   setOpened(true);
          // }
          // var evt = new MouseEvent("click", e.nativeEvent);
          // e.target.dispatchEvent(evt);
        },
        onClose: (e) => {
          // setOpened(false);
        },
        // onChange: (e) => {this.value = e.target.value; if(this.onChange) this.onChange(this, props); setUpdated(!updated);},
        // multiple: this.multiple,
        disabled: this.disabled,
      }, []);
      return React.createElement(ThemeProvider, {key: this.getKey('root'), theme: props.theme}, React.createElement(FormControl, {className: ((this.parent instanceof widgets.section) && (this.parent.itemsalign==null))?classes.controlinline:classes.control, style: {flexGrow: flexGrow, width: isSet(props.width)?props.width:null}, fullWidth: ((this.parent instanceof baseWidget) && (this.parent.itemsalign!==null))}, [this.label, this.select, this.hint]));
    } else {
      if(this.readonly) {
        this.label = React.createElement(InputLabel, {key: this.getKey('label'), color: this.color, htmlFor: this.getID()}, this.labeltext);
        if(this.hinttext) {
          this.hint = React.createElement(FormHelperText, {key: this.getKey('hint')}, this.hinttext);
        } else {
          this.hint = null;
        }
        let processitems = (items, dense) => {
          let result = [];
          for(let i in items) {
            if(!isSet(items[i].value)) {
              let option = items[i];
              let label = this.onOptionText?(this.onOptionText(this, option, option)):option.title;
              let avatar = null;
              let action = null;
              let labelstyle = {};
              
              if(isSet(option.avatar)) {
                avatar = React.createElement(ListItemAvatar, {}, React.createElement(Avatar, {src: option.avatar}));
              } else if(isSet(option.icon)) {
                if(isSet(window[option.icon])) {
                  avatar = React.createElement(ListItemAvatar, {}, React.createElement(Avatar, {}, React.createElement(window[option.icon])));
                }
              }
              if(!(((this.dialogview==null)&&(this.onEdit==null))||(isSet(option.readonly)&&(option.readonly)))) {
                action = React.createElement(ListItemSecondaryAction, {}, React.createElement(IconButton, {'aria-label': 'edit', onClick: () => {
                  setOpened(false);
                  if(this.onEdit!==null) {
                    this.onEdit(this, option, option);
                  } else {
                    this.__proto__.views[this.dialogview].view.load(option.id);
                    this.__proto__.views[this.dialogview].show();
                  }
                }}, React.createElement(EditSharpIcon)));
                labelstyle = {paddingRight: props.theme.spacing(7)};
              }
              if(!((this.onDelete==null)||(isSet(option.readonly)&&(option.readonly)))) {
                action = React.createElement(ListItemSecondaryAction, {}, React.createElement(IconButton, {'aria-label': 'delete', onClick: () => {
                  setOpened(false);
                  if(this.onDelete!==null) {
                    this.onDelete(this, option);
                  } else {
                    this.__proto__.views[this.dialogview].view.remove(option.id, option.title);
                  }
                }}, React.createElement(DeleteSharpIcon)));
                labelstyle = {paddingRight: props.theme.spacing(7)};
              }
              if(dense) {
                labelstyle.paddingTop = 0;
                labelstyle.paddingBottom = 0;
                labelstyle.margin = 0;
              }
              if(label instanceof Array) {
                if(label.length == 3) {
                  label = React.createElement(ListItemText, {style: labelstyle, primary: label[0], secondary: React.createElement(
                    React.Fragment, {}, [
                      React.createElement(Typography, {component: 'p', variant: 'body2', color: 'textPrimary'}, label[1]),
                      label[2]
                    ]
                  )});
                } else {
                  label = React.createElement(ListItemText, {style: labelstyle, primary: label[0], secondary: label[1]});
                }
              } else {
                label = React.createElement(ListItemText, {style: labelstyle, primary: label});
              }
              result.push(React.createElement(dense?ListItem:MenuItem, {
                key: this.getKey('item', items[i].id),
                dense: dense,
                value: option.id,
                alignItems: 'flex-start',
                ContainerProps: dense?{
                  style: {
                    padding: 0
                  },
                }:{},
                style: dense?{
                  padding: 0
                }:{},
              }, [avatar, label, action]));
                // result.push(React.createElement(MenuItem, {key: this.getKey('item', items[i].id), value: items[i].id}, items[i].title));
            } else {
              result.push(React.createElement(ListSubheader, {key: this.getKey('subheader', items[i].id)}, items[i].title));
              result = result.concat(processitems(items[i].value), dense);
            }
          }
          return result;
        }
        let items = processitems(this.options, false);
        this.select = React.createElement(Select, {
          key: this.getKey('select'), 
          color: this.color,
          value: this.value,
          open: opened,
          renderValue: (value) => {
            if(value instanceof Array) {
              let rendervalue = [];
              for(let i in value) {
                let option = value[i];
                let entry = (typeof option == 'object')?(this.options[this.options.indexOfId(option.id)]):(this.options[this.options.indexOfId(option)]);
                let label = isSet(entry)?(this.onChipText?(this.onChipText(this, option, entry)):entry.title):option;
                let avatar = null;
                if(isSet(entry)&&isSet(entry.avatar)) {
                  avatar = React.createElement(Avatar, {src: entry.avatar});
                } else if(isSet(entry)&&isSet(entry.icon)) {
                  if(isSet(window[entry.icon])) {
                    avatar = React.createElement(Avatar, {}, React.createElement(window[entry.icon]));
                  }
                }
                let chiptext = label;
                if(label instanceof Array) {
                  chiptext = label[0];
                  label = React.createElement(React.Fragment, {}, [
                    React.createElement(Typography, {className: classes.masterLabel, component: 'span'}, label[0]),
                    React.createElement(Typography, {className: classes.subLabel, component: 'span', variant: 'subtitle2'}, label[1]),
                  ]);
                }
                let deleteicon = React.createElement(this.removeChip, {theme: props.theme, option: option, entry: entry});
                rendervalue.push(React.createElement(this.createSimpleHint, {key: this.getKey('hint'), theme: props.theme, hint: chiptext}, React.createElement(Chip, {
                  variant: "filled",
                  label: label,
                  avatar: avatar,
                  size: 'small',
                  deleteIcon: deleteicon,
                }))); 
              }
              return React.createElement(React.Fragment, {}, rendervalue);
            } else {
              let index = this.options.indexOfId(value);
              if(index!=-1) {
                return processitems([this.options[index]], true);
              }
              return null;
            }
          },
          onOpen: (e) => {
            if(e.target==e.currentTarget || (e.target.nodeName == 'SPAN')) {
              setOpened(true);
            }
            var evt = new MouseEvent("click", e.nativeEvent);
            e.target.dispatchEvent(evt);
          },
          onClose: (e) => {
            setOpened(false);
          },
          onChange: (e) => {this.value = e.target.value; if(this.onChange) this.onChange(this, props); setUpdated(!updated);},
          multiple: this.multiple,
          disabled: this.disabled,
        }, items);
        return React.createElement(ThemeProvider, {key: this.getKey('root'), theme: props.theme}, React.createElement(FormControl, {className: ((this.parent instanceof widgets.section) && (this.parent.itemsalign==null))?classes.controlinline:classes.control, style: {flexGrow: flexGrow, width: isSet(props.width)?props.width:null}, fullWidth: ((this.parent instanceof baseWidget) && (this.parent.itemsalign!==null))}, [this.label, this.select, this.hint]));
      } else {
        let processitems = (items, group) => {
          let result = [];
          for(let i in items) {
            if(!isSet(items[i].value)) {
              result.push(_extends(items[i], {group: isSet(group)?group.id:null, groupTitle: isSet(group)?group.title:''}));
            } else {
              result = result.concat(processitems(items[i].value, items[i]));
            }
          }
          return result;
        }
        let items = processitems(this.options, null);
        const filterOptions = (options, params) => {
          let filtered = [];
          let stringoptions = null;
          for(let i in options) {
            if(filtered.length>=50) break;
            if(this.onCompareOptions) {
              if(this.onCompareOptions(this, false, options, params)) filtered.push(options[i]);
            } else {
              if(typeof options[i] == 'string') {
                stringoptions = true;
                if(options[i].toLowerCase().indexOf(params.inputValue)!=-1) {
                  filtered.push(options[i]);
                }
              } else {
                stringoptions = false;
                if((options[i].id.toLowerCase().indexOf(params.inputValue)!=-1)||(options[i].title.toLowerCase().indexOf(params.inputValue)!=-1)) {
                  filtered.push(options[i]);
                }
              }
            }
          }

          // Suggest the creation of a new value
          if((this.dialogview||this.onAppend)&&params.inputValue !== '') {
            let exists = false;
            if(this.onCompareOptions) {
              if(this.onCompareOptions(this, true, options, params)) exists = true;
            } else {
              if(stringoptions) {
                if(options[i].toLowerCase()==params.inputValue.toLowerCase()) {
                  exists = true;
                }
              } else {
                for(let i in options) {
                  if((options[i].id.toLowerCase()==params.inputValue.toLowerCase())||(options[i].id.toLowerCase()==params.inputValue.toLowerCase())) {
                    exists = true;
                    break;
                  }
                }
              }
            }
            if(!exists) {
              filtered.push({
                inputValue: params.inputValue,
                id: null,
                title: _('Добавить "{0}"').format(params.inputValue),
                input: params.inputValue,
                readonly: true,
              });
            }
          }

          return filtered;        
        }
        this.select = React.createElement(Autocomplete, {
          key: this.getKey('select'), 
          color: this.color,
          id: this.getID(),
          value: this.value,
          onChange: async (e, v) => {
            if(this.multiple) {
              if(this.onSelect) {
                let newvalues = v.filter((n) => {
                  return this.value.indexOf(n) === -1;
                });
                let oldvalues = this.value.filter((n) => {
                  return v.indexOf(n) === -1;
                });
                for(let i in newvalues) {
                  if(newvalues[i].id===null) {
                    if(this.onAppend) {
                      this.onAppend(this, newvalues[i]);
                    } else if(this.dialogview!==null) {
                      this.__proto__.views[this.dialogview].view.add();
                      this.__proto__.views[this.dialogview].show();
                    }
                  } else {
                    let entry = await this.onSelect(this, newvalues[i], 'add');
                    if(entry === true) {
                      this.value.push(newvalues[i].id);
                    } else if((typeof entry == 'object')&&isSet(entry.id)) {
                      this.value.push(entry);
                    }
                  }
                }
                for(let i in oldvalues) {
                  let entry = await this.onSelect(this, oldvalues[i], 'remove');
                  if(entry === true) {
                    this.value.splice(this.value.indexOf(oldvalues[i]),1);
                  } else if((typeof entry == 'object')&&isSet(entry.id)) {
                    this.value.splice(this.value.indexOfId(entry.id),1);
                  }
                }
              } else {
                let newvalues = v.filter((n) => {
                  return this.value.indexOf(n) === -1;
                });
                let oldvalues = this.value.filter((n) => {
                  return v.indexOf(n) === -1;
                });
                for(let i in newvalues) {
                  if((newvalues[i]==null)||((typeof newvalues[i].id == 'object')&&(newvalues[i].id===null))) {
                    if(this.onAppend) {
                      this.onAppend(this, newvalues[i]);
                    } else if(this.dialogview!==null) {
                      this.__proto__.views[this.dialogview].view.add();
                      this.__proto__.views[this.dialogview].view.setValue({title: newvalues[i].input});
                      this.__proto__.views[this.dialogview].show();
                    }
                  } else {
                    if(isSet(newvalues[i].id)) {
                      this.value.push(newvalues[i].id);
                    } else {
                      this.value.push(newvalues[i]);
                    }
                  }
                }
                for(let i in oldvalues) {
                  this.value.splice(this.value.indexOf(oldvalues[i]),1);
                }
              }
            } else {
              if((v!==null)&&(typeof v == 'object')&&(v.id == null)) {
                if(this.onAppend) {
                  this.onAppend(this, newvalues[i]);
                } else if(this.dialogview!==null) {
                  this.__proto__.views[this.dialogview].view.add();
                  this.__proto__.views[this.dialogview].view.setValue({title: v.input});
                  this.__proto__.views[this.dialogview].show();
                }
                return;
              }
              this.value = isSet(v)?v.id:null;
            }
            if(this.onChange) this.onChange(this, props);
            setUpdated(!updated);
          },
          multiple: this.multiple,
          disabled: this.disabled,
          autoComplete: true,
          autoHighlight: true,
          clearText: _('Сбросить'),
          closeText: _('Закрыть'),
          loadingText: _('Загрузка...'),
          noOptionsText: _('Список пуст'),
          openText: _('Открыть'),
          options: items,
          openOnFocus: true,
          placeholder: this.placeholder,
          filterOptions: filterOptions,
          filterSelectedOptions: this.multiple,
          freeSolo: false,
          classes: {
            paper: fullScreen?classes.fullscreenPaper:null,
            listbox: fullScreen?classes.fullscreenList:null,
          },
          renderTags: (value, getTagProps) => {
            return value.map((option, index) => {
              let entry = (typeof option == 'object')?(items[items.indexOfId(option.id)]):(items[items.indexOfId(option)]);
              let label = entry?(this.onChipText?(this.onChipText(this, option, entry)):entry.title):((typeof option == 'object')?option.id:option);
              let tagprops = getTagProps(index);
              let avatar = null;
              if(isSet(entry)&&isSet(entry.avatar)) {
                avatar = React.createElement(Avatar, {src: entry.avatar});
              } else if(isSet(entry)&&isSet(entry.icon)) {
                if(isSet(window[entry.icon])) {
                  avatar = React.createElement(Avatar, {}, React.createElement(window[entry.icon]));
                }
              }
              let deleteicon = null;
              let chiptext = label;
              if(label instanceof Array) {
                chiptext = label[0];
                label = React.createElement(React.Fragment, {}, [
                  React.createElement(Typography, {className: classes.masterLabel, component: 'span'}, label[0]),
                  React.createElement(Typography, {className: classes.subLabel, component: 'span', variant: 'subtitle2'}, label[1]),
                ]);
              }
              deleteicon = React.createElement(this.removeChip, {theme: props.theme, option: option, entry: entry});
              return React.createElement(this.createSimpleHint, {key: this.getKey('hint'), theme: props.theme, hint: chiptext}, React.createElement(Chip, _extends(tagprops, {
                variant: "filled",
                label: label,
                avatar: avatar,
                size: 'large',
                deleteIcon: deleteicon,
              }))); 
            });
          },
          PopperComponent: popperWidth,
          groupBy: (entry) => entry.groupTitle,
          renderOption: (params, option) => {
            if(this.onOptionVisible) {
              if(!this.onOptionVisible(this, option)) return null;
            } else {
              if(this.multiple) {
                if(this.value.length>0) {
                  if(typeof this.value[0] == 'string') {
                    if(this.value.indexOf(option.id)!==-1) return null;
                  } else {
                    if(this.value.indexOfId(option.id)!==-1) return null;
                  }
                }
              } else {
                if(this.value == option.id) return null;
              }
            }
            let label = this.onOptionText?(this.onOptionText(this, option, option)):option.title;
            let avatar = null;
            let action = null;
            let labelstyle = null;
            
            if(isSet(option.avatar)) {
              avatar = React.createElement(ListItemAvatar, {}, React.createElement(Avatar, {src: option.avatar}));
            } else if(isSet(option.icon)) {
              if(isSet(window[option.icon])) {
                avatar = React.createElement(ListItemAvatar, {}, React.createElement(Avatar, {}, React.createElement(window[option.icon])));
              }
            }
            if(!(((this.dialogview==null)&&(this.onEdit==null))||(isSet(option.readonly)&&(option.readonly)))) {
              action = React.createElement(ListItemSecondaryAction, {}, React.createElement(IconButton, {'aria-label': 'edit', onClick: () => {
                if(this.onEdit!==null) {
                  this.onEdit(this, option, option);
                } else {
                  this.__proto__.views[this.dialogview].view.load(option.id);
                  this.__proto__.views[this.dialogview].show();
                }
              }}, React.createElement(EditSharpIcon)));
              labelstyle = {paddingRight: props.theme.spacing(7)};
            }
            if(!((this.onDelete==null)||(isSet(option.readonly)&&(option.readonly)))) {
              action = React.createElement(ListItemSecondaryAction, {}, React.createElement(IconButton, {'aria-label': 'delete', onClick: () => {
                if(this.onDelete!==null) {
                  this.onDelete(this, option);
                } else {
                  this.__proto__.views[this.dialogview].view.remove(option.id, option.title);
                }
              }}, React.createElement(DeleteSharpIcon)));
              labelstyle = {paddingRight: props.theme.spacing(7)};
            }
            if(label instanceof Array) {
              if(label.length == 3) {
                label = React.createElement(ListItemText, {style: labelstyle, primary: label[0], secondary: React.createElement(
                  React.Fragment, {}, [
                    React.createElement(Typography, {component: 'p', variant: 'body2', color: 'textPrimary'}, label[1]),
                    label[2]
                  ]
                )});
              } else {
                label = React.createElement(ListItemText, {style: labelstyle, primary: label[0], secondary: label[1]});
              }
            } else {
              label = React.createElement(ListItemText, {style: labelstyle, primary: label});
            }
            return React.createElement(MenuItem, _extends(params, {value: option.id, alignItems: 'flex-start'}), [avatar, label, action]);
          },
          ListboxComponent: this.minidialog.hidden?null:embeddedForm,
          getOptionLabel: (entry) => { 
            if(typeof entry !== 'object') {
              let index = items.indexOfId(entry);
              if(index != -1) return items[index].title;
              return entry;
            }
            return entry.title;
          },
          getOptionDisabled: (entry) => (isSet(entry.disabled)?entry.disabled:false),
          renderInput: (params) => {
            return React.createElement(TextField, _extends(params, {
              label: this.labeltext,
              className: ((this.parent instanceof widgets.section) && (this.parent.itemsalign==null))?classes.controlinline:classes.control,
              style: {flexGrow: flexGrow, width: isSet(props.width)?props.width:null},
              fullWidth: ((this.parent instanceof baseWidget) && (this.parent.itemsalign!==null)),
              variant: this.mode,
            }));
          }
        });
        return React.createElement(ThemeProvider, {key: this.getKey('root'), theme: props.theme}, this.select);
      }
    }
  }

  removeChip(props) {
    let baseprops = {};
    if(isSet(props.theme)) baseprops.theme = props.theme;
    if(isSet(props.className)) baseprops.className = props.className;
    let cancel = React.createElement(CancelIcon, _extends({onClick: () => {
      if(this.onDelete) {
        this.onDelete(this, props.option, props.entry);
      } else {
        this.value.splice(this.value.indexOf(props.option), 1);
      }
      this.setUpdated(!this.updated);
    }}, baseprops));
    if(((this.dialogview==null)&&(this.onEdit==null))||(isSet(props.entry)&&isSet(props.entry.readonly)&&(props.entry.readonly))) return cancel;
    return React.createElement(React.Fragment, {}, [
      React.createElement(EditSharpIcon, _extends({onClick: () => {
        if(this.onEdit!==null) {
          this.onEdit(this, props.option, props.entry);
        } else {
          this.__proto__.views[this.dialogview].view.load(props.entry.id);
          this.__proto__.views[this.dialogview].show();
        }
      }}, baseprops)),
      cancel,
    ]);
  }

  setValue(avalue) {
    if((!isSet(avalue)) || (avalue == null)) avalue = {};
    if(typeof avalue == 'string') {
      if(avalue.trim()=='') avalue={};
      else avalue = {value: avalue};
    } else if(typeof avalue == 'number') {
      avalue = {value: avalue.toString()};
    } else if((typeof avalue == 'object') && (avalue instanceof Array)) {
      avalue = {value: avalue};
    }
    if(isSet(avalue.minlines)) {
      this.minlines = avalue.minlines;
    }
    if(isSet(avalue.options)) {
      this.options = avalue.options;
      if(!this.hiddenreal) {
        this.hidden = (this.options.length<this.minlines)&&!(isSet(this.options[0])&&isSet(this.options[0].value));
      }
    }
    if(isSet(avalue.multiple)) {
      if(avalue.multiple) {
        this.multiple = true;
        if(!(this.value instanceof Array)) {
          this.value = [this.value];
        }
      } else {
        if(this.value instanceof Array) {
          this.value = this.value.slice(0,1);
        }
        this.multiple = false;
      }
    }
    if(isSet(avalue.default)) {
      this.defaultvalue = avalue.default;
      if(!isSet(avalue.value)) avalue.value = false;
    }
    if((this.defaultvalue!==null)&&isSet(avalue.value)&&(avalue.value === false)) {
      avalue.value = this.defaultvalue;
    }
    if(isSet(avalue.value)) {
      if(this.multiple) {
        if(!(avalue.value instanceof Array)) {
          if(!(avalue.value instanceof Object)) {
            this.value = [avalue.value];
          } else {
            this.value = [];
            for(let i in avalue.value) {
              if(avalue.value[i]) this.value.push(i);
            }
          }
        } else {
          this.value = avalue.value;
        }
      } else {
        if(avalue.value instanceof Array) {
          this.value = avalue.value.slice(0,1);
        } else {
          if(!(avalue.value instanceof Object)) {
            this.value = avalue.value;
          }
        }
      }
      
    }
    if(typeof avalue.icon == 'string') {
      this.icon = avalue.icon;
    }
    if(typeof avalue.color == 'string') {
      this.color = avalue.color;
    }
    if(typeof avalue.placeholder == 'string') {
      this.placeholder = avalue.placeholder;
    }
    if(typeof avalue.mode == 'string') {
      if(avalue.mode == 'outlined') {
        this.mode = 'outlined';
      } else 
      if(avalue.mode == 'filled') {
        this.mode = 'filled';
      } else this.mode = 'standard';
    }
    if(typeof avalue.view == 'string') {
      this.dialogview = avalue.view;
      if(!isSet(this.__proto__.views[this.dialogview])) {
        this.__proto__.views[this.dialogview] = new widgets.dialog(dialogcontent);
        require(this.dialogview, this.__proto__.views[this.dialogview]).then(async () => {
          this.__proto__.views[this.dialogview].view.onUpdate = async (sender, action, data) => {
            this.options = await sender.getItems();
            if(this.multiple) {
              if(action=='remove') {
                let index = this.value.indexOf(data.old_id);
                if(index!==-1) this.value.splice(index, 1);
              } else {
                if(this.value.indexOf(data.id)===-1) this.value.push(data.id);
              }
            } else {
              if(action=='remove') {
                this.value = this.defaultvalue;
              } else {
                this.value = data.id;
              }
            }
            this.redraw();
          }
          if(!isSet(avalue.options)) {
            this.options = await this.__proto__.views[this.dialogview].view.getItems();
            this.redraw();
          }
        });
      }
    }
    if(typeof avalue.dialog == 'string') {
      require(avalue.dialog, this.minidialog);
      this.minidialog.hide();
    }
    if(isSet(avalue.required)) {
      if(avalue.required) {
        this.required = true;
      } else {
        this.required = false;
      }
    }
    if(isSet(avalue.readonly)) {
      if(avalue.readonly) {
        this.readonly = true;
      } else {
        this.readonly = false;
      }
    }
    if(isSet(avalue.error)) {
      if(avalue.error) {
        this.error = true;
      } else {
        this.error = false;
      }
    }
    this.redraw();
    return true;
  }

  getValue() {
    if(this.value=='') return null;
    return this.value;
  }

  show() {
    this.hiddenreal = false;
    this.hidden = (this.options.length<this.minlines)&&!(isSet(this.options[0])&&isSet(this.options[0].value));
    this.render();
  }

  hide() {
    this.hiddenreal = true;
    this.hidden = true;
    this.render();
  }

}

widgets.list=class listWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.basestyle = {
      control: {
        margin: maintheme.spacing(1),
        minWidth: 120,
        overflowY: 'auto',
      },      
      pad: {
        width: maintheme.spacing(2),
        display: 'inline-flex',
      },      
      listSection: {
        backgroundColor: 'inherit',
      },
      listHeader: {
        backgroundColor: 'inherit',
        padding: 0,
      },
      nested: {
        paddingLeft: maintheme.spacing(4) + ' !important',
      },
      arrow: {
        position: 'absolute',
        right: 0,
      },
    };
    this.defaultvalue = null;
    this.expand = null;
    this.value = null;
    this.lines = 5;
    this.options = [];
    this.tree = false;
    this.multiple = false;
    this.checkbox = false;
    this.switch = false;
    this.avatars = false;
    this.sortable = false; //TODO: Darg&Drop elements within groups
    this.onEdit=null;
    this.onDelete=null;
    this.onChange=null;
    this.onDropFiles=null;
    this.renderLock();
    if(isSet(data)) this.setValue(data);
    this.renderUnlock();
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const useStyles = makeStyles(this.basestyle);
    const classes = useStyles();
    const [updated, setUpdated] = React.useState(false);
    let hassubitems = false;
    let processitems = (items, issubmenu) => {
      const addItem = (items, i, provided) => {
        let subitems = [];
        let itemicon = null;
        let checkbox = null;
        let checkboxfirst = (!this.switch) && !(this.avatars || this.onEdit || this.onDelete || (isSet(items[i].icon)));
        let isActive = 0;
        let canShow = false;
        if(isSet(items[i].value)) {
          if(!isSet(items[i].open)) items[i].open = false;
          if(!isSet(items[i].id)) items[i].id = Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
          [subitems, isActive] = processitems(items[i].value, true);
          if(this.multiple||this.checkbox) {
            isActive = (this.value.indexOf(items[i].id)!==-1)?1:isActive;
          }
          if(subitems.length) canShow = true;
        } else {
          if(!isSet(items[i].id)) items[i].id = items[i].link;
          if(this.multiple||this.checkbox) {
            isActive = (this.value.indexOf(items[i].id)!==-1)?1:0;
          } else {
            isActive = (items[i].id == this.value)?1:0;
          }
          canShow = true;
        }
        if(canShow&&isActive) hasActive++;
        if(canShow) {
          if(this.checkbox) {
            if(this.switch) {
              checkbox = React.createElement(Switch, {
                key: this.getKey('checkbox'. items[i].id),
                edge: 'end',
                checked: isActive,
                disabled: this.disabled,
                onChange: (e) => {
                  let index = this.value.indexof(items[i].id);
                  if(index==-1) {
                    this.value.push(items[i].id);
                  } else {
                    this.value.splice(index, 1);
                  };
                  if(this.onChange) this.onChange(this, items[i], this.value);
                  setUpdated(!updated);
                },
                inputProps: {'aria-labelledby': items[i].id}
              });
            } else {
              checkbox = React.createElement(Checkbox, {
                key: this.getKey('checkbox', items[i].id),
                edge: checkboxfirst?'start':'end',
                checked: isActive==1,
                indeterminate: isActive==2,
                tabIndex: -1,
                disabled: this.disabled,
                disableRipple: true,
                onChange: (e) => {
                  let index = this.value.indexOf(items[i].id);
                  if(index==-1) {
                    this.value.push(items[i].id);
                  } else {
                    this.value.splice(index, 1);
                  };
                  if(this.onChange) this.onChange(this, items[i], this.value);
                  setUpdated(!updated);
                },
                inputProps: {'aria-labelledby': items[i].id}
              });
            }
            isActive = 0;
          }
          if(this.avatars) {
            let icon = null;
            let style = null;
            if((isSet(items[i].color))) {
              style = {
                color: props.theme.palette.getContrastText(items[i].color),
                backgroundColor: items[i].color,
              };
            }
            if((isSet(items[i].image))) {
              icon = React.createElement(Avatar, {alt: items[i].title, src: items[i].image});
            } else if((!isSet(items[i].icon)) || (!isSet(window[items[i].icon]))) {
              icon = React.createElement(Avatar, {style: style}, items[i].title.substr(0,1).toUpperCase());
            } else {
              icon = React.createElement(Avatar, {style: style}, React.createElement(window[items[i].icon]));
            }
            itemicon = React.createElement(ListItemAvatar, {key: this.getKey('itemicon', items[i].id)}, icon);
          } else {
            let icon = null;
            if((!isSet(items[i].icon)) || (!isSet(window[items[i].icon]))) {
              if(checkbox&&checkboxfirst) {
                icon = checkbox;
              } else {
                icon = React.createElement(createSvgIcon(
                  React.createElement("text", {x: 0, y: 18, className: classes.icontext}, [items[i].title.substr(0,1).toUpperCase()])
                , ('MainMenuItem'+items[i].title.substr(0,1).toUpperCase()) ));
              }
            } else {
              icon = React.createElement(window[items[i].icon]);
            }
            itemicon = React.createElement(ListItemIcon, {key: this.getKey('itemicon', items[i].id)}, icon);
          }
          if(!isSet(items[i].subtitle)) items[i].subtitle = null;
          let itemtext = React.createElement(ListItemText, {key: this.getKey('itemtext', items[i].id), id: items[i].id, primary: items[i].title, secondary: items[i].subtitle});
          //TODO: Custom actions
          let actions = [];
          if(checkbox && !checkboxfirst) {
            actions.push(checkbox);
          }
          if(!this.readonly) {
            if(!(isSet(items[i].readonly)&&(items[i].readonly))) {
              if(this.onEdit) {
                actions.push(React.createElement(IconButton, {key: this.getKey('editbtn', items[i].id), disabled: this.disabled, edge: 'end', onClick: () => {if(this.onEdit) this.onEdit(this, items[i], props);}}, React.createElement(EditSharpIcon)));
              }
              if(this.onDelete) {
                actions.push(React.createElement(IconButton, {key: this.getKey('removebtn', items[i].id), disabled: this.disabled, edge: 'end', onClick: () => {if(this.onDelete) this.onDelete(this, items[i], props);}}, React.createElement(DeleteSharpIcon)));
              }
            }
            if(this.sortable) {
              actions.push(React.createElement(ListItemIcon, _extends({key: this.getKey('drag', items[i].id), disabled: this.disabled, edge: 'end', onClick: () => {}}, provided.dragHandleProps), React.createElement(DragHandleSharpIcon)));
            }
          }
          let itemactions = React.createElement(ListItemSecondaryAction, {key: this.getKey('actions', items[i].id), style: {paddingRight: isSet(items[i].open)?props.theme.spacing(3):'0'}}, actions);
          if(subitems.length) {
            if(this.tree) {
              let expand = null;
              if(isActive) items[i].open = true;
              if(items[i].open) {
                expand = React.createElement(ExpandLessIcon, {key: this.getKey('expand', items[i].id), className: classes.arrow});
              } else {
                expand = React.createElement(ExpandMoreIcon, {key: this.getKey('expand', items[i].id), className: classes.arrow});
              }
              return React.createElement(React.Fragment, {}, [React.createElement(ListItem, {key: this.getKey('item', items[i].id), disabled: this.disabled, button: true, selected: isActive, key: items[i].id, onClick: (e) => {
                if(e.target==e.currentTarget || e.target.nodeName=='SPAN' || e.target.classList.contains(classes.arrow) || e.target.parentNode.classList.contains(classes.arrow)) {
                  items[i].open = !items[i].open;
                  setUpdated(!updated);
                }
              }}, [itemicon, itemtext, itemactions, expand]),
                    React.createElement(Collapse, {key: this.getKey('subitems', items[i].id), in: items[i].open, timeout: 'auto', unmountOnExit: true}, React.createElement(List, {theme: props.theme, component: "div", disabledPadding: true, className: classes.nested}, subitems))]);
            } else {
              return React.createElement('li', {key: this.getKey('subitems', items[i].id), className: classes.listSection}, 
                React.createElement('ul', {className: classes.listHeader}, [React.createElement(ListSubHeader, {key: this.getKey('subheader', items[i].id)}, items[i].title)].concat(subitems))
              );
            }
          } else {
            if(this.sortable) {
              items[i].ref = React.createRef();
              return React.createElement(ListItem, {ref: provided.innerRef, disabled: this.disabled, ContainerProps: provided.draggableProps, key: this.getKey('item', items[i].id), button: true, selected: isActive, onClick: (e) => {
                if(!this.checkbox) {
                  if(this.multiple) {
                    let index = this.value.indexof(items[i].id);
                    if(index==-1) {
                      this.value.push(items[i].id);
                    } else {
                      this.value.splice(index, 1);
                    };
                  } else {
                    this.value = items[i].id;
                  }
                  if(this.onChange) this.onChange(this, items[i], this.value);
                }
                setUpdated(!updated);
              }}, [itemicon, itemtext, itemactions]);
            } else {
              return React.createElement(ListItem, {key: this.getKey('item', items[i].id), disabled: this.disabled, button: true, selected: isActive, onClick: (e) => {
                if(!this.checkbox) {
                  if(this.multiple) {
                    let index = this.value.indexof(items[i].id);
                    if(index==-1) {
                      this.value.push(items[i].id);
                    } else {
                      this.value.splice(index, 1);
                    };
                  } else {
                    this.value = items[i].id;
                  }
                  if(this.onChange) this.onChange(this, items[i], this.value);
                }
                setUpdated(!updated);
              }}, [itemicon, itemtext, itemactions]);
            }
          }
        } else {
          return null;
        }
      }
      let listitems = [];
      let hasActive = 0;
      if(issubmenu) hassubitems = true;
      for(let i in items) {
        if(this.sortable) {
          listitems.push(React.createElement(ReactBeautifulDnd.Draggable, {draggableId: items[i].id, key: this.getKey('draggable', items[i].id), index: (Number.parseInt(i))}, (provided) => addItem(items, i, provided)));
        } else {
          let item = addItem(items, i, null);
          if(item) listitems.push(item);
        }
      }
      if(hasActive>0) {
        hasActive = 2;
      }
      return [listitems, hasActive];
    }
    let [listitems, hasActive] = processitems(this.options, false);
    let labelcontrols = [];
    if(this.labeltext) {
      labelcontrols.push(this.labeltext);
      if(this.children.length>0) labelcontrols.push(React.createElement(Box, {key: this.getKey('pad'), component: 'span', theme: props.theme, className: classes.pad}));
      for(let i in this.children) {
        if(!this.children[i].hidden) {
          labelcontrols.push(React.createElement(this.children[i].prepare, {key: this.children[i].getID(), disabled: this.disabled, theme: props.theme}));
        }
      }
      this.label = React.createElement(ListSubheader, {key: this.getKey('label')}, labelcontrols);
    } else {
      if(hassubitems) {
        this.label = React.createElement('li', {key: this.getKey('label')});
      } else {
        this.label = null;
      }
    }
    if(this.sortable) {
      return React.createElement(ThemeProvider, {key: this.getKey('root'), theme: props.theme}, React.createElement(ReactBeautifulDnd.DragDropContext, {onDragEnd: ({source, destination}) => {
        this.options.move(source.index, destination.index);
        let srcidx = this.value.indexOf(this.options[source.index].id);
        let dstidx = this.value.indexOf(this.options[destination.index].id);
        if((srcidx!=-1)&&(dstidx!=-1)) {
          this.value.move(srcidx, dstidx);
        }
      }}, [this.label, React.createElement(ReactBeautifulDnd.Droppable, {key: this.getKey('droppable'), droppableId: this.getID()}, (provided) => 
        React.createElement(List, {component: 'ul', fullWidth: this.expand, key: this.getKey('list'), ref: (ref) => {provided.innerRef(ref); provided.innerRef.current = ref; if(ref) {ref.ondragover = this.onDropFiles?((ev) => {ev.preventDefault();}):null; ref.ondrop = this.onDropFiles?this.dropHandler:null}}, className: classes.control, style: {height: this.lines?props.theme.spacing(9*this.lines-1):'auto'}}, 
        [listitems, provided.placeholder])
      )]));
    } else {
      return React.createElement(ThemeProvider, {key: this.getKey('root'), theme: props.theme}, [this.label, React.createElement(List, {component: 'ul', fullWidth: this.expand, key: this.getKey('list'), ref: (ref) => {if(ref) {ref.ondragover = this.onDropFiles?((ev) => {ev.preventDefault();}):null; ref.ondrop = this.onDropFiles?this.dropHandler:null}}, className: classes.control, style: {height: props.theme.spacing(9*this.lines-1)}}, 
        listitems
      )]);
    }
  }

  dropHandler(ev) {
    let files = [];
    ev.preventDefault();
    
    if (ev.dataTransfer.items) {
      // Use DataTransferItemList interface to access the file(s)
      for (var i = 0; i < ev.dataTransfer.items.length; i++) {
        // If dropped items aren't files, reject them
        if (ev.dataTransfer.items[i].kind === 'file') {
          files.push(ev.dataTransfer.items[i].getAsFile());
        }
      }
    } else {
      // Use DataTransfer interface to access the file(s)
      for (var i = 0; i < ev.dataTransfer.files.length; i++) {
        files.push(ev.dataTransfer.files[i]);
      }
    }
    this.onDropFiles(this.avatars, files);
  }

  setValue(avalue) {
    if((!isSet(avalue)) || (avalue == null)) avalue = {};
    if(typeof avalue == 'string') {
      if(avalue.trim()=='') avalue={};
      else avalue = {value: avalue};
    } else if(typeof avalue == 'number') {
      avalue = {value: avalue.toString()};
    } else if((typeof avalue == 'object') && (avalue instanceof Array)) {
      if(this.multiple||this.checkbox) {
        avalue = {value: avalue};
      } else {
        avalue = {options: avalue};
      }
    }
    if(isSet(avalue.options)) this.options = avalue.options;
    if(isSet(avalue.multiple)) {
      if(avalue.multiple) {
        if(!(this.value instanceof Array)) {
          if(this.value !== null) {
            this.value = [this.value];
          } else {
            this.value = [];
          }
        }
        this.multiple = true;
      } else {
        if(!this.checkbox) {
          if(this.value instanceof Array) {
            if(this.value.length==0) {
              this.value = null;
            } else {
              this.value = this.value[0];
            }
          }
        }
        this.multiple = false;
      }
    }
    if(isSet(avalue.default)) {
      this.defaultvalue = avalue.default;
      if(!isSet(avalue.value)) avalue.value = false;
    }
    if((this.defaultvalue!==null)&&isSet(avalue.value)&&(avalue.value === false)) {
      avalue.value = this.defaultvalue;
    }
    if(isSet(avalue.value)) {
      if(this.multiple||this.checkbox) {
        if(!(avalue.value instanceof Array)) {
          if(avalue.value !== null) {
            this.value = [avalue.value];
          } else {
            this.value = [];
          }
        } else {
          this.value = avalue.value;
        }
      } else {
        if(avalue.value instanceof Array) {
          if(avalue.value.length==0) {
            this.value = null;
          } else {
            this.value = avalue.value[0];
          }
        } else {
          this.value = avalue.value;
        }
      }
    }
    if(isSet(avalue.lines)) {
      this.lines = avalue.lines;
    }
    if(isSet(avalue.expand)) {
      if(avalue.expand) {
        this.expand = true;
      } else {
        this.expand = false;
      }
    }
    if(isSet(avalue.tree)) {
      if(avalue.tree) {
        this.tree = true;
      } else {
        this.tree = false;
      }
    }
    if(isSet(avalue.avatars)) {
      if(avalue.avatars) {
        this.avatars = true;
      } else {
        this.avatars = false;
      }
    }
    if(isSet(avalue.switch)) {
      if(avalue.switch) {
        this.switch = true;
        avalue.checkbox = true;
      } else {
        this.switch = false;
      }
    }
    if(isSet(avalue.checkbox)) {
      if(avalue.checkbox) {
        if(!(this.value instanceof Array)) {
          if(this.value !== null) {
            this.value = [this.value];
          } else {
            this.value = [];
          }
        }
        this.checkbox = true;
      } else {
        if(!this.multiple) {
          if(this.value instanceof Array) {
            if(this.value.length==0) {
              this.value = null;
            } else {
              this.value = this.value[0];
            }
          }
        }
        this.checkbox = false;
      }
    }
    if(isSet(avalue.sortable)) {
      if(avalue.sortable) {
        this.sortable = true;
      } else {
        this.sortable = false;
      }
    }
    this.redraw();
    return true;
  }

  getValue() {
    return this.value;
  }

}

/**
  * onDelete example:<br>
  * obj.onDelete = function(sender, item) { if(!isSet(item.removed)) { showdialog('dfdfd','dfdf','question',['Yes','No'],function(btn) {if(btn=='Yes') { item.removed=true; if( sender.listRemove(sender.list, item)) sender.list.delete(item.id); }}); return false; } else { return true; } }
*/
widgets.collection=class collectionWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.basestyle = {
      box: {
        '& > div:not(:last-child)': {
          paddingBottom: maintheme.spacing(2),
        },
        paddingBottom: maintheme.spacing(2),
      }
    };
    this.setValue.calls = [];
    this.defaultvalue = null;
    this.data = null;
    this.options = [true];
    this.value = [];
    this.entries = [];
    this.selectcontainter = null;
    this.select = null;
    this.entry = null;
    this.onAdd = null;
    this.onDelete = null;
    this.onChange = null;
    this.renderLock();
    if((isSet(data)) && data ) this.setValue(data);
    this.renderUnlock();
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const useStyles = makeStyles(this.basestyle);
    const classes = useStyles();
    let flexGrow = null;
    if(isSet(props.flexGrow)) flexGrow = props.flexGrow;
    let items = [];
    if(this.labeltext) {
      if(this.hinttext) {
        this.hint = React.createElement(this.createChipHint, {theme: props.theme}, this.hinttext);
        this.label = React.createElement(Typography, {theme: props.theme, key: this.getKey('label'), component: 'p'}, [this.labeltext, this.hint]);
      } else {
        this.label = React.createElement(Typography, {theme: props.theme, key: this.getKey('label'), component: 'p'}, this.labeltext);
      }
      items.push(this.label);
    }
    if(this.selectcontainter) {
      if(this.selectcontainter.itemsalign == null) {
        items.push(React.createElement(Box, {key: this.getKey('select'), display: 'flex'}, [React.createElement(this.selectcontainter.prepare, {theme: props.theme}), React.createElement(this.selectcontainter.btn.prepare, {theme: props.theme})]));
      } else {
        items.push(React.createElement(Box, {key: this.getKey('select'), display: 'flex'}, [React.createElement(this.selectcontainter.prepare, {theme: props.theme}), React.createElement(Grid, _extends({key: this.getID(), item: true}, this.selectcontainter.itemsalign), React.createElement(this.selectcontainter.btn.prepare, {theme: props.theme}))]));
      }
    }
    for(let i in this.entries) {
      if(!this.entries[i].hidden) {
        if(this.entries[i].itemsalign == null) {
          items.push(React.createElement(Box, {key: this.getKey('entry', i), display: 'flex'}, [React.createElement(this.entries[i].prepare, {theme: props.theme}), React.createElement(this.entries[i].btn.prepare, {theme: props.theme})]));
        } else {
          items.push(React.createElement(Box, {key: this.getKey('entry', i), display: 'flex'}, [React.createElement(this.entries[i].prepare, {theme: props.theme}), React.createElement(Grid, _extends({key: this.getID(), item: true}, this.entries[i].itemsalign), React.createElement(this.entries[i].btn.prepare, {theme: props.theme}))]));
        }
      }
    }
    return React.createElement(Box, {key: this.getKey('root'), className: classes.box, flexGrow: flexGrow}, items);
  }

  async createSelect(value) {
    var inputdiv = new widgets.section(null, {});
    inputdiv.parent = this;
    if(this.select) {
      await require(this.select, inputdiv, value);
    } else {
      inputdiv.data = new widgets.select(inputdiv, {search: false, options: this.options, value: value, inline: true});
    }
    inputdiv.btn = new widgets.iconbutton(null, {color: 'success', icon: 'AddSharpIcon'});
    inputdiv.btn.parent = inputdiv;
    inputdiv.btn.onClick = this.newEntry;
    return inputdiv;
  }

  async createEntry(value) {
    this.renderLock();
    var inputdiv = new widgets.section(null, {});
    inputdiv.parent = this;
    if((typeof value == 'string') && (this.options.length > 0)) {
      let id = this.options.indexOfId(value);
      if(id != -1) value = this.options[id];
    }
    if(this.entry) {
      await require(this.entry, inputdiv, value);
    } else {
      inputdiv.data = new widgets.input(inputdiv, {value: (typeof value == 'string')?value:value.title, inline: true});
      inputdiv.data.disable();
    }
    inputdiv.btn = new widgets.iconbutton(null, {color: 'error', icon: 'RemoveSharpIcon'});
    inputdiv.btn.parent = inputdiv;
    inputdiv.btn.onClick = this.removeEntry;
    this.renderUnlock();
    return inputdiv;
  }

  getEntry(sender) {
    let actiondata = null;
    if(isSet(sender.data)) actiondata = sender.data.getValue();
    else actiondata = sender.view.getValue();
    return actiondata;
  }

  removeEntry(sender) {
    let entry = this.entries.indexOf(sender.parent);
    if(entry != -1) {
      this.entries.splice(entry, 1);
    }
    this.redraw();
    return false;
  }
  
  async newEntry(sender) {
    var result = true;
    this.renderLock();
    var entry = null;
    if(sender.parent == this.selectcontainter) {
      entry = await this.createEntry(this.getEntry(sender.parent));
      if(this.select) {
        this.selectcontainter.view.clear();
      }
    } else {
      entry = await this.createEntry(null);
    }
    this.entries.push(entry);
    if(!(this.entry && !this.select)) {
      let option = null;
      if(this.options.length>0) option = this.options[0];
      if(isSet(sender.parent.data)) {
        sender.parent.data.setValue(option);
      } else {
        sender.parent.view.setValue(option);
      }
    }
    this.renderUnlock();
    this.redraw();
    return result;
  }

  preload() {
    this.loading = true;
    this.renderLock();
    for(let i in this.children) {
      this.children[i].preload();
    }
    if(this.selectcontainter) this.selectcontainter.preload();
    for(let i in this.entries) {
      this.entries[i].preload();
    }
    this.renderUnlock();
    this.redraw();
  }

  endload() {
    this.loading = true;
    this.renderLock();
    for(let i in this.children) {
      this.children[i].endload();
    }
    if(this.selectcontainter) this.selectcontainter.endload();
    for(let i in this.entries) {
      this.entries[i].endload();
    }
    this.renderUnlock();
    this.redraw();
  }

  setValue(avalue) {
    let promise = Promise.resolve({then: async resolve => {
      await Promise.all(this.setValue.calls);
      this.setValue.calls.push(promise);
      this.renderLock();
      if(typeof avalue == 'string') {
        avalue = {value: [avalue]};
      }
      if((typeof avalue == 'object')&&(avalue instanceof Array)) {
        avalue = {value: avalue};
      }
      if(isSet(avalue.default)) {
        this.defaultvalue = avalue.default;
      if(!isSet(avalue.value)) avalue.value = false;
      }
      if((this.defaultvalue!==null)&&isSet(avalue.value)&&(avalue.value === false)) {
        avalue.value = this.defaultvalue;
      }
      //Переводим объект в массив ассоциативный свойств и их значений
      if((typeof avalue.value == 'object')&&!(avalue.value instanceof Array)) {
        let newvalue = [];
        for(let i in avalue.value) {
          newvalue.push({key: i, value: avalue.value[i]});
        }
        avalue.value = newvalue;
      }
      if(isSet(avalue.entry)) this.entry = avalue.entry;
      if(isSet(avalue.select)) {
        this.select = avalue.select;
      }
      if((typeof avalue.data == 'object')) {
        this.data = avalue.data;
      }
      if(isSet(avalue.options)) {
        this.options = avalue.options;
        if(!this.entry || (this.entry && this.select)) {
          if(this.options.length>0) this.show();
          else this.hide();
        } else this.show();
      }
      if(isSet(avalue.select)) {
        this.selectcontainter = await this.createSelect(this.options);
      }
      if(isSet(avalue.value)) {
        if(this.entry && !this.select) this.show();
        this.entries = [];       
        let count = 0;
        for(var i in avalue.value) {
          this.entries.push(await this.createEntry(avalue.value[i]));
          count++;
        }
        if(this.entry && !this.select && (count==0)) {
          let option = null;
          if(this.options.length>0) option = this.options[0];
          this.entries.push(await this.createEntry(option));
        }
        if(this.entry && !this.select) {
          this.entries[0].btn.onClick = this.newEntry;
          this.entries[0].btn.icon = 'AddIcon';
          this.entries[0].btn.color = 'success';
        }
      }
      this.setValue.calls.splice(this.setValue.calls.indexOf(promise),1);
      this.renderUnlock();
      this.redraw();
      resolve(true);
    }});
    return true;
  }

  getValue() {
    let result = [];
    for(let i in this.entries) {
      result.push(this.getEntry(this.entries[i]));
    }
    return result;
  }
}

widgets.checkbox=class checkboxWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    if(!isSet(label)) label='';
    super(parent,data,label,hint);
    this.basestyle = {
      control: {
        margin: maintheme.spacing(3),
      },
    };
    this.color = 'secondary';
    this.three = false;
    this.defaultvalue = null;
    this.value = false;
    this.onChange=null;
    this.renderLock();
    if((isSet(data)) && data ) this.setValue(data);
    this.renderUnlock();
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const useStyles = makeStyles(this.basestyle);
    const classes = useStyles();
    if(this.hinttext) {
      this.hint = React.createElement(this.createChipHint, {key: this.getKey('hint'), theme: props.theme}, this.hinttext);
    } else {
      this.hint = null;
    }
    this.checkbox = React.createElement(Checkbox, {key: this.getKey('checkbox'), theme: props.theme, color: this.color, checked: this.value == true, indeterminate: this.value == null, disabled: this.disabled, onChange: (e) => {
      if(this.three) {
        if(this.value == true) this.value = false;
        else if(this.value == null) this.value = true;
        else this.value = null;
      } else {
        this.value = !this.value;
      }
      if(this.onChange) this.onChange(this, props);
      this.redraw();
    }});
    this.label = React.createElement(FormControlLabel, {key: this.getKey('label'), control: this.checkbox, label: React.createElement(React.Fragment, {}, [this.labeltext, this.hint])});
    return React.createElement(FormControl, {key: this.getKey('root'), component: 'fieldset', className: classes.control}, this.label);
  }

  setValue(avalue) {
    if(typeof avalue == 'string') {
      avalue = {value: avalue};
    }
    if(typeof avalue == 'boolean') {
      avalue = {value: avalue};
    }
    if(isSet(avalue.default)) {
      this.defaultvalue = avalue.default;
      if(!isSet(avalue.value)) avalue.value = false;
    }
    // if((this.defaultvalue!==null)&&isSet(avalue.value)&&(avalue.value === false)) {
    //   avalue.value = this.defaultvalue;
    // }
    if(avalue.value === null) {
      this.value = null;
      if(!isSet(avalue.three)) avalue.three=true;
    } else {
      if(typeof avalue.value == 'string') {
        this.value = (avalue.value == 'yes')||(avalue.value == 'on')||(avalue.value == 'true')||(avalue.value == '1');
      } else {
        this.value = (avalue.value == true) || (avalue.value == 1);
      }
    }
    if(isSet(avalue.three)) {
      if(avalue.three) {
        this.three = true;
      } else {
        this.three = false;
      }
    }
    if(typeof avalue.color == 'string') {
      this.color = avalue.color;
    }
    this.redraw();
    return true;
  }

  getValue() {
    return this.value;
  }

  reset() {
    if(this.three) {
      this.value = this.defaultvalue;
    } else {
      if(isSet(this.defaultvalue)) this.value = this.defaultvalue;
    }
    this.redraw();
  }
}

widgets.toggle=class toggleWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    if(!isSet(label)) label='';
    super(parent,data,label,hint);
    this.basestyle = {
      control: {
        margin: maintheme.spacing(3),
      },
    };
    this.defaultvalue = null;
    this.value = false;
    this.color = 'secondary';
    this.onChange=null;
    this.renderLock();
    if((isSet(data)) && data ) this.setValue(data);
    this.renderUnlock();
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const useStyles = makeStyles(this.basestyle);
    const classes = useStyles();
    if(this.labeltext&&this.hinttext) {
      this.hint = React.createElement(this.createChipHint, {key: this.getKey('hint'), theme: props.theme}, this.hinttext);
    } else {
      this.hint = null;
    }
    this.checkbox = React.createElement(Switch, {key: this.getKey('checkbox'), theme: props.theme, color: this.color, checked: this.value, disabled: this.disabled, onChange: (e) => {
      this.value = !this.value;
      if(this.onChange) this.onChange(this, props);
      this.redraw();
    }});
    if(this.labeltext) {
      this.label = React.createElement(FormControlLabel, {key: this.getKey('label'), control: this.checkbox, label: React.createElement(React.Fragment, {}, [this.labeltext, this.hint])});
    } else {
      this.label = React.createElement(this.createSimpleHint, {key: this.getKey('hint'), theme: props.theme, hint: this.hinttext}, React.createElement(FormControlLabel, {key: this.getKey('label'), control: this.checkbox, label: React.createElement(React.Fragment, {})}));
    }
    return React.createElement(FormControl, {key: this.getKey('root'), component: 'fieldset', className: classes.control}, this.label);
  }

  setValue(avalue) {
    if(typeof avalue == 'string') {
      avalue = {value: avalue};
    }
    if(typeof avalue == 'boolean') {
      avalue = {value: avalue};
    }
    if(isSet(avalue.default)) {
      this.defaultvalue = avalue.default;
      if(!isSet(avalue.value)) avalue.value = false;
    }
    // if((this.defaultvalue!==null)&&isSet(avalue.value)&&(avalue.value === false)) {
    //   avalue.value = this.defaultvalue;
    // }
    if(avalue.value==null) {
      this.value = null;
      if(!isSet(avalue.three)) avalue.three=true;
    } else {
      if(typeof avalue.value == 'string') {
        this.value = (avalue.value == 'yes')||(avalue.value == 'on')||(avalue.value == 'true')||(avalue.value == '1');
      } else {
        this.value = (avalue.value == true) || (avalue.value == 1);
      }
    }
    if(typeof avalue.color == 'string') {
      this.color = avalue.color;
    }
    this.redraw();
    return true;
  }

  getValue() {
    return this.value;
  }

  reset() {
    if(this.three) {
      this.value = this.defaultvalue;
    } else {
      if(isSet(this.defaultvalue)) this.value = this.defaultvalue;
    }
    this.redraw();
  }
}

widgets.radio=class radioWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    if(!isSet(label)) label='';
    super(parent,data,label,hint);
    this.basestyle = {
      control: {
        margin: maintheme.spacing(3),
      },
    };
    this.defaultvalue = null;
    this.value = false;
    this.color = 'secondary';
    this.onChange=null;
    this.renderLock();
    if((isSet(data)) && data ) this.setValue(data);
    this.renderUnlock();
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const useStyles = makeStyles(this.basestyle);
    const classes = useStyles();
    if(this.hinttext) {
      this.hint = React.createElement(this.createChipHint, {key: this.getKey('hint'), theme: props.theme}, this.hinttext);
    } else {
      this.hint = null;
    }
    this.checkbox = React.createElement(Radio, {key: this.getKey('radio'), theme: props.theme, color: this.color, checked: this.value, disabled: this.disabled, onChange: (e) => {
      this.value = true;
      if(this.onChange) this.onChange(this, props);
      this.redraw();
    }});
    this.label = React.createElement(FormControlLabel, {key: this.getKey('label'), control: this.checkbox, label: React.createElement(React.Fragment, {}, [this.labeltext, this.hint])});
    return React.createElement(FormControl, {key: this.getKey('root'), component: 'fieldset', className: classes.control}, this.label);
  }

  setValue(avalue) {
    if(typeof avalue == 'string') {
      avalue = {value: avalue};
    }
    if(typeof avalue == 'boolean') {
      avalue = {value: avalue};
    }
    if(isSet(avalue.default)) {
      this.defaultvalue = avalue.default;
      if(!isSet(avalue.value)) avalue.value = false;
    }
    // if((this.defaultvalue!==null)&&isSet(avalue.value)&&(avalue.value === false)) {
    //   avalue.value = this.defaultvalue;
    // }
    if(avalue.value==null) {
      this.value = null;
      if(!isSet(avalue.three)) avalue.three=true;
    } else {
      if(typeof avalue.value == 'string') {
        this.value = (avalue.value == 'yes')||(avalue.value == 'on')||(avalue.value == 'true')||(avalue.value == '1');
      } else {
        this.value = (avalue.value == true) || (avalue.value == 1);
      }
    }
    if(typeof avalue.color == 'string') {
      this.color = avalue.color;
    }
    this.redraw();
    return true;
  }

  getValue() {
    return this.value;
  }

  reset() {
    if(this.three) {
      this.value = this.defaultvalue;
    } else {
      if(isSet(this.defaultvalue)) this.value = this.defaultvalue;
    }
    this.redraw();
  }

}

widgets.button=class buttonWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    if(!isSet(label)) label='';
    super(parent,data,label,hint);
    this.basestyle = {
      button: {
      },
      buttoninline: {
        alignSelf: 'flex-start'
      }
    };
    this.expand = false;
    this.icon = null;
    this.mode = 'contained';
    this.color = 'secondary';
    this.onClick=null;
    this.renderLock();
    if((isSet(data)) && data ) this.setValue(data);
    this.renderUnlock();
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const useStyles = makeStyles(this.basestyle);
    const classes = useStyles();
    let icon = null;
    if(isSet(window[this.icon])) icon = React.createElement(window[this.icon]);
    this.button = React.createElement(Button, {
      key: this.getKey('button'),
      theme: props.theme,
      color: this.color,
      variant: this.mode,
      disabled: this.disabled,
      fullWidth: this.expand,
      startIcon: icon,
      className: ((this.parent instanceof widgets.section) && (this.parent.itemsalign==null))?classes.buttoninline:classes.button,
      onClick: () => {if(this.onClick) this.onClick(this, props);}
    }, this.labeltext);
    if(this.hinttext) {
      return React.createElement(this.createSimpleHint, {key: this.getKey('hint'), theme: props.theme, hint: this.hinttext}, this.button); 
    } else {
      return this.button;
    }
  }

  setValue(avalue) {
    if((!isSet(avalue)) || !avalue) avalue = {};
    if(typeof avalue.color == 'string') {
      this.color = avalue.color;
    }
    if(typeof avalue.icon == 'string') {
      this.icon = avalue.icon;
    }
    if(typeof avalue.mode == 'string') {
      if(avalue.mode == 'outlined') {
        this.mode = 'outlined';
      } else 
      if(avalue.mode == 'filled') {
        this.mode = 'contained';
      } else this.mode = 'standard';
    }
    if(isSet(avalue.expand)) {
      if(avalue.expand) {
        this.expand = true;
      } else {
        this.expand = false;
      }
    }
    if(typeof avalue.onClick == 'function') {
      this.onClick = avalue.onClick;
    }
    this.redraw();
    return true;
  }

  getValue() {
    return null;
  }
}

widgets.iconbutton=class iconbuttonWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    if(!isSet(label)) label='';
    super(parent,data,label,hint);
    this.basestyle = {
      button: {
      },
      buttoninline: {
        alignSelf: 'flex-start'
      }
    };
    this.icon = null;
    this.mode = 'contained';
    this.color = 'secondary';
    this.onClick=null;
    this.renderLock();
    if((isSet(data)) && data ) this.setValue(data);
    this.renderUnlock();
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const useStyles = makeStyles(this.basestyle);
    const classes = useStyles();
    let icon = null;
    if(isSet(props.data)&&isSet(props.data.icon)) {
      if(isSet(window[props.data.icon])) icon = React.createElement(window[props.data.icon]);
    } else {
      if(isSet(window[this.icon])) icon = React.createElement(window[this.icon]);
    }
    this.button = React.createElement(IconButton, {
      key: this.getKey('button'),
      theme: props.theme,
      color: this.color,
      variant: this.mode,
      disabled: this.disabled,
      className: ((this.parent instanceof widgets.section) && (this.parent.itemsalign==null))?classes.buttoninline:classes.button,
      onClick: () => {if(this.onClick) this.onClick(this, props);}
    }, icon);
    if(this.hinttext) {
      return React.createElement(this.createSimpleHint, {key: this.getKey('hint'), theme: props.theme, hint: this.hinttext}, this.button); 
    } else {
      return this.button;
    }
  }

  setValue(avalue) {
    if((!isSet(avalue)) || !avalue) avalue = {};
    if(typeof avalue.color == 'string') {
      this.color = avalue.color;
    }
    if(typeof avalue.icon == 'string') {
      this.icon = avalue.icon;
    }
    if(typeof avalue.mode == 'string') {
      if(avalue.mode == 'outlined') {
        this.mode = 'outlined';
      } else 
      if(avalue.mode == 'filled') {
        this.mode = 'contained';
      } else this.mode = 'standard';
    }
    if(typeof avalue.onClick == 'function') {
      this.onClick = avalue.onClick;
    }
    this.redraw();
    return true;
  }

  getValue() {
    return null;
  }
}

widgets.buttons=class buttonsWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    if(!isSet(label)) label='';
    super(parent,data,label,hint);
    this.basestyle = {
      button: {
      },
      buttoninline: {
        alignSelf: 'flex-start'
      }
    };
    this.defaultvalue = null;
    this.buttons = [];
    this.trigger = false;
    this.multiple = false;
    this.mode = 'contained';
    this.color = 'secondary';
    this.onClick=null;
    this.renderLock();
    if((isSet(data)) && data ) this.setValue(data);
    this.renderUnlock();
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const useStyles = makeStyles(this.basestyle);
    const classes = useStyles();
    const [updated, setUpdated] = React.useState(false);
    let buttons = [];
    if(this.trigger) {
      let value = [];     
      for(let i in this.buttons) {
        let icon = null;
        if((isSet(this.buttons[i].icon)) && (isSet(window[this.buttons[i].icon]))) icon = React.createElement(window[this.buttons[i].icon]);
        let button = React.createElement(ToggleButton, {
          key: this.getKey('button',this.buttons[i].id),
          classList: ((this.parent instanceof widgets.section) && (this.parent.itemsalign==null))?classes.buttoninline:classes.button,
          disabled: (this.disabled||((isSet(this.buttons[i].disabled))&&(this.buttons[i].disabled))),
          selected: this.buttons[i].checked,
          variant: this.mode,
          onClick: () => {
            if(this.multiple) {
              this.buttons[i].checked = !this.buttons[i].checked;
            } else {
              for(let r in this.buttons) {
                this.buttons[r].checked = false;
              }
              this.buttons[i].checked = true;
            }
            if(this.onClick) this.onClick(this, buttons[i], props);
            setUpdated(!updated);
          }
        }, icon?icon:(isSet(this.buttons[i].shorttitle)?this.buttons[i].shorttitle:this.buttons[i].title));
        if(isSet(this.buttons[i].shorttitle)) {
          buttons.push(React.createElement(Tooltip, {arrow: true, title: this.buttons[i].title}, button));
        } else {
          buttons.push(button);
        }
        if((typeof this.buttons[i].checked != 'checked') && this.buttons[i].checked) value.push(this.buttons[i].id);
        else this.buttons[i].checked = false;
      }
      return React.createElement(ThemeProvider, {theme: props.theme}, React.createElement(ToggleButtonGroup, {key: this.getKey('root'), color: this.color, value: value}, buttons));
    } else {
      for(let i in this.buttons) {
        let icon = null;
        if((isSet(this.buttons[i].icon)) && (isSet(window[this.buttons[i].icon]))) icon = React.createElement(window[this.buttons[i].icon]);
        buttons.push(React.createElement(Button, {
          key: this.getKey('button', this.buttons[i].id),
          className: ((this.parent instanceof widgets.section) && (this.parent.itemsalign==null))?classes.buttoninline:classes.button,
          startIcon: icon,
          variant: this.mode,
          onClick: () => { if(this.onClick) this.onClick(this, this.buttons[i]);}
        }, this.buttons[i].title));
      }
      if(this.hinttext) {
        return React.createElement(ThemeProvider, {theme: props.theme}, React.createElement(this.createSimpleHint, {key: this.getKey('hint'), hint: this.hinttext}, React.createElement(ButtonGroup, {theme: props.theme, color: this.color, variant: this.mode, disabled: this.disabled}, buttons))); 
      } else {
        return React.createElement(ThemeProvider, {theme: props.theme}, React.createElement(ButtonGroup, {key: this.getKey('root'), color: this.color, variant: this.mode, disabled: this.disabled}, buttons));
      }
    }
  }

  setValue(avalue) {
    if((!isSet(avalue)) || !avalue) avalue = {};
    if(typeof avalue.color == 'string') {
      this.color = avalue.color;
    }
    if(isSet(avalue.buttons)&&(avalue.buttons instanceof Array)) {
      this.buttons = avalue.buttons;
    }
    if(isSet(avalue.default)) {
      this.defaultvalue = avalue.default;
      if(!isSet(avalue.value)) avalue.value = false;
    }
    if((this.defaultvalue!==null)&&isSet(avalue.value)&&(avalue.value === false)) {
      avalue.value = this.defaultvalue;
    }
    if((isSet(avalue.value))&&(avalue.value instanceof Array)) {
      for(let i in this.buttons) {
        if(avalue.value.indexOf(this.buttons[i].id)!=-1) {
          this.buttons[i].checked = true;
        } else {
          this.buttons[i].checked = false;
        }
      }
    }
    if(isSet(avalue.multiple)) {
      if(avalue.multiple) {
        this.multiple = true;
      } else {
        this.multiple = false;
      }
    }
    if(isSet(avalue.trigger)) {
      if(avalue.trigger) {
        this.trigger = true;
      } else {
        this.trigger = false;
      }
    }
    if(typeof avalue.mode == 'string') {
      if(avalue.mode == 'outlined') {
        this.mode = 'outlined';
      } else 
      if(avalue.mode == 'filled') {
        this.mode = 'contained';
      } else this.mode = 'standard';
    }
    if(typeof avalue.onClick == 'function') {
      this.onClick = avalue.onClick;
    }
    this.redraw();
    return true;
  }

  getValue() {
    if(this.trigger) {
      let value = [];
      for(let i in this.buttons) {
        if((typeof this.buttons[i].checked != 'checked') && this.buttons[i].checked) value.push(this.buttons[i].id);
      }
      return value;
    } else return null;
  }
}

widgets.datetime=class datetimeWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    moment.locale('ru');
    this.utc = false;
    this.style = 'inline';
    this.mode = 'standard';
    this.color = 'secondary';
    this.toolbar = true;
    this.readonly = false;
    this.defaultvalue = null;
    this.storeas = 'string';
    this.value = new Date();
    this.format = 'DD.MM.YYYY HH:mm:ss';
    this.onChange = null;
    this.dateFor = null;
    this.dateFrom = null;
    this.renderLock();
    if((isSet(data)) && data ) this.setValue(data);
    this.renderUnlock();
    this.setParent(parent);
  }
  
  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    let hasdate = false;
    let hastime = false;
    let ampm = false;
    let views = [];
    if(this.format.indexOf('D')!=-1) {
      hasdate = true;
      views.push("date");
    }
    if(this.format.indexOf('M')!=-1) {
      hasdate = true;
      views.push("month");
    }
    if(this.format.indexOf('Y')!=-1) {
      hasdate = true;
      views.push("year");
    }
    if(this.format.indexOf('h')!=-1) {
      hastime = true;
      ampm = true;
      views.push("hours");
    } else if(this.format.indexOf('H')!=-1) {
      hastime = true;
      ampm = false;
      views.push("hours");
    }
    if(this.format.indexOf('m')!=-1) {
      hastime = true;
      views.push("minutes");
    }    
    if(this.format.indexOf('s')!=-1) {
      hastime = true;
      views.push("seconds");
    }    
    let params = {
      views: views,
      startFrom: views[0],
      label: this.labeltext,
      fullWidth: ((this.parent instanceof baseWidget) && (this.parent.itemsalign!==null)),
      ampm: ampm,
      disableToolbar: !this.toolbar,
      variant: this.style,
      inputVariant: this.mode,
      disabled: this.disabled,
      readOnly: this.readonly,
      format: this.format,
      value: this.value,
      // initialFocusedDate: this.value,
      clearable: (this.defaultvalue!==null),
      autoOk: true,
      animateYearScrolling: true,
      helperText: this.hinttext,
      onChange: (value) => {
        if(value == null) {
          this.value = this.defaultvalue;
        } else {
          this.value = value;
        }
        if(this.onChange) this.onChange(this, props);
        this.redraw();
      },
      okLabel: _('Принять'),
      cancelLabel: _('Отменить'),
      todayLabel: _('Сегодня'),
      clearLabel: _('Сбросить')
    };
    if(this.dateFor) {
      if(this.dateFor instanceof widgets.datetime) {
        params.maxDate = this.dateFor.value;
      } else {
        params.maxDate = this.dateFor;
      }
    };
    if(this.dateFrom) {
      if(this.dateFrom instanceof widgets.datetime) {
        params.minDate = this.dateFrom.value;
      } else {
        params.minDate = this.dateFrom;
      }
    };
    
    if(hastime&&hasdate) {
      this.picker = React.createElement(KeyboardDateTimePicker, params);
    } else if(hastime) {
      this.picker = React.createElement(KeyboardTimePicker, params);
    } else {
      this.picker = React.createElement(KeyboardDatePicker, params);
    }
    return React.createElement(ThemeProvider, {theme: props.theme}, React.createElement(MuiPickersUtilsProvider, {utils: MomentUtils, locale: this.locale}, this.picker));
  }

  setValue(avalue) {
    if(typeof avalue.format == 'string') {
      this.format = avalue.format;
    }
    if(isSet(avalue.utc)) {
      this.utc = avalue.utc;
    }
    if((typeof avalue == 'object')&&(avalue._isAmomentObject)) {
      avalue = {value: avalue._d};
    }
    if(typeof avalue.color == 'string') {
      this.color = avalue.color;
    }
    if(typeof avalue.mode == 'string') {
      if(avalue.mode == 'outlined') {
        this.mode = 'outlined';
      } else 
      if(avalue.mode == 'filled') {
        this.mode = 'filled';
      } else this.mode = 'standard';
    }
    if(typeof avalue.style == 'string') {
      if(avalue.style == 'inline') {
        this.style = 'inline';
      } else 
      if(avalue.style == 'dialog') {
        this.style = 'dialog';
      } else this.style = 'static';
    }
    if(typeof avalue.storeas == 'string') {
      if(avalue.storeas == 'moment') {
        this.storeas = 'moment';
      } else 
      if(avalue.style == 'date') {
        this.storeas = 'date';
      } else this.storeas = 'string';
    }
    if((isSet(avalue.from))&&(avalue.from instanceof Date)) {
      this.dateFrom = avalue.from;
    }
    if((isSet(avalue.for))&&(avalue.for instanceof Date)) {
      this.dateFor = avalue.for;
    }
    if((isSet(avalue.from))&&(avalue.from instanceof widgets.datetime)) {
      this.dateFrom = avalue.from;
      if(avalue.from.dateFrom == this) avalue.from.dateFrom = null;
      avalue.from.dateFor = this;
    }
    if((isSet(avalue.for))&&(avalue.for instanceof widgets.datetime)) {
      this.dateFor = avalue.for;
      if(avalue.for.dateFor == this) avalue.for.dateFor = null;
      avalue.for.dateFrom = this;
    }
    if((isSet(avalue.from))&&(avalue.from === false)) {
      if((this.dateFrom instanceof widgets.datetime)) this.dateFrom.for = null;
      this.dateFrom = null;
    }
    if((isSet(avalue.for))&&(avalue.for === false)) {
      if((this.dateFor instanceof widgets.datetime)) this.dateFrom.from = null;
      this.dateFor = null;
    }
    if(isSet(avalue.default)) {
      this.defaultvalue = avalue.default;
      if(!isSet(avalue.value)) avalue.value = false;
    }
    if((this.defaultvalue!==null)&&isSet(avalue.value)&&(avalue.value === false)) {
      avalue.value = this.defaultvalue;
    }
    if(typeof avalue.value == 'string') avalue.value = ((avalue.value.indexOf('T')!=-1)?(new Date(avalue.value)):(moment(avalue.value, this.format)._d));
    if((typeof avalue.value == 'object')&&(avalue.value instanceof Date)) this.value = avalue.value;
    if((typeof avalue.value == 'object')&&(avalue.value._isAmomentObject)) {
      if(this.utc) avalue.value=moment(avalue.value.utcOffset(avalue.value.utcOffset()*2).format('DD.MM.YYYY HH:mm:ss'), 'DD.MM.YYYY HH:mm:ss')._d;
    }
    if(typeof avalue.defaultvalue == 'string') avalue.defaultvalue = moment(avalue.defaultvalue, this.format)._d;
    if((typeof avalue.defaultvalue == 'object')&&(avalue.defaultvalue instanceof Date)) this.defaultvalue = avalue.defaultvalue;
    if((typeof avalue.defaultvalue == 'object')&&(avalue.defaultvalue._isAmomentObject)) {
      if(this.utc) avalue.defaultvalue=moment(avalue.defaultvalue.utcOffset(avalue.defaultvalue.utcOffset()*2).format('DD.MM.YYYY HH:mm:ss'), 'DD.MM.YYYY HH:mm:ss')._d;
    }
    if(isSet(avalue.readonly)) {
      if(avalue.readonly) {
        this.readonly = true;
      } else {
        this.readonly = false;
      }
    }
    if(isSet(avalue.toolbar)) {
      if(avalue.toolbar) {
        this.toolbar = true;
      } else {
        this.toolbar = false;
      }
    }
    if(isSet(avalue.utc)) {
      if(avalue.utc) {
        this.utc = true;
      } else {
        this.utc = false;
      }
    }
    this.redraw();
    return true;
  }

  getValue() {
    switch(this.storeas) {
      case 'date': {
        return this.value;
      } break;
      case 'moment': {
        return moment(this.value);
      } break;
      default: {
        return moment(this.value).format(this.format);
      }
    }
  }

  getMoment() {
    return moment(this.value);
  }

  getDate() {
    return this.value;
  }

  getFormat() {
    return moment(this.value).format(this.format);
  }

}

widgets.table=class tableWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.basestyle = {
      root: {
        width: '100%',
      },
      paper: {
        width: '100%',
        marginBottom: maintheme.spacing(2),
      },
      table: {
        minWidth: 750,
      },
    };
    this.selected = [];
    this.defaultvalue = null;
    this.value = [];
    this.header = {};
    this.dense = false;
    this.lines = 5;
    this.rows = 5;
    this.page = 0;
    this.sorted = false;
    this.sortedby = null;
    this.sortasc = true;
    this.onClick = null;
    this.renderLock();
    if((isSet(data)) && data ) this.setValue(data);
    this.renderUnlock();
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const useStyles = makeStyles(this.basestyle);
    const classes = useStyles();
    let headitems = [];
    headitems.push(React.createElement(TableCell, {
      key: this.getKey('headfilter'),
      padding: 'checkbox'
    }, React.createElement(Tooltip, {arrow: true, title: _('Фильтр')}, React.createElement(IconButton, {
      onClick: this.showFilterDialog
    }, React.createElement(FilterListIcon)))));
    for(let i in this.header) {
      let hint = null;
      if(!this.header[i].hidden) {
        if(this.header[i].description) hint = React.createElement(this.createChipHint, props, this.header[i].description);
        if(this.header[i].sortable) {
          headitems.push(React.createElement(TableCell, {
            key: this.getKey('head', i),
            align: this.header[i].align,
            padding: 'default',
            style: this.header[i].width?{width: this.header[i].width}:null,
            sortDirection: (this.sortedby == i)?this.sortasc:false,
          }, React.createElement(TableSortLabel, {
            active: this.sortedby == i,
            direction: this.sortasc?'asc':'desc',
            onClick: () => {
              if(this.sortedby == i) {
                this.sortasc = !this.sortasc;
              } else {
                this.sortedby = i;
                this.sortasc = true;
              }
              this.sortValues();
              this.redraw();
            }
          }, [this.header[i].headerName, hint])));
        } else {
          headitems.push(React.createElement(TableCell, {
            key: this.getKey('head', i),
            align: this.header[i].align,
            padding: 'default',
            style: this.header[i].width?{width: this.header[i].width}:null,
          }, [this.header[i].headerName, hint]));
        }
      }
    }
    this.tablehead = React.createElement(TableHead, {key: this.getKey('head')}, React.createElement(TableRow, {}, headitems));
    let tablerows = [];
    let values = this.value.slice(this.page*this.rows, this.page*this.rows + this.rows);
    for(let i in values) {
      let rowcells = [];
      rowcells.push(React.createElement(TableCell, {key: this.getKey('cellnumber', Number.parseInt(i)+this.page*this.rows)}, '#'+(this.page*this.rows+Number.parseInt(i))));
      for(let j in this.header) {
        if(!this.header[j].hidden) {
          let controls = [];
          let value = values[i][j];
          if(this.header[j].valueGetter) {
            value = this.header[j].valueGetter(this, value, values[i], this.header[j].controls);
          }
          if(typeof value == 'string' || typeof value == 'number') {
            controls.push(value);
          }
          if(this.header[j].controls) {
            for(let k in this.header[j].controls) {
              if(!this.header[j].controls[k].hidden) {
                let lastvalue = this.header[j].controls[k].getValue();
                let newvalue = null;
                if(isSet(value)&&isSet(value[this.header[j].controls[k].id])) {
                  newvalue = value[this.header[j].controls[k].id];
                  this.header[j].controls[k].setValue(newvalue);
                } else if(typeof value == 'object') {
                  newvalue = value;
                  this.header[j].controls[k].setValue(newvalue);
                }
                controls.push(React.createElement(this.header[j].controls[k].prepare, {
                  theme: props.theme,
                  item: values[i],
                  data: newvalue,
                }));
                this.header[j].controls[k].setValue(lastvalue);
              }
            }
          }
          rowcells.push(React.createElement(TableCell, {
            key: this.getKey('cell', i+this.page*this.rows+'_'+j),
            align: this.header[j].align
          }, controls));
        }
      }
      tablerows.push(React.createElement(TableRow, {
        hover: true,
        onClick: () => {
          if(this.onClick) this.onClick(this, this.value[i]);
        },
        tabIndex: -1,
        selected: this.selected.indexOf(this.value[i].id) != -1,
        key: this.getKey('row', i+this.page*this.rows),
      }, rowcells));
    }
    this.tablebody = React.createElement(TableBody, {}, tablerows);
    this.tablecontainer = React.createElement(TableContainer, {
      key: this.getKey('container')
    }, React.createElement(Table, {
      className: classes.table,
      size: this.dense?'small':'medium',
    }, [
      this.tablehead,
      this.tablebody
    ]));
    this.tablepagination = React.createElement(TablePagination, {
      key: this.getKey('pagination'),
      rowsPerPageOptions: [5, 10, 25, 100],
      component: 'div',
      count: this.value.length,
      rowsPerPage: this.rows,
      page: this.page, 
      onChangePage: (e, newpage) => {
        this.page = newpage;
        this.redraw();
      },
      onRowsPerPageChange: (e) => {
        this.page = 0;
        this.rows = e.target.value;
        this.redraw();
      },
      labelDisplayedRows: (data) => {
        return _('{0} - {1} из {2}').format(data.from, data.to, data.count);
      },
      labelRowsPerPage: _('Строк на страницу:'),
      nextIconButtonText: _('Далее'),
      backIconButtonText: _('Назад'),
    });
    return React.createElement(ThemeProvider, {
      theme: props.theme,
      key: this.getKey('root')
    }, React.createElement(Box, {
      style: {
        hieght: props.theme.spacing(3*(this.lines+1)),
        width: '100%'
      }
    }, React.createElement(Paper, {
      classname: classes.paper
    }, [
      this.tablecontainer,
      this.tablepagination,
    ])));
  }

  showFilterDialog(sender) {
    const dialog = new widgets.dialog(dialogcontent, {id: 'TableFilterDialog', hasclose: false}, ' ');
    dialog.renderLock();
    if(dialog.children.length == 0) {
      dialog.list = new widgets.list(dialog, {checkbox: true, options: [], sortable: true});
      dialog.list.selfalign = {xs:12};
      dialog.applyfunc = () => {
        let values = dialog.list.getValue();
        localStorage.setItem(this.id+'@'+urilocation, values.join('|'));
        let newheader = [];
        for(let i in values) {
          newheader[values[i]] = this.header[values[i]];
        }
        for(let i in this.header) {
          if(values.indexOf(i)!=-1) {
            newheader[i].hidden = false;
          } else {
            newheader[i] = this.header[i];
            if(newheader[i].headerName.trim()) {
              newheader[i].hidden = newheader[i].sortable;
            }
          }
        }
        this.header = newheader;
        dialog.hide();
        this.redraw();
      }
    }
    let options = [];
    let selected = [];
    for(let i in this.header) {
      if(this.header[i].headerName) {
        options.push({
          id: i,
          title: this.header[i].headerName,
          disabled: !this.header[i].sortable,
        })
        if(!this.header[i].hidden) selected.push(i);
      }
    }
    dialog.list.setValue({options: options, value: selected});
    dialog.renderUnlock();
    dialog.show();
  }

  setValue(avalue) {
    if((typeof avalue == 'object') && (avalue instanceof Array)) {
      avalue = {value: avalue};
    }
    if(isSet(avalue.sorted)) {
      if(avalue.sorted) {
        this.sorted = true;
      } else {
        this.sorted = false;
      }
    }
    if(typeof avalue.head == 'object') {
      let lastheader = this.header;
      this.header = {};
      let i = 0;
      for(var entry in avalue.head) {
        if(this.sorted && (i==0)) this.sortedby = entry;
        i++;
        if(isSet(lastheader[entry])) {
          this.header[entry] = lastheader[entry];
          this.header[entry].headerName = avalue.head[entry];
        } else {
          this.header[entry]= {
            headerName: avalue.head[entry],
            width: null,
            description: null,
            hidden: false,
            controls: null,
            valueGetter: null,
            sortable: true,
            sortfunc: null,
            align: 'left'
          };
        }
      }
      let values = localStorage.getItem(this.id+'@'+urilocation);
      if(isSet(values)) {
        values = values.split('|');
        let newheader = [];
        for(let i in values) {
          newheader[values[i]] = this.header[values[i]];
        }
        for(let i in this.header) {
          if(values.indexOf(i)!=-1) {
            newheader[i].hidden = false;
          } else {
            newheader[i] = this.header[i];
            if(newheader[i].headerName.trim()) {
              newheader[i].hidden = newheader[i].sortable;
            }
          }
        }
        this.header = newheader;
      }
    }
    if(isSet(avalue.default)) {
      this.defaultvalue = avalue.default;
      if(!isSet(avalue.value)) avalue.value = false;
    }
    if((this.defaultvalue!==null)&&isSet(avalue.value)&&(avalue.value === false)) {
      avalue.value = this.defaultvalue;
    }
    if(avalue.value instanceof Array) {
      this.value = avalue.value;
      if(this.sorted) {
        this.sortValues();
      }
    }
    return true;
  }

  sortValues() {
    if(this.sortedby=='') return;
    let widget = this;
    this.value.sort(function(a, b) {
      if((isSet(a[widget.sortedby]))&&(isSet(b[widget.sortedby]))) {
        if(widget.header[widget.sortedby].sortfunc) {
          return (widget.sortasc?1:-1)*widget.header[widget.sortedby].sortfunc(a[widget.sortedby], b[widget.sortedby]);
        } else {
          return (widget.sortasc?1:-1)*a[widget.sortedby].toString().localeCompare(b[widget.sortedby].toString(), [], {numeric: true});
        }
      } else {
        return (widget.sortasc?1:-1)*a.toString().localeCompare(b.toString(), [], {numeric: true});
      }
    });
  }

  /**
  * \fn bool setHeadHint(String acolumn, String ahint)
  * Устанавливает текст всплывающей подсказки заголовка, может принимать форматированный HTML текст в качестве значения.
  * \tparam String acolumn Наименование столбца
  * \tparam String ahint Текст подсказки
  * \return Истину при успешной смене текста подсказки
  */
  setHeadHint(acolumn, ahint) {
    if(isSet(this.header[acolumn])) {
      this.header[acolumn].description = ahint;
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
    if(isSet(this.header[acolumn])) {
      this.header[acolumn].hidden = false;     
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
    if(isSet(this.header[acolumn])) {
      this.header[acolumn].hidden = true;     
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
    if(isSet(this.header[acolumn])) {
      this.header[acolumn].width = awidth;
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
    if(isSet(this.header[acolumn])) {
      this.header[acolumn].align = aalign;
      return true;
    }
    return false;
  }

  enableCellSort(acolumn) {
    if(isSet(this.header[acolumn])) {
      this.header[acolumn].sortable = true;
      return true;
    }
  }

  disableCellSort(acolumn) {
    if(isSet(this.header[acolumn])) {
      this.header[acolumn].sortable = false;
      return true;
    }
  }

  setCellSortFunc(acolumn, asortfunc) {
    if(isSet(this.header[acolumn])) {
      this.header[acolumn].sortfunc = asortfunc;
      return true;
    }
  }

  setCellFilter(acolumn, afilter) {
    if(isSet(this.header[acolumn])) {
      this.header[acolumn].valueGetter = afilter;
      return true;
    }
  }

  setCellControl(acolumn, acontrol) {
    if(isSet(this.header[acolumn])) {
      if(acontrol instanceof Array) {
        this.header[acolumn].controls = acontrol;
      } else {
        this.header[acolumn].controls = [acontrol];
      }
      return true;
    }
    return false;
  }

  disable() {
    super.disable();
    return true;
  }

  enable() {
    super.enable();
    return true;
  }

  getValue() {
    return this.value;
  }

}

widgets.audio=class audioWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label);
    if(!isSet(widgets.audio.collection)) widgets.audio.collection=[];
    widgets.audio.collection.push(this);
    this.onPlay = null;
    this.onPause = null;
    this.audio = null;
    this.defaultvalue = null;
    this.value = null;
    this.paused = true;
    this.filename = null;
    this.renderLock();
    if((isSet(data)) && data ) this.setValue(data);
    this.renderUnlock();
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const [updated, setUpdated] = React.useState(false);
    React.useEffect(() => {
      this.audio.current.onerror=this.playError;
      this.audio.current.onplay = () => {
        if(this.onPlay) return this.onPlay(this);
        this.paused = false;
        setUpdated(!updated);
        return false;
      };
      this.audio.current.onpause = () => {
        if(this.onPause) return this.onPause(this);
        this.paused = true;
        setUpdated(!updated);
        return false;
      };
    });
    let playing = false;
    if(isSet(this.audio)&&isSet(this.audio.current)) {
      if(this.audio.current.src == this.value) {
        playing = !this.audio.current.paused;
      } else {
        playing = false;
      }
    }
    this.audio = React.createRef();
    this.playbtn = React.createElement(IconButton, {theme: props.theme, key: this.getKey('play'), disabled: this.disabled, onClick: () => {
      if(!this.audio.current.paused) {
        this.audio.current.pause();
      } else {
        for(var i=0; i<widgets.audio.collection.length; i++) {
          if(isSet(widgets.audio.collection[i].audio)&&isSet(widgets.audio.collection[i].audio.current) && !widgets.audio.collection[i].audio.current.paused) {
            widgets.audio.collection[i].audio.current.pause();
          }
        }
        this.audio.current.play();
      }
      setUpdated(!updated);
    }, 'aria-label': 'playpause'}, playing?React.createElement(PauseIcon):React.createElement(PlayArrowIcon));
    this.downloadbtn = React.createElement(IconButton, {theme: props.theme, key: this.getKey('down'), onClick: this.downloadClick, 'aria-label': 'download', disabled: (!isSet(this.value)||this.disabled)}, React.createElement(GetAppIcon));
    this.player = React.createElement('audio', {ref: this.audio, key: this.getKey('audio'), src: this.value});
    return React.createElement(ThemeProvider, {key: this.getKey('root'), theme: props.theme}, [this.player, this.playbtn, this.downloadbtn]);
  }

  playError(sender) {
    var result = true;
    showalert('danger',_('Невозможно воспроизвести запись'));
    return result;
  }

  downloadClick(sender) {
    let result = true;
    let dataURLtoBlob = function( dataUrl, callback ) {
      var req = new XMLHttpRequest;

      req.open( 'GET', dataUrl );
      req.responseType = 'arraybuffer'; // Can't use blob directly because of https://crbug.com/412752

      req.onload = function fileLoaded(e)
      {
          // If you require the blob to have correct mime type
          var mime = this.getResponseHeader('content-type');

          callback( new Blob([this.response], {type:mime}) );
      };

      req.send();
    }

    dataURLtoBlob(this.value, (data) => {
      saveAs(data, this.filename);
    })
    return result;
  }

  setValue(avalue) {
    if(avalue==null) avalue = {};
    if(typeof avalue == 'string') {
      avalue = {value: avalue};
    }
    if(avalue instanceof Blob) {
      avalue = {value: avalue};
    }
    if(isSet(avalue.default)) {
      this.defaultvalue = avalue.default;
      if(!isSet(avalue.value)) avalue.value = false;
    }
    if((this.defaultvalue!==null)&&isSet(avalue.value)&&(avalue.value === false)) {
      avalue.value = this.defaultvalue;
    }
    if(typeof avalue.value == 'string') {
      this.filename = null;
      this.value = avalue.value;
      if(this.value.match(/^data:/)!==null) {
        this.filename = this.value.split('/').pop();
      }
    }
    if(typeof avalue.filename == 'string') {
      this.filename = avalue.filename;
    }
    this.redraw();
    return true;
  }

  getValue() {
    return this.value;
  }

  clear() {
    widgets.audio.collection.splice(widgets.audio.collection.indexOf(this), 1);
    super.clear();
  }
}

widgets.colorpicker=class colorpickerWidget extends baseWidget {

  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.color = 'lightGreen';
    this.shade = '600';
    this.defaultvalue = MaterialUI.colors[this.color][this.shade];
    this.value = this.defaultvalue;
    this.options = [];
    for(let shade in MaterialUI.colors[this.color]) {
      this.options.push({id: shade, title: shade});
    }
    if(isSet(label)) {
      this.select = new widgets.select(null, {options: this.options, value: '600'}, label);
    } else {
      this.select = new widgets.select(null, {options: this.options, value: '600'}, _('Оттенок'));
    }
    this.select.onChange = () => {
      this.shade = this.select.getValue();
      this.value = MaterialUI.colors[this.color][this.shade];
      if(this.onChange) this.onChange(this);
      this.redraw();
    }
    this.basestyle = {
      box: {
        width: maintheme.spacing(7*4-3)+' !important',
        height: maintheme.spacing(5*4)+' !important',
      },
      colorbtn_root: {
        borderRadius: '0px !important',
      },
      colorbtn_label: {
        width: maintheme.spacing(2)+' !important',
        height: maintheme.spacing(2)+' !important',
      },
    };

    this.onChange=null;
    this.renderLock();
    if((isSet(data)) && data ) this.setValue(data);
    this.renderUnlock();
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const useStyles = makeStyles(this.basestyle);
    const classes = useStyles();
    let colors = [];
    colors.push(React.createElement(IconButton, {
      key: this.getKey('transparent'),
      classes: {
        root: classes.colorbtn_root,
        label: classes.colorbtn_label,
      },
      onClick: () => {
        this.color = null;
        this.value = null;
        if(this.onChange) this.onChange(this);
        this.redraw();
      }
    }, (this.color == null)?React.createElement(FiberManualRecordIcon):null));
    for(let color in MaterialUI.colors) {
      if(color != 'common') {
        colors.push(React.createElement(IconButton, {
          key: this.getKey(color),
          classes: {
            root: classes.colorbtn_root,
            label: classes.colorbtn_label,
          },
          onClick: () => {
            this.color = color;
            this.value = MaterialUI.colors[color][this.shade];
            if(this.onChange) this.onChange(this);
            this.redraw();
          },
          style: {
            backgroundColor: MaterialUI.colors[color][this.shade],
          },
        }, (this.color == color)?React.createElement(FiberManualRecordIcon):null));
      }
    }
    return React.createElement(ThemeProvider, {key: this.getKey('root'), theme: props.theme},
      React.createElement(Box, {}, [
        React.createElement(this.select.prepare, {theme: props.theme, width: maintheme.spacing(7*4-3)}),
        React.createElement(Box, {className: classes.box}, colors),
      ])
    );
  }

  setValue(avalue) {
    if((typeof avalue=='undefined') || (avalue===null)) avalue = {};
    if(typeof avalue == 'string') avalue = {value: avalue};
    if(isSet(avalue.default)) {
      this.defaultvalue = avalue.default;
      if(!isSet(avalue.value)) avalue.value = false;
    }
    if(typeof(avalue.value != 'undefined')&&(!avalue.value)) {
      avalue.value = this.defaultvalue;
    }
    if(isSet(avalue.value)) {
      this.color = null;
      this.shade = null;
      for(let color in MaterialUI.colors) {
        if((color != 'common')&&(this.color == null)) for(let shade in MaterialUI.colors[color]) {
          if(MaterialUI.colors[color][shade] == avalue.value) {
            this.color = color;
            this.shade = shade;
            this.select.setValue(this.shade);
            break;
          }
        }
      }
      if(this.color == null) {
        this.color = 'lightGreen';
        this.shade = '600';      
        this.select.setValue(this.shade);
      }
      this.value = MaterialUI.colors[this.color][this.shade];
    }
    this.redraw();
    return true;
  }

  setLabel(label) {
    if(isSet(this.select)) {
      if(isSet(label)) {
        this.select.setLabel(label);
      } else {
        this.select.setLabel(_('Оттенок'));
      }
    }
    super.setLabel(label);
  }

  getValue() {
    return this.value;
  }
}

widgets.chart=class chartWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);

    this.basestyle = {
    };

    this.chartdata = {
      type: 'bar',
      data: {
        labels: [],
        datasets: [],
      },
      options: {
        responsive: true,
        aspectRatio: 2.3,
        legend: {
          position: 'bottom',
          labels: {
            boxWidth: 12
          }
        },
        plugins: {
          colorschemes: {
            scheme: 'tableau.Tableau20'
          }
        }
      }
    };

    this.onClick=null;
    this.onLegendClick=null;
    this.onHintLabel=null;
    this.renderLock();
    if((isSet(data)) && data ) this.setValue(data);
    this.renderUnlock();
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const useStyles = makeStyles(this.basestyle);
    const classes = useStyles();
    let chart = null;
    switch(type) {
      case 'line': chart = Chart.Line; break;
      case 'bar': chart = Chart.Bar; break;
      case 'radar': chart = Chart.Radar; break;
      case 'doughnut': chart = Chart.Doughnut; break;
      case 'polararea': chart = Chart.PolarArea; break;
      case 'bubble': chart = Chart.Bubble; break;
      case 'pie': chart = Chart.Pie; break;
      case 'scatter': chart = Chart.Scatter; break;
      default: chart = Chart.Line;
    }
    return React.createElement(ThemeProvider, {key: this.getKey('root'), theme: props.theme}, React.createElement(chart, {data: this.chartdata.data, options: this.chartdata.options}));
  }

  selectData(dataset, index) {
    if(!isSet(this.chart.data.datasets[dataset].selected)) this.chart.data.datasets[dataset].selected = [];
    this.chart.data.datasets[dataset].selected[index] = {color: this.chart.data.datasets[dataset]._meta[0].data[index]._model.backgroundColor, radius: this.chart.data.datasets[dataset]._meta[0].data[index]._model.outerRadius, lw: this.chart.legend.legendItems[index].lineWidth};
    let color=new Color(this.chart.data.datasets[dataset]._meta[0].data[index]._model.backgroundColor);
    color.lightness(color.lightness()*0.8);
    this.chart.data.datasets[dataset]._meta[0].data[index]._model.backgroundColor = color.rgbString();
    this.chart.data.datasets[dataset]._meta[0].data[index]._model.outerRadius += 10;
    this.chart.legend.legendItems[index].lineWidth = 0;
    this.chart.render(150);
  }

  unselectData(dataset, index) {
    if(!isSet(this.chart.data.datasets[dataset].selected)) return;
    if(isSet(this.chart.data.datasets[dataset].selected[index])) {
      this.chart.data.datasets[dataset]._meta[0].data[index]._model.backgroundColor = this.chart.data.datasets[dataset].selected[index].color;
      this.chart.data.datasets[dataset]._meta[0].data[index]._model.outerRadius = this.chart.data.datasets[dataset].selected[index].radius;
      this.chart.legend.legendItems[index].lineWidth = this.chart.data.datasets[dataset].selected[index].lw;
      delete this.chart.data.datasets[dataset].selected[index];
    }
    this.chart.render(150);
  }

  unselectAll() {
    for(let dataset in this.chart.data.datasets) {
      if(isSet(this.chart.data.datasets[dataset].selected)) delete this.chart.data.datasets[dataset].selected;
    }
    this.chart.update();
  }

  getSelected() {
    let selected = [];
    for(let dataset in this.chart.data.datasets) {
      if(isSet(this.chart.data.datasets[dataset].selected)) {
        for(let index in this.chart.data.datasets[dataset].selected) {
          selected.push({dataset: dataset, index: index});
        }
      }
    }
    return selected;
  }

  isSelected(dataset, index) {
    if(!isSet(this.chart.data.datasets[dataset].selected)) return false;
    if(isSet(this.chart.data.datasets[dataset].selected[index])) return true;
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
    if(!isSet(avalue)) avalue = {};
    if(typeof avalue == 'string') {
      avalue = {value: avalue};
    } else if(typeof avalue == 'number') {
      avalue = {value: String(avalue)};
    }
    if((!isSet(avalue.id))&&(this.input.id == '')) avalue.id='chart-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
    if(!isSet(avalue.pattern)) avalue.pattern=null;
    let initindex = this.chart.data.labels.length;
    let initdsindex = this.chart.data.datasets.length;
    if((isSet(avalue.clean))&&(avalue.clean)) {
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
          if(!isSet(this.chart.data.datasets[dsindex])) this.chart.data.datasets[dsindex] = {};
          this.chart.data.datasets[dsindex].label = i;
          if(!isSet(this.chart.data.datasets[dsindex].data)) this.chart.data.datasets[dsindex].data = [];
          index = initindex;
          for(let j in avalue.value[i]) {
            this.chart.data.datasets[dsindex].data[index++] = avalue.value[i][j];
          }
          dsindex++;
        } else {
          if(!isSet(this.chart.data.datasets[dsindex])) this.chart.data.datasets[dsindex] = {};
          if(!isSet(this.chart.data.datasets[dsindex].data)) this.chart.data.datasets[dsindex].data = [];
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
    if(isSet(avalue.id)) {
      this.input.id=avalue.id;
      if(this.label) this.label.htmlFor=this.input.id;
    }
    return true;
  }

}

widgets.progress=class progressWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.defaultvalue = null;
    this.value = 0;
    this.buffer = null;
    this.color = 'primary';
    this.basestyle = {

    };
    this.renderLock();
    if((isSet(data)) && data ) this.setValue(data);
    this.renderUnlock();
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const useStyles = makeStyles(this.basestyle);
    const classes = useStyles();
    if(this.value == null) {
      this.progress = React.createElement(LinearProgress, {color: this.color});
    } else {
      if(this.buffer !== null) {
        this.progress = React.createElement(LinearProgress, {color: this.color, variant: 'buffer', value: this.value, valueBuffer: this.buffer});
      } else {
        this.progress = React.createElement(LinearProgress, {color: this.color, variant: 'determinate', value: this.value});
      }
    }
    return React.createElement(ThemeProvider, {key: this.getKey('root'), theme: props.theme}, this.progress);
  }

  setValue(avalue) {
    if((!isSet(avalue))||avalue==null) avalue = {};
    if(typeof avalue == 'number') {
      avalue = {value: avalue};
    } else if(typeof avalue == 'string') {
      avalue = {value: Number.parseFloat(avalue)};
    }
    if(isSet(avalue.default)) {
      this.defaultvalue = avalue.default;
      if(!isSet(avalue.value)) avalue.value = false;
    }
    if((this.defaultvalue!==null)&&isSet(avalue.value)&&(avalue.value === false)) {
      avalue.value = this.defaultvalue;
    }
    if(isSet(avalue.value)) {
      if(typeof avalue.value == 'string') {
        this.value = Number.parseFloat(avalue.value);
      } else {
        this.value = avalue.value;
      }
    }
    if(avalue.buffer == null) {
      this.buffer = null;
    } else if(isSet(avalue.buffer)) {
      if(typeof avalue.buffer == 'string') {
        this.buffer = Number.parseFloat(avalue.buffer);
      } else {
        this.buffer = avalue.buffer;
      }
    }
    if(typeof avalue.color == 'string') {
      this.color = avalue.color;
    }
    this.redraw();
    return true;
  }

  getValue() {
    if(this.buffer == null) {
      return this.value;
    } else {
      return {value: this.value, buffer: this.buffer};
    }
  }
}

widgets.volumemeter=class volumemeterWidget extends baseWidget {
  constructor(parent, data, label, hint) {
    super(parent,data,label,hint);
    this.defaultvalue = null;
    this.value = 0;
    this.drawvalue = 0;
    this.bar = null;
    this.canvasCtx = null;
    this.color = 'primary';
    this.gradient = false;
    this.basestyle = {
      bar: {
        width: '100%',
        height: '4px',
      }
    };
    this.props = {theme: maintheme};
    this.renderLock();
    if((isSet(data)) && data ) this.setValue(data);
    this.renderUnlock();
    this.setParent(parent);
  }

  prepare(props) {
    const [drawingState, setDrawingState] = React.useState(false);
    this.drawingstate = drawingState;
    this.setdrawingstate = setDrawingState;
    const useStyles = makeStyles(this.basestyle);
    const classes = useStyles();
    React.useEffect(() => {
      this.canvasCtx = this.bar.current.getContext("2d");
      this.animate();
      return () => {this.canvasCtx = null};
    });
    this.bar = React.createRef();
    this.props = props;
    return React.createElement(Box, {component: 'canvas', key: this.getKey('bar'), className: classes.bar, ref: this.bar});
  }

  setValue(avalue) {
    if((!isSet(avalue))||avalue==null) avalue = {};
    if(typeof avalue == 'number') {
      avalue = {value: avalue};
    } else if(typeof avalue == 'string') {
      avalue = {value: Number.parseFloat(avalue)};
    }
    if(isSet(avalue.default)) {
      this.defaultvalue = avalue.default;
      if(!isSet(avalue.value)) avalue.value = false;
    }
    if((this.defaultvalue!==null)&&isSet(avalue.value)&&(avalue.value === false)) {
      avalue.value = this.defaultvalue;
    }
    if(isSet(avalue.value)) {
      if(typeof avalue.value == 'string') {
        this.value = Number.parseFloat(avalue.value);
      } else {
        this.value = avalue.value;
      }
    }
    if(typeof avalue.color == 'string') {
      this.color = avalue.color;
    }
    if(isSet(avalue.gradient)) {
      if(avalue.gradient) {
        this.gradient = true;
      } else {
        this.gradient = false;
      }
    }
    this.animate();  
    return true;
  }

  getValue() {
    return this.value;
  }

  animate() {
    if(isSet(this.bar)&&isSet(this.bar.current)&&isSet(this.canvasCtx)) {
      let lightcolor = maintheme.palette[this.color].light.match(/^rgb[a]{0,1}\(([0-9]+), *([0-9]+), *([0-9]+)/m);
      if(lightcolor == null) {
        lightcolor = maintheme.palette[this.color].light.match(/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})/m);
        lightcolor[1] = Number.parseInt('0x'+lightcolor[1]);
        lightcolor[2] = Number.parseInt('0x'+lightcolor[2]);
        lightcolor[3] = Number.parseInt('0x'+lightcolor[3]);
      }
      lightcolor = 'rgba({0},{1},{2},0.2)'.format(lightcolor[1], lightcolor[2], lightcolor[3]);
      this.canvasCtx.clearRect(0, 0, this.bar.current.width, this.bar.current.height);
      this.canvasCtx.fillStyle = lightcolor;
      this.canvasCtx.fillRect(0, 0, this.bar.current.width, this.bar.current.height);

      let gradient = null;
      if(this.gradient) {
        gradient = this.canvasCtx.createLinearGradient(0, 0, this.bar.current.width, 0);
        // Добавление трёх контрольных точек
        gradient.addColorStop(0, this.props.theme.palette.success.main);
        gradient.addColorStop(.6, this.props.theme.palette.success.light);
        gradient.addColorStop(.8, this.props.theme.palette.warning.main);
        gradient.addColorStop(1, this.props.theme.palette.error.main);
      } else {
        gradient = this.props.theme.palette[this.color].main;
      }
      this.canvasCtx.fillStyle = gradient;
      this.canvasCtx.fillRect(0, 0, this.bar.current.width*this.drawvalue/100, this.bar.current.height);
      
      if(this.value<this.drawvalue) {
        this.drawvalue -= 4;
        if(this.value>this.drawvalue) this.drawvalue=this.value;
      }
      else if(this.value>this.drawvalue) {
        this.drawvalue += 4;
        if(this.value<this.drawvalue) this.drawvalue=this.value;
      }
      if(this.drawvalue != this.value) {
        requestAnimationFrame(() => {this.animate()});
      } else {
        this.canvasCtx.clearRect(0, 0, this.bar.current.width, this.bar.current.height);
        this.canvasCtx.fillStyle = lightcolor;
        this.canvasCtx.fillRect(0, 0, this.bar.current.width, this.bar.current.height);
        this.canvasCtx.fillStyle = gradient;
        this.canvasCtx.fillRect(0, 0, this.bar.current.width*this.drawvalue/100, this.bar.current.height);
      }
    } else {
      this.drawvalue = this.value;
    }
  }

}
