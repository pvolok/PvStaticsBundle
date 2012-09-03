<?php

namespace Pv\Bundle\StaticsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StaticsDumpCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('statics:dump')->setDescription('Dump all');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $statics_manager = $this->getContainer()->get('statics.manager');
        $s_dir = $this->getContainer()->getParameter('kernel.root_dir').'/../web/s';

        $files = $this->getFilesToCompile();

        foreach ($files as $file) {
            $content = (string) $statics_manager->create($file);
            $abs_path = $s_dir.'/'.$file;
            $dir = dirname($abs_path);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($abs_path, $content);
        }
    }

    protected function getFilesToCompile()
    {
        $langs = $this->getContainer()->getParameter('locales');

        $files = array();

        foreach ($this->getContainer()->get('kernel')->getBundles() as $bundle) {
            $public_dir = $bundle->getPath().'/Resources/statics';
            if (!is_dir($public_dir)) {
                continue;
            }
            $dir_iter = new \RecursiveDirectoryIterator($public_dir, \FilesystemIterator::SKIP_DOTS);
            $dir_iter = new \RecursiveIteratorIterator($dir_iter);
            $cut_len = strlen($public_dir) + 1;
            foreach ($dir_iter as $file) {
                $file = substr($file, $cut_len);
                if ($this->filterFile($file)) {
                    if (pathinfo($file, PATHINFO_EXTENSION) == 'js') {
                        foreach ($langs as $lang) {
                            $files[] = preg_replace('/\.js$/', "_$lang.js", $file);
                        }
                    } else {
                        $files[] = $file;
                    }
                }
            }
        }

        $files = array_unique($files);
        return $files;
    }

    protected function filterFile($path)
    {
        if (preg_match('/(\/|^)_/', $path)) {
            return false;
        }

        return true;
    }
}
