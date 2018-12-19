<?php
namespace MichielRoos\TYPO3Migrate\Command;

/**
 * Copyright (c) 2018 Michiel Roos
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ConvertFluidNamespacesCommand
 *
 * Convert old Fluid namespaces to html tag with attributes
 *
 * @package MichielRoos\TYPO3Migrate\Command
 */
class ConvertFluidNamespacesCommand extends Command
{
    /**
     * Configure
     */
    protected function configure()
    {
        $this
            ->setName('fluidNsToHtml')
            ->setDescription('Convert old Fluid namespaces (brace style) to html tag with attributes')
            ->setDefinition([
                new InputArgument('template', InputArgument::REQUIRED, 'File to convert')
            ])
            ->setHelp(<<<EOT
The <info>fluidNsToHtml</info> command converts old Fluid namespaces (brace style) to html tag with attributes.

Convert a file:
<info>php typo3migrate.phar fluidNsToHtml ~/tmp/Partials/Template.html</info>

EOT
            );
    }

    /**
     * Execute
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void|int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stdErr = $output;
        if ($output instanceof ConsoleOutputInterface) {
            $stdErr = $output->getErrorOutput();
        }

        $template = realpath($input->getArgument('template'));
        if (!is_file($template)) {
            $stdErr->writeln(sprintf('File does not exist: "%s"', $template));
            exit;
        }

        $lines = file($template);

        $hasOldNamespaces = false;
        $namespaces = [];
        $contentLines = [];
        foreach ($lines as $line) {
            if (preg_match('/{namespace\s+[^=]*\s*=\s*[^}]*}/', $line)) {
                $hasOldNamespaces = true;
                $namespaces[] = trim($line);
            } else {
                $contentLines[] = $line;
            }
        }

        $hasHtmlTag = false;
        foreach ($contentLines as $contentLine) {
            if (trim($contentLine) === '') {
                continue;
            }
            if (preg_match('/\s*<html.*/', $line)) {
                $hasHtmlTag = true;
                break;
            }
        }

        if (!$hasOldNamespaces) {
            $output->writeln(sprintf('Found <info>%s</info> old namespaces', 0));
            exit;
        }

        if ($hasOldNamespaces && $hasHtmlTag) {
            $output->writeln('Found old namespaces but also a html tag. Please investigate.');
            exit;
        }

        $output->writeln(sprintf('Found <info>%s</info> old namespaces:', count($namespaces)));
        foreach ($namespaces as $namespace) {
            $output->writeln(sprintf('- <comment>%s</comment>', $namespace));
        }

        $htmlTag = '<html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"' . PHP_EOL;
        foreach ($namespaces as $namespace) {
            $namespace = trim($namespace, '{}');
            $namespace = preg_replace('/^namespace\s+([^ ]*)/', '$1', $namespace);
            list($key, $class) = explode('=', $namespace);
            $htmlTag .= sprintf("\t  xmlns:%s=\"http://typo3.org/ns/%s\"" . PHP_EOL, $key, str_replace('\\', '/', $class));
        }
        $htmlTag .= '	  data-namespace-typo3-fluid="true">' . PHP_EOL;

        $newTemplate = $htmlTag . implode('', $contentLines) . PHP_EOL . '</html>';

        $filesystem = new Filesystem();
        try {
            $filesystem->touch($template);
        } catch (IOExceptionInterface $exception) {
            echo 'An error occurred while creating the template file at ' . $exception->getPath();
            exit;
        }
        $filesystem->dumpFile($template, $newTemplate);
        $output->writeln(sprintf('Wrote template data to: <info>%s</info>', $template));
    }
}
