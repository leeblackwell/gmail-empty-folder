#!/bin/sh

#Place a marker down showing when this image was built
TIMESTAMP=`date "+%Y-%m-%d %H:%M:%S"`
echo "$TIMESTAMP" > /image_build_timestamp.txt

apk update
apk add php php-imap dumb-init
