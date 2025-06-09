<?php

namespace Cube\Commands;

use Cube\App\Directory;
use Cube\Http\Env;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServerCommand extends BaseCommand
{
    private $defaultPort = '8888';

    protected static $defaultName = 'serve';

    /**
     * Configure
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Serve your app')
            ->setHelp('This command allows you serve your app locally')
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'Port', '8888')
            ->addOption('web', '-w', InputOption::VALUE_OPTIONAL, 'Serve on local network', false)
            ->addOption('all', '-a', InputOption::VALUE_OPTIONAL, 'Serve on local network and localhost', false);
    }

    /**
     * Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $input->getOptions();
        $port = $options['port'];
        $env_port = env('serve_port');

        if ($port === $this->defaultPort && $env_port) {
            $port = $env_port;
        }

        $serve_type = match (true) {
            $options['all'] !== false => 'all',
            $options['web'] !== false => 'web',
            default => 'local'
        };

        if (!preg_match('/[0-9]/', $port)) {
            $output->writeln('<fg=#c0392b>Invalid port</>');
            return Command::FAILURE;
        }

        $local_host = '127.0.0.1';
        $host = match ($serve_type) {
            'web' => $this->getLocalIP(),
            'all' => '0.0.0.0',
            default => $local_host
        };

        Env::set('APP_URL', 'http' . '://' . $host . concat(':', $port));
        $url = concat($host, ':', $port);

        $webroot = $this->app->getPath(Directory::PATH_WEBROOT);
        $php_path = env('CLI_PHP_PATH') ?: 'php';

        $process = implode(' ', [
            $php_path,
            '-S',
            $url,
            '-t',
            $webroot
        ]);

        $msg_suffix = match ($serve_type) {
            'all' => concat('http://', $this->getLocalIP(), ':', $port, ' and http://', $local_host, ':', $port),
            'web' => concat('http://', $this->getLocalIP(), ':', $port),
            default => concat('http://', $local_host, ':', $port)
        };

        $msg = concat('App running on ', $msg_suffix);
        $output->writeln(concat('<info>', $msg, '</info>'));
        $output->writeln(concat('Webroot: ', $webroot));

        $response = exec($process);
        $failed_msg = concat('Unable to serve app on: ', $msg);

        if (!$response) {
            $output->writeln(concat('<fg=red>', $failed_msg, '</>'));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function getLocalIP(): string
    {
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_connect($sock, "8.8.8.8", 53);
        socket_getsockname($sock, $name);

        return $name;
    }
}
