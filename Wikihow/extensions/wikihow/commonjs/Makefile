# We compile our inline javascript using Google Closure for loading speed and
# to allow us more verbose variable names in the uncompiled files.

JAVA=java
# From: https://developers.google.com/closure/compiler/
CLOSURE=/home/reuben/google-closure/compiler.jar
#CLOSURE=/opt/google-closure/compiler.jar
SOURCES=whshared.js webvitals.js
COMPILED=$(SOURCES:.js=.compiled.js)

all: $(COMPILED)

$(COMPILED): $(SOURCES)
	$(JAVA) -jar $(CLOSURE) --js_output_file=$@ $(patsubst %.compiled.js,%.js,$@)

clean:
	rm -f *.compiled.js

