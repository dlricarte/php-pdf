<?php

namespace Ang3\Component\Pdf;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use Ang3\Component\Pdf\Command\GenerateCommand;
use Ang3\Component\Pdf\Command\MergeCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Application;

/**
 * @author Joanis ROUANET
 */
class PdfFactory
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var array
     */
    private $parameters;

    public function __construct(array $parameters = [])
    {
        $this->filesystem = new Filesystem();
        $this->parameters = $parameters;
    }

    /**
     * Creates a PDF from content.
     *
     * @throws RuntimeException when creating failed
     *
     * @return string
     */
    public function createFromContent($content, $pdfPath = null, OutputInterface $output = null)
    {
        // Création d'un fichier temporaire pour le contenu
        $tmpFile = $this->createTemporaryFile();

        try {
            // Création du fichier PDF selon l'URL du fichier temporaire
            $pdfFile = $this->createFromUrl(sprintf('file://%s', $tmpFile), $pdfPath, $output);
        } finally {
            // Supression du fichier temporaire
            $this->filesystem->remove($tmpFile);
        }

        // Retour du chemin du fichier PDF
        return $pdfFile;
    }

    /**
     * Generates a PDF from an URL.
     *
     * @throws RuntimeException when creating failed
     *
     * @return string
     */
    public function createFromUrl(string $url, string $target = null, OutputInterface $output = null)
    {
        /**
         * @var string
         */
        $target = $target ?: $this->createTemporaryFile();

        // Lancement de la commande et récupération du code de retour
        $result = $this->runCommand(new ArrayInput([
            'command' => 'ang3:pdf:generate',
            'url' => $url,
            'target' => $target,
            '--chrome-path' => $this->getChromePath(),
        ]), $output);

        // Si la commande a échoué
        if (0 != $result) {
            // Supression du fichier cible
            $this->filesystem->remove($target);

            throw new RuntimeException(sprintf('Unable to create PDF - Error code: %d', $result));
        }

        // Retour du chemin local du fichier PDF
        return $target;
    }

    /**
     * Merges all pdf files and creates an unique PDF to target URL.
     *
     * @throws InvalidArgumentException when no pdf file to merge
     * @throws IOException              when a PDF file was not found
     *
     * @return string
     */
    public function merge(string $target, array $pdfFiles, OutputInterface $output = null)
    {
        // Pour chaque fichier PDF
        foreach ($pdfFiles as $key => &$pdfFile) {
            // Retrait de tous les espaces en trop dans le chemin
            $pdfFile = trim($pdfFile);

            // Si le fichier n'existe pas
            if (!$this->filesystem->exists($pdfFile)) {
                throw new IOException(sprintf('The PDF file %s was not found', $pdfFile));
            }
        }

        // On filtre les valeurs nulles
        $pdfFiles = array_filter($pdfFiles);

        // Si pas de fichier à fusionner
        if (0 === count($pdfFiles)) {
            throw new InvalidArgumentException('No PDF file to merge');
        }

        /**
         * Définition du chemin cible.
         *
         * @var string
         */
        $target = $target ?: $this->createTemporaryFile();

        // Lancement de la commande et récupération du code de retour
        $result = $this->runCommand(new ArrayInput([
            'command' => 'ang3:pdf:merge',
            'target' => $target,
            '--pdfunite-path' => $this->getPdfUnitePath(),
            'files' => $pdfFiles,
        ]), $output);

        // Si la commande a échoué
        if (0 != $result) {
            // Supression du fichier cible
            $this->filesystem->remove($target);

            throw new RuntimeException(sprintf('PDF merge failed - Error code: %d', $result));
        }

        // Retour du chemin du fichier PDF
        return $target;
    }

    /**
     * @return int
     */
    protected function runCommand(ArrayInput $input = null, OutputInterface $output = null)
    {
        // Lancement de l'application en mode console
        $app = new Application();
        $app->setAutoExit(false);

        $app->add(new GenerateCommand());
        $app->add(new MergeCommand());

        // Retour du résultat de la commande
        return $app->run($input ?: new ArrayInput([]), $output ?: new NullOutput());
    }

    /**
     * @throws IOException when file creation failed
     *
     * @return string
     */
    protected function createTemporaryFile()
    {
        // Définition du répertoire des fichiers temporaires
        $tmpDir = sys_get_temp_dir();

        try {
            // Création du fichier temporaire
            $tmpFile = tempnam($tmpDir, uniqid('', true));
        } catch (Exception $e) {
            throw new IOException(sprintf('Unable to create temporary file in directory "%s"', $tmpDir), 0, $e);
        }
    }

    /**
     * @return string
     */
    public function getChromePath()
    {
        return $this->parameters['chrome_path'] ?? GenerateCommand::DEFAULT_BIN_PATH;
    }

    /**
     * @return string
     */
    public function getPdfUnitePath()
    {
        return $this->parameters['pdfunite_path'] ?? MergeCommand::DEFAULT_BIN_PATH;
    }
}
