REVISION := $(shell svn info --show-item revision | cut -d' ' -f1)

all:
	if [ ! -d "js" ]; then mkdir js; fi;
	sed "s/{REVISION}/${REVISION}/" Doxyfile.in > Doxyfile
	./js2doxy.pl ../html/js/widgets.js > js/widgets.js
	./js2doxy.pl ../html/js/asterisk.js > js/asterisk.js
	./js2doxy.pl ../html/js/sidebar.js > js/sidebar.js
	doxygen Doxyfile

clean:
	rm -r html
