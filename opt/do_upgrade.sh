#! /bin/sh

cd /tmp/

f=$1

if [ `dirname $f` != "/tmp" ]; then
  cp $f /tmp/
  f=/tmp/`basename $f`
fi

zcat $f | tar xf -

if [ "$?" -ne 0 ]; then
  echo "Upgrade corrupted, abort";
  return 1
else
  /tmp/upgrade/upgrade.sh
fi

