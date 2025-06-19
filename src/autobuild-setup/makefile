BXX = bpp

all: autobuild-setup

autobuild-setup: includes/SetupProgram.sh includes/Window.sh autobuild-setup.bpp
	$(BXX) -o autobuild-setup autobuild-setup.bpp

includes/SetupProgram.sh: includes/SetupProgram.bpp
	$(BXX) -o includes/SetupProgram.sh includes/SetupProgram.bpp

includes/Window.sh: includes/Window.bpp
	$(BXX) -o includes/Window.sh includes/Window.bpp

static:
	$(BXX) -o autobuild-setup autobuild-setup.bpp

clean:
	rm -f autobuild-setup includes/SetupProgram.sh includes/Window.sh

.PHONY: all static clean
