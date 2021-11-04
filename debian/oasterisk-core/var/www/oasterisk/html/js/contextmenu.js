(function ($, window) {

    $.fn.contextMenu = function (settings) {

        return this.each(function () {

            // Open context menu
            $(this).on("contextmenu", function (e) {
                // return native menu if pressing control
                if (e.ctrlKey) return;
                e.preventDefault();
                //open menu
                $(settings.menuSelector).data("user", settings.user).data("group", settings.group);
                if(settings.menuOpen.call(this, $(settings.menuSelector))) {
                  var $menu = $(settings.menuSelector)
                    .data("invokedOn", $(settings.menuSelector))
                    .show()
                    .css({
                        position: "absolute",
                        left: getMenuPosition(e, 'width', $(settings.menuSelector)),
                        top: getMenuPosition(e, 'height', $(settings.menuSelector))
                    })
                    .off('click')
                    .on('click', 'button', function (e) {
                        $menu.hide();
                        var $invokedOn = $menu.data("invokedOn");
                        var $selectedMenu = $(e.target);
                        settings.menuSelected.call(this, $invokedOn, $selectedMenu);
                    });
                }
                return false;
            });

            //make sure menu closes on any click
            $('body').click(function () {
                $(settings.menuSelector).hide();
            });
        });
        
        function getMenuPosition(e, type, $menu) {
	    var mouseX = e.clientX
		, mouseY = e.clientY
		, boundsX = $(window).width()
		, boundsY = $(window).height()
		, menuWidth = $menu.outerWidth()
		, menuHeight = $menu.outerHeight()
		, Y, X, parentOffset;
	    if (mouseY + menuHeight > boundsY) {
		Y = mouseY - menuHeight + $(window).scrollTop();
	    } else {
		Y = mouseY + $(window).scrollTop();
	    }
	    if ((mouseX + menuWidth > boundsX) && ((mouseX - menuWidth) > 0)) {
		X = mouseX - menuWidth + $(window).scrollLeft();
	    } else {
		X = mouseX + $(window).scrollLeft();
	    }

	    // If context-menu's parent is positioned using absolute or relative positioning,
	    // the calculated mouse position will be incorrect.
	    // Adjust the position of the menu by its offset parent position.
	    parentOffset = $menu.parent().offset();
//	    X = X - parentOffset.left + menuWidth;
	    Y = Y - parentOffset.top;
	    X = X - menuWidth/2;
 
	    return (type=='width')?X:Y;
	}
    };
})(jQuery, window);
