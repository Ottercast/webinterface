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

if ($config['CONFIG_LIBRESPOT_ACTIVE'])
{
	`systemctl start librespot`;
}
else
{
	`systemctl stop librespot`;
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
	`ifdown --force wlan0`;
	`ifup wlan0`;
}
else
{
	`ifup wlan0`;
}

if (trim(file_get_contents("/etc/hostname")) != trim($config['CONFIG_DISPLAY_NAME']))
{
	$cmd = 'hostnamectl set-hostname ' . escapeshellarg($config['CONFIG_DISPLAY_NAME']);
	`$cmd`;

	`systemctl restart avahi-daemon`;

	if ($config['CONFIG_PULSEAUDIO_ACTIVE'])
	{
		`systemctl restart pulseaudio`;
	}
	if ($config['CONFIG_AIRPLAY_ACTIVE'])
	{
		`systemctl restart shairport-sync`;
	}
	if ($config['CONFIG_LIBRESPOT_ACTIVE'])
	{
		`systemctl restart librespot`;
	}

	$restarted_services = true;

}
