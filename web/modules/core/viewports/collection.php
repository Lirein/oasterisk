<?php

namespace core;

class CollectionViewPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'collection';
  }

  public function implementation() {
    ?>
      <script>
      async function init(parent, data) {

        if(!isSet(data)) data = {modalview: ''};

        this.mediaQueryList = window.matchMedia(maintheme.breakpoints.down('sm').replace('@media ', ''));
        this.documentChangeHandler = () => {
          this.small = this.mediaQueryList.matches;
          this.modalview.onRefreshActions();
        }
        this.mediaQueryList.onchange = this.documentChangeHandler;
        this.small = this.mediaQueryList.matches;
        
        this.collectionlist = new widgets.section(parent);
        this.collectionlist.itemsalign = {xs: 12};
        this.collectionlist.paper = true;
        this.collectionlist.selfalign = {xs: 12, sm: 6, md: 5, lg: 3};
        this.collectionlist.hidesteps = {smDown: true};

        this.addbutton = new widgets.button(this.collectionlist, {expand: true, icon: 'AddSharpIcon', onClick: this.add}, _('Добавить'));
        this.addbutton.hide();

        this.paperlist = new widgets.list(this.collectionlist, {id: 'collection', lines: 0, expand: true, options: []});
        this.paperlist.onDelete = async (sender, item) => {
          try {
            await this.modalview.remove(item.id, item.title);
            this.collectionitem.redraw();
          } catch(e) {
            showalert('danger', _('Невозможно удалить объект: {0}').format(e.message));
          }
        }
        this.paperlist.onChange = async (sender) => {
          if(sender == this.list) {
            this.paperlist.setValue(sender.value);
          } else {
            this.list.setValue(sender.value);
          }
          return this.modalview.load(sender.value);
        }
        this.paperlist.selfalign = {
          xs: 12,
          style: {
            height: 'calc( 100vh - '+maintheme.spacing(18)+')',
            overflowY: 'auto',
            paddingTop: 0,
            marginTop: maintheme.spacing(3),
          }
        };

        this.list = new widgets.list(parent, {id: 'collection', options: []});
        this.list.hidesteps = {smUp: true};
        this.list.onDelete = this.paperlist.onDelete;
        this.list.onChange = this.paperlist.onChange;

        this.collectionitem = new widgets.section(parent);
        this.collectionitem.setLabel = (label) => {
          this.parent.setLabel(label);
        }
        this.collectionitem.setApply = (func) => {
          if(this.list.value != null) {
            this.parent.setApply(func?this.send:null);
          } else {
            this.parent.setApply(null);
          }
        }
        this.collectionitem.setAppend = (func) => {
          this.parent.setAppend((func&&this.small)?this.add:null);
        }
        this.collectionitem.setReset = (func) => {
          this.parent.setReset(func?this.reset:null);
        }
        this.dummyitem = new widgets.section(parent);
        this.dummyitem.selfalign = {xs: 12, sm: 6, md: 7, lg: 9};
        this.dummyitem.hidesteps = {smDown: true};
        this.collectionitem.selfalign = {
          xs: 12,
          sm: 6,
          md: 7,
          lg: 9,
          style: {
            height: 'calc( 100vh - '+maintheme.spacing(15)+')',
            overflowY: 'auto',
            overflowX: 'clip',
            marginTop: maintheme.spacing(2),
            paddingTop: maintheme.spacing(2),
          }
        };
        this.collectionitem.itemsalign = {xs: 12, lg: 6};
        this.collectionitem.hide();
        this.modalview = await require(data.modalview, this.collectionitem);
        // if(this.modalview.hasAdd && this.modalview.showAdd) {
        //   this.parent.setAppend(this.add);
        // } else {
        //   this.parent.setAppend(null);
        // }
        this.modalview.onRefreshActions = () => {
          this.parent.renderLock();
          this.parent.setElement(this.list.value?this.list.options[this.list.options.indexOfId(this.list.value)].title:'');
          if(this.list.value == null) {
            this.collectionitem.hide();
            this.list.hidesteps = {smUp: true};
            this.dummyitem.show();
          } else {
            this.collectionlist.hidesteps = {smDown: true};
            this.dummyitem.hide();
            this.list.hidesteps = {only: ['xs', 'sm', 'md', 'lg', 'xl']};
            this.collectionitem.show();
          }
          if(this.modalview.hasAdd && this.modalview.showAdd) {
            this.parent.setAppend(this.small?this.add:null);
            this.addbutton.show();
            this.paperlist.selfalign = {
              xs: 12,
              style: {
                height: 'calc( 100vh - '+maintheme.spacing(26)+')',
                overflowY: 'auto',
                paddingTop: 0,
                marginTop: maintheme.spacing(3),
              }
            };
          } else {
            this.parent.setAppend(null);
            this.addbutton.hide();
            this.paperlist.selfalign = {
              xs: 12,
              style: {
                height: 'calc( 100vh - '+maintheme.spacing(18)+')',
                overflowY: 'auto',
                paddingTop: 0,
                marginTop: maintheme.spacing(3),
              }
            };
          }
          if((this.list.value !== null) && this.modalview.hasSave && this.modalview.showSave) {
            this.parent.setApply(this.send);
          } else {
            this.parent.setApply(null);
          }
          this.parent.renderUnlock();
          this.parent.redraw();
        }
        parent.onReturn = () => {
          this.collectionitem.view.showAdd = true;
          this.list.value = null;
          this.paperlist.value = null;
          this.modalview.onRefreshActions(this.modalview);
        }
        this.modalview.onUpdate = (sender, action) => {
          this.load();
        }
        this.documentChangeHandler();
      }

      function setValue(data) {
        if(!isSet(data)) data = {};
        if(isSet(data.data)) {
          this.modalview.setValue(data.data);
        }
      }

      async function updateList(sender) {
        let options = await this.modalview.getItems();
        this.list.setValue({options: options});
        this.paperlist.setValue({options: options});
        if((this.list.value == null)&&(this.list.options.length>0)) {
          this.list.setValue(this.list.options[0].id);
          this.paperlist.setValue(this.list.options[0].id);
          this.list.onChange(this.list);
        }
      }

      async function send() {
        this.modalview.send();
      }

      async function add() {
        if(this.modalview.add()) {
          this.modalview.parent.show();
          let data = this.modalview.getValue();
          if(isSet(data.title)) {
            this.parent.setElement(data.title);
          } else
          if(isSet(data.name)) {
            this.parent.setElement(data.name);
          }
          if(this.modalview.hasSave && this.modalview.showSave) {
            this.parent.setApply(this.send);
          }
        }
      }

      async function reset() {
        this.modalview.reset();
      }

      async function load() {
        await this.updateList();
      }

    </script>
    <?php
  }

}

?>
