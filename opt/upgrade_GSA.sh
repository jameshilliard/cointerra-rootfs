#!/bin/sh


REBOOT_REQUIRED=0

if [ ! -f $1 ] ; then
   echo "No file: $1"
   exit
fi

# this is because some old versions of GSA code take a long time to boot
if [ `lsusb | wc -l` -lt 5 ] ; then sleep 20; fi

/opt/dfu-util -D $1 --reset -a 0
DFU_RETURN=$?
if [ "$DFU_RETURN" == "0" ] ; then
  /opt/dfu-util -D $1 --reset -a 0
  sync
  reboot
fi 

for i in 0 1

do

if [ ! -f /opt/cointool-info ] ; then
  ln -s /opt/cointool /opt/cointool-info
fi 

VER=`/opt/cointool-info -i $i | grep "FW " | cut -d " " -f2`


FILE=`echo $1 | cut -d"_" -f2 | cut -d"d" -f1 | sed s/.$//`


if [ "$VER" != "" ] ; then 

echo "Board is $VER"
echo "File is $FILE"

if [ "$VER" == "$FILE" ] ; then
  echo "no upgrade required"
else
  echo "Upgrade required"
  REBOOT_REQUIRED=1
  if [ ! -f /opt/cointool-upgrade ] ; then
    ln -s /opt/cointool /opt/cointool-upgrade
  fi
  /opt/cointool-upgrade -i $i
  sleep 1
  /opt/dfu-util -D $1 --reset -a 0
  sleep 5
fi

fi

done

if [ $REBOOT_REQUIRED -eq 1 ] ; then
  echo "Reboot"
  sync
  sync
  echo b > /proc/sysrq-trigger
fi

