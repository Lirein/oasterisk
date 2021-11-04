<?php

namespace addressbook;

class AddressBookSettings extends \core\ViewModule {

  public static function getLocation() {
    return 'settings/peers/addressbook';
  }

  public static function getMenu() {
    return (object) array('name' => 'Адресная книга', 'prio' => 1, 'icon' => 'oi oi-book');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-addressbook');
    return $result;
  }

  public static function getZoneInfo() {
    $result = new \SecZoneInfo();
    $result->zoneClass = 'addressbook';
    $result->getObjects = function () {
                              $addressbook = new \addressbook\AddressBook();
                              $books = $addressbook->getBooks();
                              $returnData = array();
                              foreach($books as $k => $v){
                                $profile = new \stdClass();
                                $profile->id = $k;
                                $profile->text = $v;
                                $returnData[] = $profile;
                              }
                              return $returnData;
                            };
    return $result;
  }

  private static function contactcmp($contact1, $contact2) {
    return strnatcmp($contact1->id,$contact2->id);
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    $addressbook = new \addressbook\AddressBook();
    $zonesmodule=getModuleByClass('core\SecZones');
    if($zonesmodule) $zonesmodule->getCurrentSeczones();
    switch($request) {
      case "books":{
        $books = $addressbook->getBooks();
        asort($books);
        $returnData = array();
        $returnData[] = (object) array('id' => '@allcontacts@', 'title' => 'Все контакты');
        foreach($books as $k => $v){
          $profile = new \stdClass();
          $profile->id = $k;
          $profile->title = $v;
          if(self::checkEffectivePriv('addressbook', $profile->id, 'settings_reader')) $returnData[] = $profile;
        }
        $result = self::returnResult($returnData);
      } break;
      case "book-set": {
        if(isset($request_data->id)&&self::checkEffectivePriv('addressbook', $request_data->id, 'settings_writer')) {
          $books = $addressbook->getBooks();
          $book = $request_data->id;
          if(($request_data->orig_id!='')&&($request_data->id!=$request_data->orig_id)) {
            if(isset($books[$book])) {
              $result = self::returnError('danger', 'Адресная книга с таким идентификатором уже существует');
              break;
            }            
            self::deltreeDB('addressbook/'.$request_data->orig_id);
            $zones = $zonesmodule->getObjectSeczones('addressbook', $request_data->orig_id);
            if(isset($request_data->copy)) {
              $result = $addressbook->copyBook($request_data->orig_id, $request_data->id, $request_data->title);
            } else {
              foreach($zones as $zone) {
                $zonesmodule->removeSeczoneObject($zone, 'addressbook', $request_data->orig_id);
              }
              $result = $addressbook->renameBook($request_data->orig_id, $request_data->id, $request_data->title);
            }
          } else {
            if(($request_data->orig_id=='')&&isset($books[$book])) {
              $result = self::returnError('danger', 'Адресная книга с таким идентификатором уже существует');
              break;
            }            
            self::deltreeDB('addressbook/'.$book);
            self::setDB('addressbook/'.$book, 'straitnumbering', $request_data->straitnumbering);
            $result = $addressbook->setBook($book, $request_data->title);
          }
          if(findModuleByPath('settings/security/seczones')&&($zonesmodule&&!self::checkZones())) {
            $zones = $zonesmodule->getObjectSeczones('addressbook', $book);
            foreach($zones as $zone) {
              $zonesmodule->removeSeczoneObject($zone, 'addressbook', $book);
            }
            if(isset($request_data->zones)&&is_array($request_data->zones)) foreach($request_data->zones as $zone) {
              $zonesmodule->addSeczoneObject($zone, 'addressbook', $book);
            }
          }
          if(!isset($books[$book])&&$zonesmodule&&$this->checkZones()) {
            $eprivs = $zonesmodule->getCurrentPrivs('addressbook', $book);
            $zone = isset($eprivs['settings_writer'])?$eprivs['settings_writer']:false;
            if(!$zone) $zone = isset($eprivs['settings_reader'])?$eprivs['settings_reader']:false;
            if($zone) {
              $zonesmodule->addSeczoneObject($zone, 'addressbook', $book);
            } else {
              $result = self::returnError('danger', 'Отказано в доступе');
              break;
            }
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "book-remove": {
        if(isset($request_data->id)&&self::checkEffectivePriv('addressbook', $request_data->id, 'settings_writer')) {
          if($zonesmodule) {
            foreach($zonesmodule->getObjectSeczones('addressbook', $request_data->id) as $zone) {
              $zonesmodule->removeSeczoneObject($zone, 'addressbook', $request_data->id);
            }
          }
          $result = $addressbook->removeBook($request_data->id);
          self::deltreeDB('addressbook/'.$request_data->id);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;    
      case "contacts-get": {
        if(isset($request_data->id)&&self::checkEffectivePriv('addressbook', $request_data->id, 'settings_reader')) {
          $books = $addressbook->getBooks();
          $book = $request_data->id;
          if($book == '@allcontacts@') {
            $profile = new \stdClass();
            $profile->id = $book;
            $profile->title = 'Все контакты';
            $profile->contacts = array();
            $profile->readonly = true;
            $profile->straitnumbering = false;
            foreach($books as $book => $bookname) {
              $contacts = $addressbook->getContacts($book);
              foreach($contacts as $contact) {
                $contact->id .= '@['.$bookname.']';
                $profile->contacts[] = $contact;
              }
            }
            $result = self::returnResult($profile);
          } else {
            if(isset($books[$book])) {
              $profile = new \stdClass();
              $profile->id = $book;
              $profile->title = $books[$book];
              $profile->contacts = $addressbook->getContacts($book);
              $profile->readonly = !self::checkEffectivePriv('addressbook', $request_data->id, 'settings_writer');
              $straitnumbering=self::getDB('addressbook/'.$book, 'straitnumbering');
              $profile->straitnumbering = ($straitnumbering == 'true');
              if($zonesmodule&&!$this->checkZones()) {
                $profile->zones=$zonesmodule->getObjectSeczones('addressbook', $request_data->id);
              }
              $result = self::returnResult($profile);
            } else {
              $result = self::returnError('danger', 'Адресная книга не найдена');
            }
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "contact-get": {
        if(isset($request_data->id)&&isset($request_data->book)&&self::checkEffectivePriv('addressbook', $request_data->book, 'settings_reader')) {
          $profile = new \stdClass();
          $contact = $addressbook->getContact($request_data->book, $request_data->id);
          if(isset($contact)) {
            $result = self::returnResult($contact);
          } else {
            $result = self::returnError('danger', 'Контакт не найден');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "contact-set": {
        if(isset($request_data->orig_id)&&isset($request_data->book)&&self::checkEffectivePriv('addressbook', $request_data->book, 'settings_writer')) {
          $canprocess = true;
          $straitnumbering=self::getDB('addressbook/'.$request_data->book, 'straitnumbering');
          if($request_data->orig_id!=$request_data->id) {
            if($straitnumbering == 'true') {
              $books = $addressbook->getBooks();
              asort($books);
              foreach($books as $k => $v) {
                $straitnumbering=self::getDB('addressbook/'.$k, 'straitnumbering');
                if($straitnumbering == 'true') {
                  $contacts = $addressbook->getContacts($k);
                  foreach($contacts as $contact) {
                    if($contact->id == $request_data->id) {
                      $canprocess = false;
                      break;
                    }
                  }                   
                }
                if(!$canprocess) break;
              }
            } else {
              $contacts = $addressbook->getContacts($request_data->book);
              foreach($contacts as $contact) {
                if($contact->id == $request_data->id) {
                  $canprocess = false;
                  break;
                }
              }
            }
          }
          if($canprocess) {
            if($addressbook->setContact($request_data->book, $request_data)) {
              $addressbook->reloadConfig();
              $result = self::returnSuccess('Контакт успешно сохранен');
            } else {
              $result = self::returnError('danger', 'Не удалось сохранить контакт');
            }
          } else {
            $result = self::returnError('danger', 'Контакт с таким внутренним номером уже существует');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "contact-remove": {
        if(isset($request_data->id)&&isset($request_data->book)&&self::checkEffectivePriv('addressbook', $request_data->book, 'settings_writer')) {
          if($addressbook->removeContact($request_data->book, $request_data->id)) {
            $addressbook->reloadConfig();
            $result = self::returnSuccess('Контакт успешно удален');
          } else {
            $result = self::returnError('danger', 'Не удается удалить контакт');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "newid": {
        if(isset($request_data->book)&&self::checkEffectivePriv('addressbook', $request_data->book, 'settings_writer')) {
          $newid = -1;
          $straitnumbering=self::getDB('addressbook/'.$request_data->book, 'straitnumbering');
          $contacts = array();
          if($straitnumbering == 'true') {
            $books = $addressbook->getBooks();
            asort($books);
            foreach($books as $k => $v) {
              $straitnumbering=self::getDB('addressbook/'.$k, 'straitnumbering');
              if($straitnumbering == 'true') {
                $contacts = array_merge($contacts, $addressbook->getContacts($k));
              }
            }
          } else {
            $contacts = $addressbook->getContacts($request_data->book);
          }
          uasort($contacts, array(__CLASS__,'contactcmp'));
          foreach($contacts as $contact) {
            if($newid==-1) $newid=$contact->id;
            if($contact->id == $newid) $newid=$contact->id+1;
              else break;
          }
          if($newid==-1) $newid=1;
          $result = self::returnResult($newid);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;   
      case "contactaction": {
        if(isset($request_data->action)&&isset($request_data->propertyclass)) {
          $found = false;
          $modules = findModulesByClass('core\ContactPropertyModule', true);
          if($modules&&count($modules)) {
            foreach($modules as $module) {
              $classname = $module->class;
              $info = $classname::info();
              if($info->class == $request_data->propertyclass) {
                $found = true;
                $action = $request_data->action;
                unset($request_data->action);
                unset($request_data->propertyclass);
                $result = $classname::json($action, $request_data);
                break;
              }
            }
          }
          if(!$found) {
            $result = self::returnError('danger', 'Не найден класс расширения карточки контакта');
          }
        } else {
          $result = self::returnError('danger', 'Не переданы все требуемые параметры');
        }
      } break;
      case "import": {
        if(isset($request_data->file)&&self::checkPriv('settings_writer')) {
          $json = json_decode(file_get_contents($request_data->file->tmp_name));
          foreach($json as $profile) {
            $addressbook->setBook($profile->id, $profile->title);
            if($profile->straitnumbering) self::getDB('addressbook/'.$profile->id, 'straitnumbering', 'true');
            foreach($profile->contacts as $contact) {
              $contact->orig_id = $contact->id;
              $addressbook->setContact($profile->id, $contact);
            }
          }
          $result = self::returnSuccess('Контакт успешно сохранен');
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "export": {
        $abooks = array();
        $books = $addressbook->getBooks();
        foreach($books as $book => $bookname) {
          $profile = new \stdClass();
          $profile->id = $book;
          $profile->title = $bookname;
          $profile->contacts = array();
          $straitnumbering=self::getDB('addressbook/'.$book, 'straitnumbering');
          $profile->straitnumbering = ($straitnumbering == 'true');
          $contacts = $addressbook->getContacts($book);
          foreach($contacts as $contact) {
            $profile->contacts[] = $addressbook->getContact($book, $contact->id);
          }
          $abooks[] = $profile;
        }
        $result = self::returnData(json_encode($abooks), 'application/octet-stream', 'oasterisk-contacts.json');
      } break;
      case "users": {
        $result =self::returnResult(self::getAsteriskPeers());
      } break;
      case "applications": {
        $result = self::returnResult($addressbook->getApplications());
      } break;
    }
    return $result;
  }

  public function scripts() {
    ?>
      <script>
      var book_id='<?php echo isset($_GET['id'])?$_GET['id']:''; ?>';
      var contact_id = null;
      if(book_id.indexOf('@')!=-1) {
        let contactinfo = book_id.split('@');
        book_id = contactinfo[1];
        contact_id = contactinfo[0];
      }
      var contactdialog = null;
      var addcontactbtn = null;
      var contactstable = null;
      var importbtn = null;
      var exportbtn = null;
      var newfile = null;
      var users = [];
      var applications = [];
      var defaulttype = '';
      var newcontacthandlers = [];
      var savecontacthandlers = [];

      function loadAddressBooks() {
        sendRequest('books').success(function(data) {
          var hasactive = false;
          if(data.length) {
            for(var i = 0; i < data.length; i++) {
              if(data[i].id==book_id) { 
                data[i].active = true; 
                hasactive = true;
                break;
              }
            }
          } else {
            data = [];
          };
          rightsidebar_set('#sidebarRightCollapse', data);
          if(!hasactive) {
            card.hide();
            window.history.pushState(book_id, $('title').html(), '/'+urilocation);
            book_id='';
            rightsidebar_init('#sidebarRightCollapse', null, sbadd, sbselect);
            sidebar_apply(null);
            if(data.length>0) loadContacts(data[0].id);
          } else {
            loadContacts(book_id);
          }
          return false;
        });
      }

      function addAddressBook() {
        book_id='';
        contact_id='';
        var tpl_data={id: 'new-book', title: 'Новая адресная книга', straitnumbering: true, contacts: {value: [], clean: true}};
        rightsidebar_activate('#sidebarRightCollapse', null);

        card.setValue(tpl_data);
        addcontactbtn.hide();
        card.enable();
        card.show();
        sidebar_apply(sbapply);
        rightsidebar_init('#sidebarRightCollapse', null, null, sbselect);
      }

      function removeAddressBook() {
        showdialog('Удаление адресной книги','Вы уверены что действительно хотите удалить адресную книгу?',"error",['Yes','No'],function(e) {
          if(e=='Yes') {
            var data = {};
            data.id = book_id;
            sendRequest('book-remove', data).success(function(data) {
              book_id='';
              loadAddressBooks();
            });
          }
        });
      }

      function loadContacts(id) {
        sendRequest('contacts-get', {id: id}).success(function(data) {
          data.contacts = {clean: true, value: data.contacts};
          card.setValue(data);
          if(data.readonly) card.disable(); else card.enable();
          rightsidebar_activate('#sidebarRightCollapse', id);
          window.history.pushState(book_id, $('title').html(), '/'+urilocation+'?id='+id);
          rightsidebar_init('#sidebarRightCollapse', data.readonly?null:sbdel, sbadd, sbselect);
          if(data.readonly) {
            addcontactbtn.hide();
            if(data.id == '@allcontacts@') {
              importbtn.show();
              exportbtn.show();
            } else {
              importbtn.hide();
              exportbtn.hide();
            }
            // contactstable.hideColumn('remove');
          } else {
            addcontactbtn.show();
            importbtn.hide();
            exportbtn.hide();
            // contactstable.showColumn('remove');
          }
          sidebar_apply(data.readonly?null:sbapply);
          book_id = data.id;
          card.show();
          if(contact_id) loadContact(id, contact_id);
        });
      }

      function loadApplications() {
        sendRequest('applications').success(function(data) {
          applications.splice(0);
          applications.push.apply(applications, data);
        });
      }

      function loadUsers(onLoad) {
        sendRequest('users').success(function(data) {
          defaulttype=localStorage.getItem("addressbook-usertype");
          users.splice(0);
          users.push.apply(users, data);
          if(users.length>0) {
            var found=false;
            for(var i=0; i<users.length; i++) {
              if(users[i].type==defaulttype) {
                found=true;
                break;
              }
            }
            if(!found) defaulttype=users[0].type;
          }
          if(typeof onLoad == 'function') {
            onLoad();
          }
        });
      }

      function loadContact(book, id) {
        sendRequest('contact-get', {book: book, id: id}).success(function(data) {
          contactusers = [];
          for(var i in users) {
            if(!((users[i].type == 'Local') && (users[i].login == id+'@ab-'+book))) contactusers.push(users[i]);
          }
          contactdialog.setValue({actions: {applications: applications, users: contactusers}});
          contactdialog.setValue(data);
          if(data.readonly) {
            contactdialog.disable(); 
          } else {
            contactdialog.enable();
          }
          contact_id = data.id;
          contactdialog.setLabel(_('Редактирование контакта'));
          contactdialog.show();
        });
      }

      function addContact(sender) {
        loadUsers(function() {
          contact_id='';
          for(i in newcontacthandlers) {
            newcontacthandlers[i]();
          }
          contactdialog.setLabel(_('Создание контакта'));
          contactdialog.enable();
          contactdialog.show();
        });
      };

      async function sendContact(dialog) {
        var proceed = false;
        var data = dialog.getValue()
        data.orig_id = contact_id;
        data.book = book_id;
        if(data.id=='') {
          showalert('warning','Не задан идентификатор');
          return false;
        }
        let handlersok = true; 
        for(i in savecontacthandlers) {
          if(handlersok) {
            handlersok &= await savecontacthandlers[i]();
          }
        }
        if(!handlersok) return false;
        if((data.orig_id!='')&&(data.id!=data.orig_id)) {
          let modalresult = await showdialog('Идентификатор контакта изменен','Выберите действие с контактом:',"warning",['Rename','Copy', 'Cancel']);
          if(modalresult=='Rename') {
            proceed=true;
          }
          if(modalresult=='Copy') {
            proceed=true;
            data.orig_id = '';
            contact_id='';
          }
        } else {
          proceed=true;
        }
        if(proceed) {
          sendRequest('contact-set', data).success(function() {
            contact_id='';
            loadContacts(book_id);
            return true;
          });
        }
        return false;
      }

      function sendAddressBook() {
        var proceed = false;
        var data = card.getValue()
        data.orig_id = book_id;
        delete data.contacts;
        if(data.id=='') {
          showalert('warning','Не задан идентификатор адресной книги');
          return false;
        }
        if((data.orig_id!='')&&(data.id!=data.orig_id)) {
          showdialog('Идентификатор адресной книги изменен','Выберите действие с адресной книгой:',"warning",['Rename','Copy', 'Cancel'],function(e) {
            if(e=='Rename') {
              proceed=true;
            }
            if(e=='Copy') {
              proceed=true;
              data.copy = true;
              book_id='';
            }
            if(proceed) {
              sendRequest('book-set', data).success(function() {
                book_id = data.id;
                loadAddressBooks();
                return true;
              });
            }
          });
        } else {
          proceed=true;
        }
        if(proceed) {
          sendRequest('book-set', data).success(function() {
            book_id = data.id;
            loadAddressBooks();
            return true;
          });
        }
        return true;
      }

      $(function () {
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();
      });

      function sbselect(e, item) {
        contact_id = null;
        loadContacts(item);
      }

<?php
  if(self::checkPriv('settings_writer')) {
?>

      function sbadd(e) {
        addAddressBook();
      }

      function sbdel(e) {
        removeAddressBook();
      }


<?php
   } else {
      ?>

    var sbapply=null;

<?php
}

?>

      function sbapply(e) {
        sendAddressBook();
      }

      function contact_numbers(anumbers) {
        return anumbers.join(', ');
      }

      function contact_pincode(aprop) {
        return aprop.pincode;
      }

      function editClick(e) {
        loadUsers();
        loadContact(book_id, e.rowdata.id);
      }

      function removeContact(e) {
        showdialog('Удаление контакта','Вы уверены что хотите удалить контакт <b>"'+e.rowdata.name+'"</b>?',"warning",['Yes', 'No'],function(a) {
          if(a=='Yes') {
            var data = {};
            data.book = book_id;
            data.id = e.rowdata.id;
            sendRequest('contact-remove', data).success(function(data) {
              contact_id='';
              loadContacts(book_id);
            });
          }
        });
      }

      function importContact(sender, data) {
        sendRequest('import', {action: 'import', file: data}).success(function() {
          loadAddressBooks();
        });
      }

      function exportContact(sender) {
        sendSingleRequest('export').success(function() {
          window.location = '?json=export';
        });
      }
 
      $(function () {
        contactdialog = new widgets.dialog(rootcontent, null, _('Редактирование контакта'));

        <?php
        $modules = findModulesByClass('core\ContactPropertyModule', true);
        if($modules&&count($modules)) {        
        ?>
          var row = new widgets.section(contactdialog, null);
          row.node.classList.add('row');
          var col = new widgets.columns(row, 2);
          var sect = new widgets.section(col, null, _('Основные настройки'));
        <?php
        } else {
        ?>
          var sect = contactdialog;
        <?php
        }
        ?>
        obj = new widgets.input(sect, {id: 'id', pattern: '[+]{0,1}[0-9]*', placeholder: '100'}, _('Внутренний номер'), _('Внутренний номер контакта адресной книги, используется как идентификатор записи или набираемый номер абонента при включении адресной книги в направление вызовов'));

        obj = new widgets.input(sect, {id: 'name', pattern: '[^:;*$%^#@]+', placeholder: _('Пупкин Василий Иванович')}, _('Наименование контакта'));

        obj = new widgets.input(sect, {id: 'title', pattern: '[^:;*$%^#@]+', placeholder: _('Рядовой сотрудник')}, _('Должность'));

        <?php
        if($modules&&count($modules)) {        
          foreach($modules as $module) {
            $classname = $module->class;
            $info=$classname::info();
            printf("col = new widgets.columns(row, 2);\nsect = new widgets.section(col, '%s', _('%s'));\n", $info->class, $info->name);
            printf("let func = function(card) {\n");
            $classname::scripts();
            printf("}\nfunc(sect);\n");
          }
        }
        ?>

        obj = new widgets.contactactions(contactdialog, {id: 'actions', users: users, applications: applications}, _('Порядок вызова контакта'));

        contactdialog.onSave = sendContact;

        card = new widgets.section(rootcontent,null);
        
        var section = new widgets.section(card, null);
        section.node.className='row';

        var col = new widgets.columns(section, 2);

        obj = new widgets.input(col, {id: 'title'},
            "Наименование адресной книги");
        obj.label.classList.remove('mb-md-0');
        obj.inputdiv.classList.remove('col-md-7');
        
        var col = new widgets.columns(section, 2);

        obj = new widgets.input(col, {id: 'id', pattern: '[a-zA-Z_-]+'}, 
            "Контекст", 
            "Задает уникальный идентификатор контекста адресной книги. Все контексты адресных книг имепют префикс <i>ab-</i>");
        obj.label.classList.remove('mb-md-0');
        obj.inputdiv.classList.remove('col-md-7');

        addcontactbtn = new widgets.button(obj.inputdiv, {id: 'addcontactbtn', class: 'success', icon: 'oi oi-plus'}, 
            "Добавить контакт");
        addcontactbtn.node.classList.add('ml-md-3')
        addcontactbtn.onClick = addContact;
        newfile = new widgets.file(card, {accept: 'application/json'});
        newfile.hide();
        importbtn = new widgets.button(obj.inputdiv, {id: 'importbtn', class: 'warning', icon: 'oi oi-data-transfer-upload'}, 
            "Импорт");
        importbtn.node.classList.add('ml-md-3')
        importbtn.hide();
        importbtn.onClick = function() {
          newfile.enable();
          newfile.input.click();
          newfile.onChange = function(sender) {
            if(!sender.getValue() == '') {
              importContact(importbtn, sender.getValue());
            }
          }
        }
        exportbtn = new widgets.button(obj.inputdiv, {id: 'exportbtn', class: 'success', icon: 'oi oi-data-transfer-download'}, 
            "Экспорт");
        exportbtn.node.classList.add('ml-md-3')
        exportbtn.onClick = exportContact;
        exportbtn.hide();

        obj = new widgets.checkbox(card, {id: 'straitnumbering', value: false}, _("Сквозная нумерация абонентов"), _("Включает для данной адресной книги сквозную нумерацию абонентов учитывая записи всех существующих адресных книг со сквозной нумерацией"));

<?php
        if(findModuleByPath('settings/security/seczones')) {
          $zonesmodule=getModuleByClass('core\SecZones');
          if($zonesmodule) $zonesmodule->getCurrentSeczones();
          if($zonesmodule&&(!self::checkZones())) {
            printf('var values = [];');
            foreach($zonesmodule->getSeczones() as $zone => $name) {
              printf('values.push({id: "%s", text: "%s"});', $zone, $name);
            }
            printf('obj = new widgets.collection(card, {id: "zones", value: values}, "Зоны безопасности");');
          }
        }
?>

        contactstable = new widgets.table(card, {id: 'contacts', sorted: true, head: {id: _('Вн. номер'), numbers: _('Номера'), name: _('Имя'), title: _('Должность'), confbridgeproperty: _('Пинкод'), remove: ' ', edit: ''}, value: [], clean: true});
        contactstable.setHeadHint('id', _('Внутренний номер контакта адресной книги, используется как идентификатор записи или набираемый номер абонента при включении адресной книги в направление вызовов'));
        contactstable.setHeadHint('numbers', _('Все номера которые назначены контакту адресной книги'));
        contactstable.setHeadWidth('remove', '1px');
        contactstable.setHeadWidth('edit', '1px');
        contactstable.setCellControl('remove', {class: 'button', initval: {class: 'danger', icon: 'oi oi-trash', onClick: removeContact}, title: '', novalue: true});
        contactstable.setCellControl('edit', {class: 'button', initval: {icon: 'oi oi-pencil', onClick: editClick}, title: '', novalue: true});
        contactstable.setCellFilter('numbers', contact_numbers);        
        contactstable.setCellFilter('confbridgeproperty', contact_pincode);
        contactstable.setCellSortFunc('id', function(a, b) {
          let as = a.split('@');
          let bs = b.split('@');
          if(typeof as[1] == 'undefined') as[1] = '[!undefined]';
          if(typeof bs[1] == 'undefined') bs[1] = '[!undefined]';
          as[1] = as[1].split('[')[1].split(']')[0];
          bs[1] = bs[1].split('[')[1].split(']')[0];
          let res = as[1].localeCompare(bs[1], [], {numeric: true});
          if(res!=0) {
            return res;
          } else {
            return as[0].localeCompare(bs[0], [], {numeric: true});
          }
        });
        contactstable.setCellSortFunc('confbridgeproperty', function(a, b) {
          if(a.pincode>b.pincode) {
            return 1;
          } else if(a.pincode<b.pincode) {
            return -1;
          } else {
            return 0;
          }
        });

        newcontacthandlers.push(function() {
          sendRequest('newid', {book: book_id}).success(function(data) {
            contactdialog.setValue({id: data});
          });
          contactdialog.setValue({id: 1, name: '', title: '', actions: {applications: applications, users: users, value: []}});
        });
        card.hide();
<?php
if(!self::checkPriv('settings_writer')) {
      ?>
    card.disable();
<?php
}
    ?>
        rightsidebar_init('#sidebarRightCollapse', null, null, sbselect);
        sidebar_apply(null);
        loadApplications();
        loadAddressBooks();
      });
    </script>
    <?php
}

  public function render() {
    ?>
        <input type="password" hidden/>
    <?php
}
}

?>
