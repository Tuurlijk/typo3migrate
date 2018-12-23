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
use Symfony\Component\Finder\Finder;

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
            ->setDescription('Convert old Fluid namespaces (curly brace style) to html tag with attributes')
            ->setDefinition([
                new InputArgument('target', InputArgument::REQUIRED, 'File or directory to convert')
            ])
            ->setHelp(<<<EOT
The <info>fluidNsToHtml</info> command converts old Fluid namespaces (curly brace style) to html tag with attributes.

Convert a file:
<info>php typo3migrate.phar fluidNsToHtml ~/tmp/Partials/Template.html</info>

Convert a directory:
<info>php typo3migrate.phar fluidNsToHtml ~/tmp/Partials</info>

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

        $target = realpath($input->getArgument('target'));
        if (!is_file($target) && !is_dir($target)) {
            $stdErr->writeln(sprintf('Path does not exist: "%s"', $target));
            exit;
        }

        if (is_dir($target)) {
            $templateFinder = new Finder();
            $templateList = $templateFinder->files()->in($target)->name('*.html');
            /** @var \SplFileInfo $templateFile */
            foreach ($templateList as $templateFile) {
                $this->convertFile($templateFile->getPathname(), $input, $output);
            }
        } elseif (is_file($target)) {
            $this->convertFile($target, $input, $output);
        }
    }

    /**
     * Convert a file
     *
     * @param string $target
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void|int
     */
    protected function convertFile($target, InputInterface $input, OutputInterface $output)
    {
        $lines = file($target);

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
            $contentLine = trim($contentLine);
            if ($contentLine === '') {
                continue;
            }
            if (preg_match('/\s*<html.*/', $contentLine)) {
                $hasHtmlTag = true;
                break;
            }
        }

        if (!$hasOldNamespaces && $hasHtmlTag) {
            $output->writeln(sprintf('Found <info>%s</info> old namespaces', 0));
            return;
        }

        if ($hasOldNamespaces && $hasHtmlTag) {
            $output->writeln('Found old namespaces but also a html tag. Please investigate.');
            return;
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
            $filesystem->touch($target);
        } catch (IOExceptionInterface $exception) {
            echo 'An error occurred while creating the template file at ' . $exception->getPath();
            return;
        }
        $filesystem->dumpFile($target, $newTemplate);
        $output->writeln(sprintf('Wrote template data to: <info>%s</info>', basename($target)));
    }
}
