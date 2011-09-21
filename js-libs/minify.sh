#!/bin/bash

FILE="sp-mobile.js.combined"

touch $FILE

cat lc_prod.js > $FILE
cat micro-template.js >> $FILE
cat json2.js >> $FILE
cat sp-mobile.js >> $FILE

java -jar ../yui/yuicompressor-2.4.2/build/yuicompressor-2.4.2.jar --type js $FILE > $FILE.min.js

