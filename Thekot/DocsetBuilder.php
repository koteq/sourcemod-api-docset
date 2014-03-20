<?php

/*
 * Copyright (c) 2014 thekot
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

namespace Thekot;

use Bcserv\SourcepawnIncParser\PawnElement\PawnComment;
use Bcserv\SourcepawnIncParser\PawnElement\PawnDefinition;
use Bcserv\SourcepawnIncParser\PawnElement\PawnEnum;
use Bcserv\SourcepawnIncParser\PawnElement\PawnFunction;
use Bcserv\SourcepawnIncParser\PawnElement\PawnStruct;
use Bcserv\SourcepawnIncParser\PawnElement\PawnVariable;
use Bcserv\SourcepawnIncParser\PawnElement;
use Bcserv\SourcepawnIncParser\PawnParser;

require_once __DIR__ . '/../vendor/hyperlight/hyperlight.php';


class DocsetBuilder extends \stdClass
{

    public function __construct($docset_name, $assets_dir, $include_dir, $output_dir)
    {
        $this->assets_dir = realpath($assets_dir);
        if ($this->assets_dir === false) {
            throw new \Exception('Assets dir is not exist');
        }
        $this->include_dir = realpath($include_dir);
        if ($this->include_dir === false) {
            throw new \Exception('Include dir is not exist');
        }
        $this->output_dir = realpath($output_dir);
        if ($this->assets_dir === false) {
            throw new \Exception('Output dir is not exist');
        }

        $this->db_file = $this->output_dir . "/$docset_name.docset/Contents/Resources/docSet.dsidx";
        $this->docset_dir = $this->output_dir . "/$docset_name.docset/";
        $this->contents_dir = $this->output_dir . "/$docset_name.docset/Contents/";
        $this->documents_dir = $this->output_dir . "/$docset_name.docset/Contents/Resources/Documents/";

        if (!file_exists($this->documents_dir)) {
            mkdir($this->documents_dir, 0777, true);
        }
    }

    public function build()
    {
        $this->prepareDatabase();
        $this->db->beginTransaction();
        foreach (glob($this->include_dir . '/*.inc') as $filename) {
            $this->current_docset_file = $this->convertIncFileToDocset($filename);
            $pawnParser = new PawnParser(array($this, '__pawnParserCallback'));
            $pawnParser->parseFile($filename);
            unset($pawnParser);
        }
        $this->db->commit();

        // add assets into docset
        copy($this->assets_dir . '/Info.plist', $this->contents_dir . '/Info.plist');
        copy($this->assets_dir . '/style.css', $this->documents_dir . '/style.css');
        copy($this->assets_dir . '/icon.png', $this->docset_dir . '/icon.png');
    }

    protected function prepareDatabase()
    {
        $this->db = new \PDO('sqlite:' . $this->db_file);
        $this->db->exec("DROP TABLE IF EXISTS searchIndex");
        $this->db->exec("DROP INDEX IF EXISTS anchor");
        $this->db->exec("CREATE TABLE searchIndex (id INTEGER PRIMARY KEY, name TEXT, type TEXT, path TEXT)");
        $this->db->exec("CREATE UNIQUE INDEX anchor ON searchIndex (name, type, path)");

        $insert = "INSERT OR IGNORE INTO searchIndex (name, type, path) VALUES (:name, :type, :path);";
        $this->stmt = $this->db->prepare($insert);
    }

    protected function convertIncFileToDocset($inc_filename)
    {
        $title = basename($inc_filename);
        $docset_filename = $this->documents_dir . '/' . basename($inc_filename) . '.html';
        $content = file_get_contents($inc_filename);
        $hyperlight = new \Hyperlight('sourcepawn');
        $content = $hyperlight->render($content);
        unset($hyperlight);

        // add <a neme="#"> to beggining of each line
        $content = preg_replace_callback("/^.*/m", function ($matches) {
            static $line_number = 0;
            $line_number += 1;
            return '<a name="' . $line_number . '"></a>' . $matches[0];
        }, $content);

        ob_start();
        // template uses $title and $content
        require $this->assets_dir . '/template.html.php';
        $content = ob_get_clean();

        file_put_contents($docset_filename, $content);
        return basename($docset_filename);
    }

    public function __pawnParserCallback($pawnElement)
    {
        /** @var PawnElement $pawnElement */
        $line = $pawnElement->GetLineStart();
        $pawnElementComment = $pawnElement->GetComment();
        if (!empty($pawnElementComment) && $pawnElementComment instanceof PawnComment) {
            /** @var PawnComment $pawnElementComment */
            $line = min($line, $pawnElementComment->GetLineStart());
        }
        if ($pawnElement instanceof PawnDefinition) {
            /** @var PawnDefinition $pawnElement */
            $this->addToSearchIndex("Define", $line, $pawnElement->GetName());
        }
        if ($pawnElement instanceof PawnEnum) {
            /** @var PawnEnum $pawnElement */
            $this->addToSearchIndex("Enum", $line, $pawnElement->GetName());
            foreach ($pawnElement->GetElements() as $enumElement) {
                if (is_array($enumElement)) {
                    $this->addToSearchIndex("Enum", $line, $enumElement['name']);
                }
            }
        }
        if ($pawnElement instanceof PawnFunction) {
            /** @var PawnFunction $pawnElement */
            $this->addToSearchIndex("Function", $line, $pawnElement->GetName());
        }
        if ($pawnElement instanceof PawnStruct) {
            /** @var PawnStruct $pawnElement */
            $this->addToSearchIndex("Struct", $line, $pawnElement->GetName());
        }
        if ($pawnElement instanceof PawnVariable) {
            /** @var PawnVariable $pawnElement */
            $this->addToSearchIndex("Variable", $line, $pawnElement->GetName());
        }
    }

    protected function addToSearchIndex($type, $line, $name)
    {
        if (empty($name) || strpos($name, '_') === 0) {
            return;
        }
        $file = basename($this->current_docset_file);
        $path = $file . '#' . $line;
        $this->insertSearchIndexLine($name, $type, $path);
    }

    protected function insertSearchIndexLine($name, $type, $path)
    {
        $this->stmt->bindValue(':name', $name, \PDO::PARAM_STR);
        $this->stmt->bindValue(':type', $type, \PDO::PARAM_STR);
        $this->stmt->bindValue(':path', $path, \PDO::PARAM_STR);
        $this->stmt->execute();
    }
}

