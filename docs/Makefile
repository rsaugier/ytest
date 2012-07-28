# Makefile for ytest docs

.PHONY: all clean

all: ytest.html

clean:
	rm -f ytest.html

ytest.html: ytest.asciidoc
	asciidoc -d book -a toc -a numbered -a toclevels=3 -o ytest.html ytest.asciidoc
