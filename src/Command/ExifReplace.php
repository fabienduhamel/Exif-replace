<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Process;

class ExifReplace extends Command
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('replace')
            ->addArgument('directory')
            ->addArgument('source-pattern')
            ->addArgument('dest-pattern')
            ->addOption('exif-opts')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $directory = $input->getArgument('directory');
        $sourcePattern = $input->getArgument('source-pattern');
        $destPattern = $input->getArgument('dest-pattern');

        $process = new Process('ls', $directory);
        $process->mustRun();

        $finder = new Finder();

        $filter = function (SplFileInfo $file) use ($sourcePattern)
        {
            return preg_match($sourcePattern, $file->getFilename()) != 0;
        };

        $finder->filter($filter)->depth(0)->files();

        /** @var SplFileInfo $file */
        foreach ($finder->in($directory) as $file) {
            $output->writeln('');
            $output->writeln(sprintf(
                '<info>Working on file %s found...</info>',
                $file->getFilename()
            ));

            $destFileName = $this->getDestFile($file, $sourcePattern, $destPattern);

            if (!file_exists($directory.'/'.$destFileName)) {
                $output->writeln(sprintf(
                    '<comment>Dest file %s not found. Skipping.</comment>',
                    $destFileName
                ));

                continue;
            }

            $output->writeln(sprintf(
                '<info>Dest file %s found...</info>',
                $destFileName
            ));

            $exifCopyProcess = new Process([
                'exiftool',
                '-TagsFromFile',
                $directory.'/'.$file->getFilename(),
                $directory.'/'.$destFileName,
            ]);
            $exifCopyProcess->mustRun();
            $output->writeln($exifCopyProcess->getOutput());

            $touchCopyProcess = new Process([
                'touch',
                '-r',
                $directory.'/'.$file->getFilename(),
                $directory.'/'.$destFileName,
            ]);
            $touchCopyProcess->mustRun();
            $output->writeln($touchCopyProcess->getOutput());

            $output->writeln(sprintf(
                '<info>Dest file %s successfully processed.</info>',
                $destFileName
            ));
        }
    }

    public function getDestFile(SplFileInfo $file, $sourcePattern, $destPattern)
    {
        return preg_replace($sourcePattern, $destPattern, $file->getFilename());
    }
}
