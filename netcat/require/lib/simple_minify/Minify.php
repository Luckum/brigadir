<?php
//namespace MatthiasMullie\Minify;

/**
 * Minify abstract class
 *
 * This source file can be used to write minifiers for multiple file types.
 *
 * The class is documented in the file itself. If you find any bugs help me out and report them. Reporting can be done by sending an email to minify@mullie.eu.
 * If you report a bug, make sure you give me enough information (include your code).
 *
 * License
 * Copyright (c) 2012, Matthias Mullie. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products derived from this software without specific prior written permission.
 *
 * This software is provided by the author "as is" and any express or implied warranties, including, but not limited to, the implied warranties of merchantability and fitness for a particular purpose are disclaimed. In no event shall the author be liable for any direct, indirect, incidental, special, exemplary, or consequential damages (including, but not limited to, procurement of substitute goods or services; loss of use, data, or profits; or business interruption) however caused and on any theory of liability, whether in contract, strict liability, or tort (including negligence or otherwise) arising in any way out of the use of this software, even if advised of the possibility of such damage.
 *
 * @author Matthias Mullie <minify@mullie.eu>
 * @version 1.2.0
 *
 * @copyright Copyright (c) 2012, Matthias Mullie. All rights reserved.
 * @license MIT License
 */
abstract class Minify {
    /**
     * The data to be minified
     *
     * @var array
     */
    protected $data = array();

    /**
     * Array of patterns to match.
     *
     * @var array
     */
    protected $patterns = array();

    /**
     * Init the minify class - optionally, css may be passed along already.
     *
     * @param string [optional] $css
     */
    public function __construct() {
    }

    /**
     * Add a file or straight-up code to be minified.
     *
     * @param string $data
     */
    public function add($data) {
        // redefine var
        $data = (string)$data;

        // load data
        $value = $this->load($data);
        $key = ($data != $value) ? $data : 0;

        // initialize key
        if (!array_key_exists($key, $this->data)) $this->data[$key] = '';

        // store data
        $this->data[$key] .= $value;
    }

    /**
     * Load data.
     *
     * @param  string $data Either a path to a file or the content itself.
     * @return string
     */
    protected function load($data) {
        // check if the data is a file
        if (@file_exists($data) && is_file($data)) {
            // grab content
            return @file_get_contents($data);
        } // no file, just return the data itself
        else return $data;
    }

    /**
     * Save to file
     *
     * @param string $content The minified data.
     * @param string $path The path to save the minified data to.
     */
    protected function save($content, $path) {
        // create file & open for writing
        if (($handler = @fopen($path, 'w')) === false) throw new Exception('The file "' . $path . '" could not be opened. Check if PHP has enough permissions.');

        // write to file
        if (@fwrite($handler, $content) === false) throw new Exception('The file "' . $path . '" could not be written to. Check if PHP has enough permissions.');

        // close the file
        @fclose($handler);
    }

    /**
     * Minify the data.
     *
     * @param  string [optional] $path    The path the data should be written to.
     * @return string           The minified data.
     */
    abstract public function minify($path = false);

    /**
     * Register a pattern to execute against the source content.
     * Patterns should always include caret (= start from the beginning of the
     * string) - processing will be performed by traversing the content
     * character by character, so we need the pattern to start matching
     * exactly at the first character of the content at that point.
     *
     * @param string $pattern PCRE pattern.
     * @param string|Closure[optional] $replacement Replacement value for matched pattern.
     * @param bool [optional] $skip Identifies if this match should be skipped by the rest of the patterns, once matched.
     * @throws Exception
     */
    protected function registerPattern($pattern, $replacement = '', $skip = false) {
        // doublecheck if pattern actually starts at beginning of content
        if (substr($pattern, 1, 1) !== '^') {
            throw new Exception('Pattern "' . $pattern . '" should start processing at the beginning of the string.');
        }

        $this->patterns[] = array($pattern, $replacement, $skip);
    }

