<?php
require(__DIR__ . "/config.php");

// 23 == 0dB capture gain
`amixer cset name='Capture Volume' 23`;

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

if ($config['CONFIG_LINEIN_STREAM_ACTIVE'])
{
	`systemctl start snapserver`;
}
else
{
	`systemctl stop snapserver`;
}

if ($config['CONFIG_SNAPCAST_CLIENT_ACTIVE'])
{
	$snapclient_config = 'START_SNAPCLIENT=true' . "\n" .
			     'SNAPCLIENT_OPTS="-h ' . escapeshellarg($config['CONFIG_SNAPCAST_CLIENT_HOST']) . '"' . "\n";
	$snapclient_config_path = "/etc/default/snapclient";
	if (file_get_contents($snapclient_config_path) != $snapclient_config)
	{
		file_put_contents($snapclient_config_path, $snapclient_config);
		`systemctl restart snapclient`;
	}
	else
	{
		`systemctl start snapclient`;
	}
}
else
{
	`systemctl stop snapclient`;
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
	`systemctl restart network`;
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
	if ($config['CONFIG_SNAPCAST_CLIENT_ACTIVE'])
	{
		`systemctl restart snapclient`;
	}
	if ($config['CONFIG_LINEIN_STREAM_ACTIVE'])
	{
		`systemctl restart snapserver`;
	}

	$restarted_services = true;

}
