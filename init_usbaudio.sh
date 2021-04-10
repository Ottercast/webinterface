#!/bin/bash
modprobe libcomposite
cd /sys/kernel/config/usb_gadget/
mkdir g1
cd g1
echo 0x33ff > idVendor
echo 0x4010 > idProduct

mkdir strings/0x409

echo 42424242 > strings/0x409/serialnumber
echo Otter Labs > strings/0x409/manufacturer
echo OtterCastAudio > strings/0x409/product

mkdir configs/audio.1
cd configs/audio.1/
mkdir strings/0x409
echo "USB Audio Otter" > strings/0x409/configuration
echo 42424242 > strings/0x409/serialnumber
echo Otter Labs > strings/0x409/manufacturer
echo OtterCastAudio > strings/0x409/product

cd /sys/kernel/config/usb_gadget/g1
mkdir functions/uac1.0

ln -s functions/uac1.0/ configs/audio.1/
echo "musb-hdrc.1.auto" > UDC
