#!/bin/sh

if [ -z "$GFLDR" ]; then
/gmail-cleanup.php --user=$GUSER --pass=$GPASS $FLAGS
else
/gmail-cleanup.php --user=$GUSER --pass=$GPASS $GFLDR $FLAGS
fi
