<?php
define("SERVER_PATH", realpath(__DIR__ . "/../"));

spl_autoload_register(function ($class)
{
    $path = SERVER_PATH . "/lib/$class.php";
	if(file_exists($path))
		require_once($path);
});

$config_file = "/mnt/config.ini";
$default_config_file = SERVER_PATH . "/example_config.ini";

$configService = ConfigService::current();
$configService->load_config($config_file, $default_config_file);

$devices = [
	"OtterCast Amp" => [
		"pulseaudio_devices" => [
			["type" => "sink",	"device" => "OtterAudioCard",	"name" => "Speakers"],
			["type" => "source",	"device" => "Codec",		"name" => "LineIn"],
		],
		"pulseaudio_loopbacks" => [],
		"amixer" => [
			["card" => "Codec", "name" => "Line In Capture Switch", "value" => "on"],
			["card" => "OtterAudioCard", "name" => "main Speaker Driver Playback Volume", "value" => "640"],
			["card" => "OtterAudioCard", "name" => "main Speaker Driver Analog Gain", "value" => "0"],
			["card" => "OtterAudioCard", "name" => "woofer Speaker Driver Playback Volume", "value" => "640"],
			["card" => "OtterAudioCard", "name" => "woofer Speaker Driver Analog Gain", "value" => "0"],
		]
	],
	"OtterCast Audio V2" => [
		"pulseaudio_devices" => [
			["type" => "sink",	"device" => "OtterAudioCard",	"name" => "Speakers"],
			["type" => "source",	"device" => "OtterAudioCard",	"name" => "LineIn"],
		],
		"pulseaudio_loopbacks" => [],
		"amixer" => [
			// 23 == 0dB capture gain
			["card" => "OtterAudioCard", "name" => "Capture Volume", "value" => "23"]
		]
	]
];

// Add additional pulseaudio devices and loopbacks when USB audio is active
if ($configService->config['software']["usbaudio_active"])
{
	$devices["OtterCast Amp"]["pulseaudio_devices"][] = ["type" => "source", "device" => "UAC1Gadget", "name" => "USBAudio"];
	$devices["OtterCast Audio V2"]["pulseaudio_devices"][] = ["type" => "source", "device" => "UAC1Gadget", "name" => "USBAudio"];

	$devices["OtterCast Amp"]["pulseaudio_loopbacks"][] = ["source" => "USBAudio", "sink" => "Speakers"];
	$devices["OtterCast Audio V2"]["pulseaudio_loopbacks"][] = ["source" => "USBAudio", "sink" => "Speakers"];
}

$model = trim(file_get_contents("/proc/device-tree/model")) ?? 'Unknown hardware';
$is_ottercast_amp = (bool) ($model == "OtterCast Amp");
$is_ottercast_audio = (bool) ($model == "OtterCast Audio V2");

$configService->device = $devices[$model] ?? [
	"pulseaudio_devices" => [],
	"pulseaudio_loopbacks" => [],
	"amixer" => []
];
