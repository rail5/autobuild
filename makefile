all:
	bpp -o autobuildd ./autobuildd.bpp
	bpp -o autobuild-setup ./autobuild-setup.bpp
	bpp -o autobuild ./autobuild.bpp

clean:
	rm -f autobuildd autobuild-setup autobuild