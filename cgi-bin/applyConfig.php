#!/usr/bin/env php
<?php
require(__DIR__ . "/config.php");

if ($config['CONFIG_AIRPLAY_ACTIVE'])
{
	`systemctl start shairport-sync`;
}
else
{
	`systemctl stop shairport-sync`;
}

if ($config['CONFIG_PULSEAUDIO_ACTIVE'])
{
	`systemctl start pulseaudio`;
}
else
{
	`systemctl stop pulseaudio`;
}

$wpaconf = 'ctrl_interface=/var/run/wpa_supplicant
#ap_scan=1

network={
        ssid="'. $config['CONFIG_WIFI_SSID'] .'"
        scan_ssid=1
        key_mgmt=WPA-PSK
        psk="'. $config['CONFIG_WIFI_PSK'] .'"
}
';

$wpaconfPath = "/etc/wpa_supplicant/wpa_supplicant-wlan0.conf"; 
if (file_get_contents($wpaconfPath) != $wpaconf)
{
	file_put_contents($wpaconfPath, $wpaconf);
	`systemctl restart wpa_supplicant@wlan0`;
}

