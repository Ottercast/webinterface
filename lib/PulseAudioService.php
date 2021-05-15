<?php 
class PulseAudioService
{
	public function get_sinks(): array
	{
		return $this->get_list("sinks");
	}

	public function get_sources(): array
	{
		return $this->get_list("sources");
	}

	public function get_list(string $type): array
	{
		$type_esc = escapeshellarg($type);
		$list_raw = `pactl list $type_esc`;
		$return = []; 

		if ($type == "sources")
		{
			$list_parts = explode("Source #", $list_raw);
		}
		if ($type == "sinks")
		{
			$list_parts = explode("Sink #", $list_raw);
		}
		
		foreach ($list_parts as $list_part)
		{
			if (trim($list_part) == "")
			{
				continue; 
			}

			$element = [];
			$element['Properties'] = [];

			$element['ID'] = (int)explode("\n", $list_part, 2)[0];

			$matches = [];
			preg_match_all('/\t(.*?): (.*)/m', $list_part, $matches, PREG_SET_ORDER, 0);

			foreach ($matches as $match)
			{
				$element[trim($match[1])] = trim($match[2]);
			}

			$matches = [];
			preg_match_all('/\t\t(.*?) = \"(.*)\"/m', $list_part, $matches, PREG_SET_ORDER, 0);
			foreach ($matches as $match)
			{
				$element['Properties'][trim($match[1])] = trim($match[2]);
			}

			$return[$element['ID']] = $element;
		}

		return $return;
	}

	public function configure_pulseaudio()
	{
		require_once(SERVER_PATH . '/vendor/autoload.php');
		$loader = new \Twig\Loader\FilesystemLoader(SERVER_PATH.'/templates_config');
		$twig = new \Twig\Environment($loader, [
		    'cache' => '/tmp/twig_cache',
		]);
		$configService = ConfigService::current();

		$restartPA = $configService->put_file_if_different("/tmp/system.pa", $twig->render('system.pa.twig', ConfigService::current()->device));
		$restartPA = $restartPA || $configService->put_file_if_different("/tmp/daemon.conf", $twig->render('daemon.conf.twig', ConfigService::current()->device));

		if ($restartPA)
		{
			`systemctl restart pulseaudio`;

			// will block until PulseAudio is fully loaded 
			while (count($this->get_sources()) == 0)
			{
				error_log("Waiting for PulseAudio...");
				usleep(500 * 1000);
			}
		}
	}
}