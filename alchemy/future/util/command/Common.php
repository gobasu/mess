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
final class Common extends \alchemy\app\Controller
{
    public static function error($input)
    {
        $input = Console::instance()->getInput();
        CLI::output('Uknown command `' . $input . '` to help press `help`', 'white','red');
        CLI::eol();
    }

    public static function close()
    {
        CLI::output('bye!');
        exit(0);
    }

    public static function help()
    {

        $welcome = <<<WELCOME
      Welcome to alchemy util toolset
      ===============================
WELCOME;
        CLI::eol();
        CLI::multiLineCenteredOutput($welcome, 80);
        CLI::output('Command list:');
        CLI::eol();
        CLI::output("\t" . sprintf('%-30s', '- create:application [name] '), 'red');
        CLI::output('creates bootstrap application in current working directory');
        CLI::eol();
        CLI::output("\t" . sprintf('%-30s', '- locale:generate'), 'red');
        CLI::output('generates locale\'s template for current working directory');

        CLI::eol();
    }
}