    /**
     * We can't "just" run some regular expressions against JavaScript: it's a
     * complex language. E.g. having an occurrence of // xyz would be a comment,
     * unless it's used within a string. Of you could have something that looks
     * like a 'string', but inside a comment.
     * The only way to accurately replace these pieces is to traverse the JS one
     * character at a time and try to find whatever starts first.
     *
     * @param  string $content The content to replace patterns in.
     * @return string The (manipulated) content.
     */
    protected function replace($content) {
        return $content;
        // every character that has been processed will be moved to this string
        $processed = '';

        // update will keep shrinking, character by character, until all of it
        // has been processed
        while ($content) {
            $ignore = -1;

            for ($i = 0; $i < count($this->patterns); $i++) {
                // pattern to ignore (if it was previously matched)
                if ($i === $ignore) continue;

                list($pattern, $replacement, $skip) = $this->patterns[$i];
                if ($pattern === '/^\s*?;?\s*?$\s+/m') {
                    var_dump('start');
                    var_dump($content);
                }

                // replace pattern occurrences starting at this character
                list($content, $replacement, $match) = $this->replacePattern($pattern, $content, $replacement);
                if ($pattern === '/^\s*?;?\s*?$\s+/m') {
                    var_dump($content);
                    var_dump($match);
                    var_dump($replacement);
                }
                // pattern matched & content replaced; save replacement value
                if ($match != '' || $replacement != '') {
                    // some patterns will want to make sure that the replaced
                    // content is not touched by any other patterns (like
                    // strings are comments)
                    if ($skip) {
                        $processed .= $replacement;
                        $content = (string)substr($content, strlen($replacement));
                        continue 2;
                    }

                    // since there's been a change in the content, let's re-run
                    // all patterns at the start of the new content, but make
                    // sure the same pattern is ignored time
                    $ignore = $i;
                    $i = -1;
                }
            }

            // add this character to $processed & strip it from $content, moving
            // on to the next character
            $processed .= $content[0];
            $content = substr($content, 1);
        }

        // clear registered patterns
        $this->patterns = array();

        return $processed;
    }

    /**
     * This is where a pattern is matched against $content and the matches
     * are replaced by their respective value.
     * This function will be called plenty of times, where $content will always
     * move up 1 character.
     *
     * @param string $pattern Pattern to match.
     * @param string $content Content to match pattern against.
     * @param string|callable $replacement Replacement value.
     * @return array [content, replacement, match]
     */
    protected function replacePattern($pattern, $content, $replacement) {
        if (is_callable($replacement) || $replacement instanceof Closure) {
            return $this->replaceWithCallback($pattern, $content, $replacement);
        } else {
            return $this->replaceWithString($pattern, $content, $replacement);
        }
    }

    /**
     * Replaces pattern by a value from a callback, via preg_replace_callback.
     *
     * @param string $pattern Pattern to match.
     * @param string $content Content to match pattern against.
     * @param string|callable $replacement Replacement value.
     * @return array [content, replacement, match]
     */
    protected function replaceWithCallback($pattern, $content, $replacement) {
        $matched = '';
        $replaced = '';

        /*
         * Instead of just passing the $replacement callback, we'll wrap another
         * callback around it to also allow us to catch the match & replacement
         * value.
         */
        //$callback = function ($match) use ($replacement, &$replaced, &$matched) {
        /*$callback = function ($match) {
            $matched = $match;
            $replaced = call_user_func($replacement, $match);
            return $replaced;
        };*/
        $content = preg_replace_callback($pattern, $callback, $content, 1, $count);

        return array($content, $replaced, $matched);
    }

    /**
     * Replaces pattern by a value from a callback, via preg_replace.
     *
     * @param string $pattern Pattern to match.
     * @param string $content Content to match pattern against.
     * @param string|callable $replacement Replacement value.
     * @return array [content, replacement, match]
     */
    protected function replaceWithString($pattern, $content, $replacement) {
        /*
         * This preg_match is really only meant to capture $match, which we can
         * then also use to deduce the replacement value. We can't just assume
         * $replacement as replacement value, because it may be a back-reference
         * (e.g. \\1)
         */
        if (!preg_match($pattern, $content, $match)) {
            return array($content, '', '');
        }

        $untouched = strlen($content) - strlen($match[0]);
        $content = preg_replace($pattern, $replacement, $content, 1, $count);
        $replaced = (string)substr($content, 0, strlen($content) - $untouched);

        return array($content, $replaced, $match[0]);
    }
}
