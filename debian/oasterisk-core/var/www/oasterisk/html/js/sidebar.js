var sidebar_showing=false;

function sidebar_collapse_show(e) {
 $(e.target).parents('.sidebar').addClass('active');
 sidebar_showing=true;
}

function sidebar_collapse_shown(e) {
 sidebar_showing=false;
}

function sidebar_collapse_hide(e) {
 if(sidebar_showing) return;
 var sidebar = $(e.target).parents('.sidebar');
 var elems=sidebar.find('div > ul > li > a[aria-expanded="true"]');
 if((elems.length==1)&&(elems.prop('hash')=='#'+e.target.id)) sidebar.removeClass('active');
}

function sidebar_toggle(obj) {
  var hasclass=$(obj).parent().parent().parent().parent().toggleClass("toggled").hasClass("toggled");
   $(obj).find("i").toggleClass("oi-fullscreen-exit oi-fullscreen-enter");
  return hasclass;
}

function leftsidebar_toggle(obj) {
  var hasclass=sidebar_toggle(obj);
  window.localStorage.setItem('hidesidebar', hasclass);
}

function rightsidebar_toggle(obj) {
  var hasclass=sidebar_toggle(obj);
  window.localStorage.setItem('hidesidebar-right', hasclass);
  $('.sidebar + div').toggleClass('toggled');
}

$(document).on('show.bs.collapse', '.sidebar .collapse', function(e) { sidebar_collapse_show(e)});
$(document).on('shown.bs.collapse', '.sidebar .collapse', function(e) { sidebar_collapse_shown(e)});
$(document).on('hide.bs.collapse', '.sidebar .collapse', function(e) { sidebar_collapse_hide(e)});

if(window.localStorage.getItem('hidesidebar')=="true") {
  var obj = $('.sidebar:not(.sidebar-right)').addClass('toggled').find('ul.list-unstyled-bottom li:first-child a');
  obj.find("i").toggleClass("oi-fullscreen-exit oi-fullscreen-enter");
}

if(window.localStorage.getItem('hidesidebar-right')=="true") { 
  var obj = $('.sidebar-right').addClass('toggled').find('ul.list-unstyled-bottom li:first-child a');
  $('button.sb-addbtn').addClass('toggled');
  $('button.sb-delbtn').addClass('toggled');
  $('.sidebar + div').addClass('toggled');
  obj.find("i").toggleClass("oi-fullscreen-exit oi-fullscreen-enter");
}

function sidebar_apply(applyfunc) {
  if(applyfunc==null) {
    $('button.sb-applybtn').addClass('disabled');
  } else {
    $('button.sb-applybtn').removeClass('disabled').off('click').on('click', function(e) {applyfunc(e);});
  }
}

function sidebar_reset(resetfunc) {
  if(resetfunc==null) {
    $('button.sb-delbtn').addClass('disabled').removeClass('singlebtn');
  } else {
    $('button.sb-delbtn').removeClass('disabled').addClass('singlebtn').off('click').on('click', function(e) {resetfunc(e);});
  }
}

function rightsidebar_init(control, delfunc, addfunc, selectfunc) {
  var bar = $(control);
  $('.sidebar + div').addClass('sb-right');
  var btncnt=0;
  if(addfunc==null) {
    bar.find('#addbtn').parent().addClass('disabled');
    $('button.sb-addbtn').addClass('disabled');
  } else {
    btncnt++;
    bar.find('#addbtn').off('click').on('click', function(e) {addfunc(e);}).parent().removeClass('disabled');
    $('button.sb-addbtn').removeClass('disabled').off('click').on('click', function(e) {addfunc(e);});
  }
  if(delfunc==null) {
    bar.find('#delbtn').parent().addClass('disabled');
    $('button.sb-delbtn').addClass('disabled');
  } else {
    btncnt++;
    bar.find('#delbtn').off('click').on('click', function(e) {delfunc(e);}).parent().removeClass('disabled');
    $('button.sb-delbtn').removeClass('disabled').off('click').on('click', function(e) {delfunc(e);});
  }
  var firstbtn=bar.find('ul.list-unstyled-bottom li:first-child');
  firstbtn.removeClass('nobtn');
  firstbtn.removeClass('onebtn');
  if(btncnt==0) {
    firstbtn.addClass('nobtn');
  }
  $('button.sb-delbtn').removeClass('onebtn');
  if(btncnt==1) {
    firstbtn.addClass('onebtn');
    $('button.sb-delbtn').addClass('onebtn');
  }
  if(!isTouchDevice()) bar.hover(function(e) {$('button.sb-addbtn').removeClass('toggled'); $('button.sb-delbtn').removeClass('toggled'); }, function(e) {if(bar.hasClass('toggled')) { $('button.sb-addbtn').addClass('toggled'); $('button.sb-delbtn').addClass('toggled'); } });
  $(bar.find('div > ul').get(0)).off('click').on('click', 'li a', function(e) {selectfunc(e, e.currentTarget.id.substr(3));});
  bar.removeClass('disabled');
}

function rightsidebar_add(list, item) {
    if(typeof item.icon == 'undefined') {
      item.icon='oi\' style=\'width: 2rem; text-align: center; box-sizing: content-box; background: url("data:image/svg+xml;utf8,<svg xmlns=\\"http://www.w3.org/2000/svg\\" xmlns:xlink=\\"http://www.w3.org/1999/xlink\\" version=\\"1.1\\" width=\\"40\\" height=\\"30\\"><text x=\\"0\\" y=\\"18\\">'+item.title.substr(0,3).toUpperCase()+'</text></svg>"); height: 1rem; background-size: 100%;';
    }
    item.badge=false;
    if(typeof item.count == 'undefined') {
      item.count=0;
    } else {
      item.badge=true;
    }
    if(typeof item.max == 'undefined') {
      item.max=0;
    } else {
      item.badge=true;
    }
    var str = '<li class=\'';
    if(typeof item.class != 'undefined') {
      str+='list-group-item-'+item.class+' ';
    }
    if((typeof item.active != 'undefined')&&item.active) {
       if(item.active) str+='active ';
    }
    str+='\'><a id=\'rs-'+item.id+'\'><i class=\''+item.icon+'\'></i><span>'+item.title;
    if(item.badge) {
      var badge_class='success';
      if(typeof item.badgeclass != 'undefined') {
        badge_class=item.badgeclass;
      }
      str+='</span><span class=\'badge badge-pill badge-'+badge_class+' right\'>'+item.count;
      if(item.max>0) str+='/'+item.max;
      str+='';
    }
    str+='</span></a></li>';
    var listitem = $(str).appendTo(list);
    if(typeof item.items != 'undefined') {
      if(item.items.length>0) {
        listitem.children().prop('href', '#sub-'+item.id).attr('data-toggle', 'collapse');
        var sublist = $('<ul id=\'sub-'+item.id+'\' class=\'list-unstyled collapse\' data-parent=\'#rmenuaccordion\'></ul>').appendTo(listitem);
        for(var i=0; i<item.items.length; i++) {
          rightsidebar_add(sublist, item.items[i]);
        }
      }
    }
}

function rightsidebar_set(control, items) {
  var bar = $(control);
  var list = $(bar.find('div > ul').get(0));
  list.html('');
  for(var i=0; i<items.length; i++) {
    rightsidebar_add(list, items[i]);
  }
}

function rightsidebar_activate(control, item) {
  var bar = $(control);
  var list = $(bar.find('div > ul').get(0));
  list.find('li').removeClass('active');
  list.find('a[id="rs-'+item+'"]').parent().addClass('active');
}
