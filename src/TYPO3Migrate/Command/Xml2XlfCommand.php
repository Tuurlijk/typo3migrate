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
The <info>xml2xlf</info> command converts a T3locallang file to Xliff.

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

        $doc = simplexml_load_string(file_get_contents($xml));


        if (!count($doc->data->languageKey)) {
            throw new InvalidXmlFileException('Invalid .xml language file', 1545219496987);
        }

        $languages = $doc->data->languageKey;

        $languageKeys = [];
        foreach ($languages as $language) {
            $languageKeys[(string)$language->attributes()->index] = $language->label;
        }

        $output->writeln(sprintf('Found <info>%s</info> languages: <comment>%s</comment>', count($languages), implode(',', array_keys($languageKeys))));

        $data = [];
        foreach ($languageKeys as $languageKey => $labels) {
            $output->writeln(sprintf('Found <info>%s</info> language labels for language <comment>%s</comment>', count($labels), $languageKey));
            $data[$languageKey] = [];
            foreach ($labels as $label) {
                $key = (string)$label->attributes()->index;
                $label = (string)$label;
                $data[$languageKey][$key] = $label;
                $output->writeln(sprintf('<info>%s</info>: <comment>%s</comment>', $key, $label));
            }
        }

        $pathInfo = pathinfo($xml);
        $productName = $this->getExtKeyFromPath($xml);

        $filesystem = new Filesystem();
        foreach ($data as $language => $languageData) {
            if ($language === 'default') {
                $xlfFile = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $pathInfo['filename'] . '.xlf';
            } else {
                $xlfFile = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $language . '.' . $pathInfo['filename'] . '.xlf';
            }
            try {
                $filesystem->touch($xlfFile);
            } catch (IOExceptionInterface $exception) {
                echo 'An error occurred while creating the translation file at ' . $exception->getPath();
                exit;
            }
            $filesystem->dumpFile($xlfFile, $this->getXlf($data, $language, $productName));
            $output->writeln(sprintf('Wrote <comment>%s</comment> labels to: <info>%s</info>', $language, $xlfFile));
        }
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
     * @return string
     */
    protected function getXlf($data, $language = 'default', $productName = 'typo3migrate')
    {
        $isTranslation = false;
        if ($language !== 'default') {
            $isTranslation = true;
        }

        $xml = new \DOMDocument('1.0', 'utf-8');
        $xliff = $xml->createElement('xliff');
        $xliff->setAttribute('version', '1.0');
        $xliff->setAttribute('standalone', 'yes');
        $file = $xml->createElement('file');
        $file->setAttribute('source-language', 'en');
        if ($isTranslation) {
            $file->setAttribute('target-language', $language);
        }
        $file->setAttribute('datatype', 'plaintext');
        $file->setAttribute('original', 'messages');
        $file->setAttribute('data', strftime('%Y-%m-%dT%H-%M-%S'));
        $file->setAttribute('product-name', $productName);
        $header = $xml->createElement('header');
        $file->appendChild($header);
        $body = $xml->createElement('body');
        foreach ($data[$language] as $key => $value) {
            $unit = $xml->createElement('trans-unit');
            $unit->setAttribute('id', $key);
            $unit->setAttribute('xml:space', 'preserve');
            if ($isTranslation) {
                if (array_key_exists($key, $data['default'])) {
                    $source = $xml->createElement('source', htmlspecialchars($data['default'][$key]));
                    $unit->appendChild($source);
                }
                $target = $xml->createElement('target', htmlspecialchars($data[$language][$key]));
                $unit->appendChild($target);
            } else {
                $source = $xml->createElement('source', htmlspecialchars($data[$language][$key]));
                $unit->appendChild($source);
            }
            $body->appendChild($unit);
        }
        $file->appendChild($body);
        $xliff->appendChild($file);
        $xml->appendChild($xliff);
        $xml->formatOutput = true;
        return $xml->saveXML();
    }

    /**
     * Return the extension key from the path
     *
     * @param $path
     * @return string
     */
    protected function getExtKeyFromPath($path)
    {
        $extensionName = '';
        while ($dir = basename($path)) {
            if ($dir === 'ext') {
                return $extensionName;
            }
            $extensionName = $dir;
            $path = \dirname($path);
        }
        return $extensionName;
    }
}
