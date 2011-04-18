#!/bin/bash

filename="sp-mobile."`date +%F`".tar"
tar -cvf $filename ./
gzip $filename
mv $filename.gz ~/etc/
