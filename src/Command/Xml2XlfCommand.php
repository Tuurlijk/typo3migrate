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
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Class Xml2XlfCommand
 *
 * Convert T3locallang to Xliff format
 *
 * @package MichielRoos\TYPO3Migrate\Command
 */
class Xml2XlfCommand extends Command
{
    /**
     * Configure
     */
    protected function configure()
    {
        $this
            ->setName('xml2xlf')
            ->setDescription('Convert T3locallang files to Xliff')
            ->setDefinition([
                new InputArgument('xml', InputArgument::REQUIRED, 'File to convert')
            ])
            ->setHelp(<<<EOT
The <info>xml2xlf</info> command converts a T3locallang file to Xliff</info>.

Convert a file:
<info>php typo3migrate.phar xml2xlf ~/tmp/source/locallang.xml</info>

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
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stdErr = $output;
        if ($output instanceof ConsoleOutputInterface) {
            $stdErr = $output->getErrorOutput();
        }

        $xml = realpath($input->getArgument('xml'));
        if (!is_file($xml)) {
            $stdErr->writeln(sprintf('File does not exist: "%s"', $xml));
            exit;
        }
    }
}
