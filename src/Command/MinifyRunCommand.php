<?php

namespace Wgg\MinifyBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Wgg\MinifyBundle\MinifyRunner;

#[AsCommand('minify:run', 'Runs Minify on configured assets')]
class MinifyRunCommand extends Command
{
    public function __construct(
        private readonly MinifyRunner $minify,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('watch', 'w', InputOption::VALUE_NONE, 'Watch');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->minify->setOutputStyle($io);

        $process = $this->minify->run($input->getOption('watch'));
        $process->wait(function ($type, $buffer) use ($io) {
            $io->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $io->error('Minify failed: see output above.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
