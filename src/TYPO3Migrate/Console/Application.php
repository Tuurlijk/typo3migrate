<?php
namespace MichielRoos\TYPO3Migrate\Console;

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

use Symfony\Component\Console\Application as BaseApplication;

/**
 * Class Application
 * @package MichielRoos\TYPO3Migrate\Console
 */
class Application extends BaseApplication
{
    /**
     * Generator: http://www.patorjk.com/software/taag/
     * Font: Slant
     *
     * @var string
     */
    private static $logo = "                               TYPO3    
    __  ____                  __  _                ______            __    
   /  |/  (_)___ __________ _/ /_(_)___  ____     /_  __/___  ____  / /____
  / /|_/ / / __ `/ ___/ __ `/ __/ / __ \/ __ \     / / / __ \/ __ \/ / ___/
 / /  / / / /_/ / /  / /_/ / /_/ / /_/ / / / /    / / / /_/ / /_/ / (__  ) 
/_/  /_/_/\__, /_/   \__,_/\__/_/\____/_/ /_/    /_/  \____/\____/_/____/  
         /____/                                                            

        https://github.com/tuurlijk/typo3migrate

          Hand coded with %s️ by Michiel Roos 

";

    /**
     * @return string
     */
    public function getHelp()
    {
        $love = $this->isColorSupported() ? "\e[31m♥\e[0m" : "♥";
        return sprintf(self::$logo, $love) . parent::getHelp();
    }

    /**
     * Check if color output is supported
     * @return bool
     */
    private function isColorSupported()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON';
        }
        return \function_exists('posix_isatty') && @posix_isatty(STDOUT);
    }
}
