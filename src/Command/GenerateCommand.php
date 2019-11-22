<?php

namespace Ang3\Component\Pdf\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @author Joanis ROUANET
 */
class GenerateCommand extends Command
{
    /**
     * Default chrome path.
     */
    const DEFAULT_BIN_PATH = '/usr/bin/google-chrome-stable';

    /**
     * Configuration de la commande.
     */
    protected function configure()
    {
        $this
            ->setName('ang3:pdf:generate')
            ->setDescription('Generation of pdf file - Usage : ang3:pdf:generate <url> [--target|-t pdf_file]')
            ->setHelp('This command generates a PDF from html file.')
            ->addOption('chrome-path', 'c', InputOption::VALUE_OPTIONAL, 'Path of google chrome executable (default: /usr/bin/google-chrome-stable).')
            ->addArgument('url', InputArgument::REQUIRED, 'URL of the HTML file')
            ->addArgument('target', InputArgument::REQUIRED, 'Location of generated PDF')
        ;
    }

    /**
     * Execution de la commande.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Récupération du style symfony
        $io = new SymfonyStyle($input, $output);

        // Récupération du chemin du fichier HTML
        $url = $input->getArgument('url');

        // Récupération du chemin du PDF
        $target = $input->getArgument('target');

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $io->text("Generating PDF in $target...");
        }

        // Définition du chemin de google chrome
        $chromePath = $input->getOption('chrome-path') ?: self::DEFAULT_BIN_PATH;

        // Réupération d'un helper de process
        $helper = $this->getHelper('process');

        // Génération du processus
        $process = new Process([
            $chromePath,
            '--headless',
            '--disable-gpu',
            '--hide-scrollbars',
            sprintf('--print-to-pdf=%s', $target),
            $url,
        ]);

        try {
            // Lancement du process via le helper
            $helper->mustRun($output, $process);
        } catch (ProcessFailedException $e) {
            $io->error($e->getMessage());

            return 1;
        }

        // Retour ok
        return 0;
    }
}
