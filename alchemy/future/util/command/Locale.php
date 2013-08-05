<?php
/**
 * Copyright (C) 2012 Dawid Kraczkowski
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR
 * A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace util\command;
use alchemy\util\CLI;
use alchemy\future\util\Console;
/**
 * Locale Console Command Handler
 *
 * locale:generate
 * locale:add {language}
 * locale:refresh
 */

class Locale
{
    public static function generate()
    {
        CLI::output('Are you sure you want to generate locale for application from current directory? [yes/no]');
        Console::instance()->switchContext(array(__CLASS__, '_confirmLocaleGeneration'));
    }

    public static function _confirmLocaleGeneration()
    {
        switch (trim(Console::instance()->getInput())) {
            case 'yes':
            case 'y':
                CLI::output('Generating template...');
                CLI::eol();
                self::executeGenerateLocale();
                break;
            case 'no':
            case 'n':
                CLI::output('Canceling...');
                CLI::eol();
                break;
            default:
                CLI::output('yes[y] or not[n]?');
        }
    }

    protected static function executeGenerateLocale()
    {
        self::$potArray = array();
        $cwd = getcwd();
        $cwdLength = strlen($cwd) + 1;
        $fileList = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($cwd));
        foreach($fileList as $name => $file) {
            if ($file->getExtension() != 'php') {
                continue;
            }
            CLI::output($file);
            $contents = file_get_contents($name);
            preg_match_all('/' . self::_REGEX . '/is', $contents, $matches, PREG_OFFSET_CAPTURE);

            //any _() calls found ?
            if ($length = count($matches[0])) {
                for ($i = 0; $i < $length; $i++) {
                    //strip slashes from single quotes and add only to double quotes
                    $msgId = addcslashes(stripslashes($matches[2][$i][0]), '"\\/');

                    //find line number
                    $lineNo = substr_count($contents, "\n", 0, $matches[0][$i][1]) + 1;

                    //create relative filename
                    $file = substr($name, $cwdLength) . ':' . $lineNo;

                    if (!isset(self::$potArray[$msgId])) {
                        self::$potArray[$msgId]['msgid'] = $msgId;
                        self::$potArray[$msgId]['files'] = array();

                    }
                    self::$potArray[$msgId]['files'][] = $file;
                }
                CLI::output(" [" . $i . " found]");
                CLI::eol();

            } else {
                CLI::output(" [0 found]");
                CLI::eol();
            }
        }
        CLI::eol();
        CLI::output('Choose save file for locale template or hit enter [' . self::DEFAULT_LOCALTE_TEMPLATE_FILE . ']');
        Console::instance()->switchContext(array(__CLASS__, '_chooseSavePotFile'));
    }

    public static function _chooseSavePotFile()
    {
        $filePath = Console::instance()->getInput();
        if (!$filePath) {
            $filePath = self::DEFAULT_LOCALTE_TEMPLATE_FILE;
        }
        $filePath = getcwd() . '/' . $filePath;

        $pathInfo = pathinfo($filePath);
        if (!is_dir($pathInfo['dirname'])) {
            mkdir($pathInfo['dirname'], 0755, true);
        }
        $potData = sprintf(self::POT_TEMPLATE, date('Y-m-d H:i:dO'), date('Y-m-d H:i:dO'));
        foreach (self::$potArray as $msgId => $msg) {
            $potData .= PHP_EOL . PHP_EOL . '#: ' . implode("\n#: ", $msg['files']) . PHP_EOL .
                        'msgid "' . $msgId . '"' . PHP_EOL . 'msgstr ""';
        }

        file_put_contents($filePath, $potData);

        self::$potArray = array();
        CLI::output('Locale template file saved!');
        CLI::eol();
    }

    const DEFAULT_LANGUAGE = 'en';
    const _REGEX = '_\\((["\'])((?:(?=(\\\?))\3.)*?)\1\\)';
    const DEFAULT_LOCALTE_TEMPLATE_FILE = 'locale/messages.pot';

    const POT_TEMPLATE = <<<POT
# Generated by alchemy framework <alchemyframework.org>
# To edit this file use poedit or other gettext catalog editor
msgid ""
msgstr ""
"Project-Id-Version: Alchemy framework 1.0\\n"
"POT-Creation-Date: %s\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"PO-Revision-Date: %s\\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\n"
"Language-Team: LANGUAGE <LL@li.org>\\n"
POT;

    protected static $potArray;
}
