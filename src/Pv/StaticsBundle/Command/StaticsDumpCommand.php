<?php

namespace Pv\StaticsBundle\Command;

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
        /** @var $statics_manager \Pv\StaticsBundle\StaticsManager */
        $statics_manager = $this->getContainer()->get('statics.manager');
        $s_dir = $this->getContainer()->getParameter('kernel.root_dir').'/../web/s';

        $files = $statics_manager->getPublicFiles();
        $names_map = array();

        exec("rm -fR $s_dir");
        mkdir($s_dir, 0755, true);

        $webDir = dirname($s_dir);
        foreach ($files as $file) {
            $content = $statics_manager->getFileContent($file);
            $abs_path = $s_dir.'/'.$file;
            $dir = dirname($abs_path);

            $public_name = $statics_manager->getUrl($file, false);
            $names_map[$file] = $public_name;
            file_put_contents($webDir.'/'.$public_name, $content);

            $output->writeln($file.' -> '.$public_name);
        }

        $names_map_file = $this->getContainer()->getParameter('kernel.root_dir').'/statics_map.php';
        $names_map = "<?php\n\nreturn ".var_export($names_map, true).';';
        file_put_contents($names_map_file, $names_map);
    }

    private function getPublicExt($path)
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        switch ($ext) {
            case 'less':
            case 'sass':
            case 'scss':
                $ext = 'css';
        }
        return $ext;
    }
}
