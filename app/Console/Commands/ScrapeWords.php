<?php
/**
 * ScrapeWords.php
 */

namespace App\Console\Commands;

use App\Picturnery\Words\Category;
use App\Picturnery\Words\Word;
use Illuminate\Console\Command;

/**
 * ScrapeWords
 *
 * @author  Ruud Seberechts
 * @package App\Console\Commands
 * @since   2020-03-28 18:22
 */
class ScrapeWords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'picturnery:scrape:palabrasaleatorias';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape words from palabrasaleatorias.com';

    public function handle()
    {
        $url = 'https://www.palabrasaleatorias.com/willekeurige-woorden.php?fs=10&fs2=0&Submit=Nieuw+woord';
        $timesWithoutNew = 0;

        $existingWords = [];

        do {
            $html =  file_get_contents($url);
            preg_match_all('/<div style="font-size:3em; color:#6200C5;">\s*(.+)<\/div>/iU', $html, $matches);
            $newWords = $matches[1];
            foreach ($newWords as $newWord) {
                if (!isset($existingWords[$newWord])) {
                    $existingWords[$newWord] = true;
                    $word = new Word();
                    $word->name = $newWord;
                    $word->locale = 'nl_NL';
                    $word->save();
                    echo(count($existingWords));
                } else {
                    $timesWithoutNew = 0;
                }
            }
        } while ($timesWithoutNew++ < 100);
    }
}