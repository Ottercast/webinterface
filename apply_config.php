<?php
require(__DIR__ . "/config.php");

$snapclient_config_path = "/tmp/snapclient_env";
$wpaconf_path = "/tmp/wpa_supplicant-wlan0.conf";

// Line-In Ottercast Audio
// 23 == 0dB capture gain
`amixer cset name='Capture Volume' 23`;

// Class-D Codec on Ottercast Amp
`amixer cset name='Speaker Driver Playback Volume' 640`;
`amixer cset name='Speaker Driver Analog Gain' 0`;

if ($config['software']["airplay_active"])
{
	`systemctl start shairport-sync`;
}
else
{
	`systemctl stop shairport-sync`;
}

if ($config['software']["pulseaudio_active"])
{
	`systemctl start pulseaudio`;
}
else
{
	`systemctl stop pulseaudio`;
}

if ($config['software']["spotifyd_active"])
{
	`systemctl start spotifyd`;
}
else
{
	`systemctl stop spotifyd`;
}

if ($config['software']["linein_stream_active"])
{
	`systemctl start snapserver`;
}
else
{
	`systemctl stop snapserver`;
}

if ($config['software']["snapcast_client_active"])
{
	$snapclient_config = 'START_SNAPCLIENT=true' . "\n" .
			     'SNAPCLIENT_OPTS="-h ' . escapeshellarg($config['software']["snapcast_client_hostname"]) . '"' . "\n";
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

$country = strtoupper(substr(preg_replace("/[^ \w]+/", "", $config['network']["wifi_country"] ?? '00'), 0, 2));

$wpaconf = 'ctrl_interface=/var/run/wpa_supplicant
#ap_scan=1
country='. $country .'

network={
		ssid="'. addslashes($config['network']["wifi_ssid"]) .'"
		scan_ssid=1
		key_mgmt=WPA-PSK
		psk="'. addslashes($config['network']["wifi_passphrase"]) .'"
}
';

if (file_get_contents($wpaconf_path) != $wpaconf)
{
	file_put_contents($wpaconf_path, $wpaconf);
	`ifdown --force wlan0`;
	`systemctl restart network`;
	`ifup wlan0`;
}
else
{
	`ifup wlan0`;
}

if (trim(file_get_contents("/etc/hostname")) != trim($config['general']["hostname"]))
{
	file_put_contents("/etc/hostname", $config['general']["hostname"]);
	$cmd = 'hostname ' . escapeshellarg($config['general']["hostname"]);
	`$cmd`;
	`systemctl daemon-reload`;
	
	`systemctl restart avahi-daemon`;

	if ($config['software']["pulseaudio_active"])
	{
		`systemctl restart pulseaudio`;
	}
	if ($config['software']["airplay_active"])
	{
		`systemctl restart shairport-sync`;
	}
	if ($config['software']["spotifyd_active"])
	{
		`systemctl restart spotifyd`;
	}
	if ($config['software']["snapcast_client_active"])
	{
		`systemctl restart snapclient`;
	}
	if ($config['software']["linein_stream_active"])
	{
		`systemctl restart snapserver`;
	}
}
