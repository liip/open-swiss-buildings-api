<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Console;

use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Exception\MissingInputException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;

final class Autocomplete
{
    /**
     * Autocomplete adapted from Symfony console component.
     *
     * @param resource                     $inputStream
     * @param callable(string):list<mixed> $autocomplete
     * @param callable(mixed):string       $resultFormatter
     *
     * @see QuestionHelper::autocomplete()
     *
     * @todo move the callables into the constructor, including a type-safe template for the autocomplete() return
     */
    public static function autocomplete(OutputInterface $output, $inputStream, callable $autocomplete, callable $resultFormatter): void
    {
        $cursor = new Cursor($output, $inputStream);
        $ret = '';
        $i = 0;

        $sttyMode = shell_exec('stty -g');
        $metadata = stream_get_meta_data($inputStream);
        if (!\array_key_exists('uri', $metadata)) {
            throw new \RuntimeException('Input stream has no URI');
        }
        $isStdin = 'php://stdin' === $metadata['uri'];
        $r = [$inputStream];
        $w = [];

        // Disable icanon (so we can fread each keypress) and echo (we'll do echoing here instead)
        shell_exec('stty -icanon -echo');

        // Add highlighted text style
        $output->getFormatter()->setStyle('hl', new OutputFormatterStyle('black', 'white'));

        // Read a keypress
        while (!feof($inputStream)) {
            while ($isStdin && 0 === @stream_select($r, $w, $w, 0, 100)) {
                // Give signal handlers a chance to run
                $r = [$inputStream];
            }
            $c = fread($inputStream, 1);

            // as opposed to fgets(), fread() returns an empty string when the stream content is empty, not false.
            if (false === $c || ('' === $ret && '' === $c)) {
                shell_exec('stty ' . $sttyMode);
                throw new MissingInputException('Aborted.');
            }

            if ("\177" === $c) { // Backspace Character
                if (0 !== $i) {
                    --$i;
                    $cursor->moveLeft();
                }

                // Pop the last character off the end of our string
                $ret = mb_substr($ret, 0, $i);

                $matches = $autocomplete($ret);
            } elseif ("\033" === $c) {
                // Did we read an escape sequence?
                // @TODO: implement the cursor moving here?
                continue;
            } elseif (\ord($c) < 32) {
                continue;
            } else {
                if ("\x80" <= $c) {
                    $c .= fread($inputStream, ["\xC0" => 1, "\xD0" => 1, "\xE0" => 2, "\xF0" => 3][$c & "\xF0"]);
                }

                $output->write($c);
                $ret .= $c;
                ++$i;

                $matches = $autocomplete($ret);
            }
            $cursor->savePosition();
            $cursor->clearOutput();

            $cursor->moveToColumn(1)->moveDown();

            foreach ($matches as $match) {
                $output->writeln($resultFormatter($match));
            }

            $cursor->restorePosition();
        }

        // Reset stty so it behaves normally again
        shell_exec('stty ' . $sttyMode);
    }
}
