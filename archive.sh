#!/usr/bin/env bash

rm -f BoehsermoeHbConnector.zip
cd src
zip -vr ../BoehsermoeHbConnector.zip . -x "*.DS_Store" "__MACOSX/*"
cd ..
zip BoehsermoeHbConnector.zip -d "*.DS_Store" "__MACOSX/*"

#open .