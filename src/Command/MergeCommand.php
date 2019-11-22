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
class MergeCommand extends Command
{
    /**
     * pdfunit bin path.
     */
    const DEFAULT_BIN_PATH = '/usr/bin/pdfunite';

    /**
     * Configuration de la commande.
     */
    protected function configure()
    {
        $this
            ->setName('ang3:pdf:merge')
            ->setDescription('Generation of pdf file - Usage : ang3:pdf:merge <files> [--target|-t pdf_file]')
            ->setHelp('This command merges PDF files to an unique PDF file.')
            ->addOption('pdfunite-path', 'p', InputOption::VALUE_OPTIONAL, sprintf('Path of pdfunite executable %s.', sprintf('(default: %s)', self::DEFAULT_BIN_PATH)))
            ->addArgument('target', InputArgument::REQUIRED, 'Location of merged PDF file')
            ->addArgument('files', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'URL\'s of PDF files')
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

        // Récupération du chemin du PDF
        $target = $input->getArgument('target');

        // Récupération du chemin du fichier HTML
        $files = array_unique(array_filter((array) $input->getArgument('files')));

        // Si pas de fichier à merger
        if (0 === count($files)) {
            $io->error('No file to merge');
        }

        // Définition du chemin de google chrome
        $pdfunitePath = $input->getOption('pdfunite-path') ?: self::DEFAULT_BIN_PATH;

        // Réupération d'un helper de process
        $helper = $this->getHelper('process');

        // Génération du processus
        $process = new Process(array_merge([$pdfunitePath], $files, [$target]));

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
