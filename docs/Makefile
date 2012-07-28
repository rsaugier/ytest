# Makefile for ytest docs

.PHONY: all clean

all: ytest.html

clean:
	rm -f ytest.html

ytest.html: ytest.asciidoc
	asciidoc -o ytest.html ytest.asciidoc
