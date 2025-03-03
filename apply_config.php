<?php
require(__DIR__ . "/lib/config.php");

$configService = ConfigService::current();
$config = $configService->config;

$snapclient_config_path = "/tmp/snapclient_env";
$wpaconf_path = "/tmp/wpa_supplicant-wlan0.conf";
$config_file = "/mnt/config.ini";

$firstconfig = !file_exists("/tmp/config_done");
file_put_contents("/tmp/config_done", "");

if ($firstconfig)
{
	`mkdir -p /tmp/otter-home/.config/pulse`;
	`chown -hR otter:otter /tmp/otter-home`;

	$dropbear_key_path = "/etc/dropbear/dropbear_ed25519_host_key";
	// check if we already have SSH hostkeys
	if (isset($config['ssh']["hostkey"]) && $config['ssh']["hostkey"] != "")
	{
		file_put_contents($dropbear_key_path, base64_decode($config['ssh']["hostkey"]));
		`systemctl restart dropbear`;
	}
	else
	{
		@unlink($dropbear_key_path);
		`dropbearkey -t ed25519 -f $dropbear_key_path`;
		$configService->config['ssh'] = [];
		$configService->config['ssh']["hostkey"] = base64_encode(file_get_contents($dropbear_key_path));
		$configService->save_config($config_file, $configService->config);
		`systemctl restart dropbear`;
	}
}

// USB audio (experimental)
if ($config['software']["usbaudio_active"] && 
	!file_exists("/sys/kernel/config/usb_gadget/g1/configs/audio.1/"))
{
	$init = realpath(__DIR__ . "/init_usbaudio.sh");
	`$init`;
}

// Subwoofer support (experimental)
if ($config['general']["subwoofer_active"])
{
	foreach (explode("\n", file_get_contents(__DIR__ . "/subwoofer_regmap.dump")) as $regentry)
	{
		file_put_contents("/sys/kernel/debug/regmap/0-004d/registers", $regentry . "\n");
	}
}
else
{
	// TAS5825M - DEVICE_CTRL2, CTRL_STATE 00 -> Deep sleep
	file_put_contents("/sys/kernel/debug/regmap/0-004d/registers", "000003 00" . "\n");
}

$alsaService = new AlsaService();
$pulseAudioService = new PulseAudioService();

if ($firstconfig)
{
	$alsaService->configure_alsa_mixers();
}

if ($configService->put_file_if_different("/etc/hostname", $config['general']["hostname"]))
{
	$cmd = 'hostname ' . escapeshellarg($config['general']["hostname"]);
	`$cmd`;
	`systemctl daemon-reload`;

	file_put_contents("/etc/hosts", "127.0.0.1	localhost\n");
	file_put_contents("/etc/hosts", "127.0.1.1	" . $config['general']["hostname"] . "\n", FILE_APPEND);
	
	`systemctl restart avahi-daemon`;
	$pulseAudioService->configure_pulseaudio();
}

if ($config['software']["airplay_active"])
{
	`systemctl start shairport-sync`;
}
else
{
	`systemctl stop shairport-sync`;
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

// Line-In Volume
if ($config['software']["linein_loopback_volume"])
{
        $volume = (int)$config['software']["linein_loopback_volume"];
        `PULSE_SERVER=127.0.0.1 pactl set-source-volume LineIn {$volume}%`;
}

// Line-In Loopback
$loopback_instances = `PULSE_SERVER=127.0.0.1 pactl list short modules | grep source=LineIn | cut -f1`;
if ($config['software']["linein_loopback_active"])
{
	if ($loopback_instances == "")
	{
		`PULSE_SERVER=127.0.0.1 pactl load-module module-loopback latency_msec=100 source=LineIn sink=Speakers`;
	}
} else {
	foreach (explode("\n", $loopback_instances) as $instance)
	{
		`PULSE_SERVER=127.0.0.1 pactl unload-module $instance`;
	}
}

if ($config['software']["snapcast_client_active"])
{
	$snapclient_config = 'START_SNAPCLIENT=true' . "\n" .
			     'SNAPCLIENT_OPTS="-h ' . escapeshellarg($config['software']["snapcast_client_hostname"]) . '"' . "\n";

	if ($configService->put_file_if_different($snapclient_config_path, $snapclient_config))
	{
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

if ($configService->put_file_if_different($wpaconf_path, $wpaconf))
{
	error_log("Restarting WiFi");
	`ifdown --force wlan0`;
	`systemctl restart network`;
	`ifup wlan0`;
}
else
{
	`ifup wlan0`;
}

if ($firstconfig)
{
	`systemctl stop ottercast-displayboot`;
	`systemctl start ottercast-frontend`;
}

