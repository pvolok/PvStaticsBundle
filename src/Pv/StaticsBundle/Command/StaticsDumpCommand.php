<?php

namespace Pv\StaticsBundle\Command;

use Pv\StaticsBundle\StaticsManager;
use Pv\StaticsBundle\UrlHelper;
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
        $container = $this->getContainer();
        /** @var $staticsManager StaticsManager */
        $staticsManager = $this->getContainer()->get('statics.manager');
        /** @var $urlHelper UrlHelper */
        $urlHelper = $container->get('statics.url_helper');
        $sDir = $this->getContainer()->getParameter('kernel.root_dir').'/../web/s';

        $uris = array();
        foreach ($container->get('statics.loader')->findAll() as $uri) {
            $uris = array_merge($uris, $urlHelper->getUrlVariants($uri));
        }

        exec(sprintf('rm -fR %s', escapeshellarg($sDir)));
        mkdir($sDir, 0755, true);

        $namesMap = array();
        $webDir = dirname($sDir);
        foreach ($uris as $uri) {
            $asset = $staticsManager->get($uri);
            $content = $asset->getContent();

            $publicUri = 's/'.md5($content).'.'.$this->getPublicExt($uri);
            $namesMap[$uri] = $publicUri;
            file_put_contents($webDir.'/'.$publicUri, $content);

            $output->writeln($uri.' -> '.$publicUri);
        }

        $namesMapFile = $this->getContainer()->getParameter('kernel.root_dir').'/statics_map.php';
        $namesMap = "<?php\n\nreturn ".var_export($namesMap, true).';';
        file_put_contents($namesMapFile, $namesMap);
    }

    private function getPublicExt($uri)
    {
        preg_match('/[^?]*/', $uri, $matches);
        $ext = pathinfo($matches[0], PATHINFO_EXTENSION);
        switch ($ext) {
            case 'less':
            case 'sass':
            case 'scss':
                $ext = 'css';
        }
        return $ext;
    }
}
