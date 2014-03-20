<?php
namespace Bcserv\SourcepawnIncParser\PawnElement;

use Bcserv\SourcepawnIncParser\PawnElement;

class PawnVariable extends PawnElement
{
    static $keywords = array(
        'new',
        'decl',
        'public',
        'const',
    );

    static function IsPawnElement($pawnParser)
    {
        if (in_array($pawnParser->GetCurrentWord(), self::$keywords)) {
            return true;
        }
        
        return false;
    }

    public function Parse()
    {
        parent::Parse();

        //$pp = $this->pawnParser;

        // We don't handle variables for now, so let's just skip this line.
        //fgets($pp->GetHandle()); $pp->Jump(-1);

        $this->ParseName();

        $this->pawnParser->Jump(-1);
    }

    protected function ParseName()
    {
        $pp = $this->pawnParser;

        do {
            $pp->SkipWhiteSpace();
            $keyword = $pp->GetWord();
        } while (in_array($keyword, self::$keywords));

        if (($pos = strpos($keyword, ':')) !== false) {
            $keyword = substr($keyword, $pos + 1);
        }
        if (($pos = strpos($keyword, '[')) !== false) {
            $keyword = substr($keyword, 0, $pos);
        }
        if (($pos = strpos($keyword, '(')) !== false) {
            $keyword = substr($keyword, 0, $pos);
        }
        if (($pos = strpos($keyword, ';')) !== false) {
            $keyword = substr($keyword, 0, $pos);
        }
        $this->name = $keyword;
    }
}
