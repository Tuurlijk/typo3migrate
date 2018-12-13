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
use TYPO3\CMS\Core\Localization\Parser\LocallangXmlParser;

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
     * @throws \Exception
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

        $parser = new LocallangXmlParser();
        $data = $parser->getParsedData($xml, 'default');

        if (!count($data)) {
            $output->writeln('Did not find any language labels for the given language.');
            exit;
        }
        foreach ($data as $key => $labels) {
            $output->writeln(sprintf('Found <info>%s</info> language labels for language <comment>%s</comment>', count($labels), $key));
        }

        $pathInfo = pathinfo($xml);
        $xlfFile = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $pathInfo['filename'] . '.xlf';

        $filesystem = new Filesystem();
        try {
            $filesystem->touch($xlfFile);
        } catch (IOExceptionInterface $exception) {
            echo 'An error occurred while creating the translation file at ' . $exception->getPath();
        }
        $filesystem->dumpFile($xlfFile, $this->getXlf($data));
        $output->writeln(sprintf('Wrote file to: <info>%s</info>', $xlfFile));
    }

    /**
     *
     * <?xml version="1.0" encoding="utf-8" standalone="yes" ?>
     *     <xliff version="1.0">
     *     <file source-language="en" datatype="plaintext" original="messages" date="2013-02-21T01:52:55Z" product-name="static_info_tables">
     *     <header/>
     *     <body>
     *     <trans-unit id="m_default" xml:space="preserve">
     *     <source>default</source>
     *     </trans-unit>
     *     <trans-unit id="m_ext" xml:space="preserve">
     *     <source>extended</source>
     *     </trans-unit>
     *     <trans-unit id="m_country" xml:space="preserve">
     *     <source>country</source>
     *     </trans-unit>
     *     <trans-unit id="m_other" xml:space="preserve">
     *     <source>other</source>
     *     </trans-unit>
     *     </body>
     *     </file>
     *     </xliff>
     *
     * @param $data
     * @param $language
     * @param array $translations
     * @return string
     */
    protected function getXlf($data, $language = 'default', $translations = array())
    {
        $xml = new \DOMDocument('1.0', 'utf-8');
        $xliff = $xml->createElement('xliff');
        $xliff->setAttribute('version', '1.0');
        $xliff->setAttribute('standalone', 'yes');
        $file = $xml->createElement('file');
        $file->setAttribute('source-language', 'en');
        if (func_num_args() > 2) {
            $file->setAttribute('target-language', $language);
        }
        $file->setAttribute('datatype', 'plaintext');
        $file->setAttribute('original', 'messages');
        $file->setAttribute('data', strftime('%Y-%m-%dT%H-%M-%S'));
        $file->setAttribute('product-name', 'typo3migration');
        $header = $xml->createElement('header');
        $file->appendChild($header);
        $body = $xml->createElement('body');
        foreach ($data[$language] as $key => $values) {
            $unit = $xml->createElement('trans-unit');
            $unit->setAttribute('id', $key);
            $unit->setAttribute('xml:space', 'preserve');
            $source = $xml->createElement('source', $values[0]['source']);
            $unit->appendChild($source);
            if (func_num_args() > 2) {
                $target = $xml->createElement('target', $translations[$key]);
                $unit->appendChild($target);
            }
            $body->appendChild($unit);
        }
        $file->appendChild($body);
        $xliff->appendChild($file);
        $xml->appendChild($xliff);
        $xml->formatOutput = true;
        return $xml->saveXML();
    }
}
