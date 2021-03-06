<?php
/**
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace BD\GoogleDocImportBundle\Command;

use eZ\Publish\API\Repository\Exceptions\ContentFieldValidationException;
use eZ\Publish\Core\MVC\Symfony\Routing\RouteReference;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ImportGoogleDocHtmlCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ez:gdoc:import-gdoc-html')
            ->setDescription('Imports an HTML export of a google document to an article')
            ->addArgument('html-file', InputArgument::REQUIRED, 'path to the HTML file to import');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $htmlFile = $input->getArgument('html-file');
        if (!is_file($htmlFile) || !is_readable($htmlFile)) {
            $output->writeln("<error>$htmlFile doesn't exist or is not readable</error>");
            return 1;
        }
        $converter = $this->getContainer()->get('ezpublish.fieldType.ezrichtext.converter.input.html');

        $htmlDoc = new \DOMDocument();
        $htmlDoc->loadHTMLFile($input->getArgument('html-file'));
        $docbookDoc = $converter->convert($htmlDoc);

        $xml = $docbookDoc->saveHTML();

        $repository = $this->getContainer()->get('ezpublish.api.repository');
        $userService = $this->getContainer()->get('ezpublish.api.service.user');
        $repository->setCurrentUser($userService->loadUserByLogin('admin'));
        $contentService = $this->getContainer()->get('ezpublish.api.service.content');
        $contentTypeService = $this->getContainer()->get('ezpublish.api.service.content_type');
        $locationService = $this->getContainer()->get('ezpublish.api.service.location');

        $contentCreateStruct = $contentService->newContentCreateStruct($contentTypeService->loadContentTypeByIdentifier(('article')), 'eng-GB');
        $contentCreateStruct->setField('title', 'HTML import test');
        $contentCreateStruct->setField('intro', $xml);

        try {
            $draft = $contentService->createContent($contentCreateStruct,
                [$locationService->newLocationCreateStruct(2)]);
            $publishedContent = $contentService->publishVersion($draft->getVersionInfo());
            $output->writeln('Published content with id ' . $publishedContent->id);
            $output->writeln('Body:');
            $output->write($xml);
        } catch (ContentFieldValidationException $e) {
            $output->writeln("Fields did not validate:");
            print_r($e->getFieldErrors());
            $output->write($xml);
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            $output->write($xml);
            return;
        }

    }
}
