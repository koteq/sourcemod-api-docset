<?php

class SourcepawnLanguage extends HyperLanguage {
    public function __construct() {
        $this->setInfo(array(
            parent::NAME => 'SourcePawn',
            parent::VERSION => '1.0',
            parent::AUTHOR => array(
                parent::NAME => 'Reflex',
                parent::WEBSITE => '',
                parent::EMAIL => ''
            )
        ));

        $this->setExtensions(array('sm'));

        $common = array(
            'string', 'char', 'number', 'comment', 'commentline',
            'keyword' => array('', 'statement', 'operator', 'type', 'literal', 'core'),
            'identifier',
            'operator'
        );

        $this->addStates(array(
            'init' => array_merge(array('include', 'preprocessor'), $common),
            'include' => array('incpath'),
            'preprocessor' => array_merge($common, array('pp_newline')),
            'comment' => array('doctag'),
        ));

        $this->addRules(array(
            'whitespace' => RULE::ALL_WHITESPACE,
            'operator' => '/\+\+|--|<<|>>|>>>|\.\.\.|[-+*\/%^&|!~<>=,;:?()\[\]\{\}]|[-+*\/%^&|=!~<>]=|<<=|>>=|>>>=/',
            'include' => new Rule('/#\s*include/', '/\n/'),
            'preprocessor' => new Rule('/#\s*\w+/', '/\n/'),
            'pp_newline' => '/(?<!\\\\)(?:\\\\\\\\)*?\\\\\n/',
            'incpath' => '/<[^>]*>|"[^"]*"/',
            'string' => Rule::C_DOUBLEQUOTESTRING,
            'char' => Rule::C_SINGLEQUOTESTRING,
            'number' => Rule::C_NUMBER,
            'comment' => new Rule('#/\*#', '#\*/#'),
            'commentline' => '#//.*?\n#s',
            'doctag' => '/@\w+/',
            'keyword' => array(
                array(
                    // Pawn
                    'const', 'forward', 'native', 'new', 'operator', 'public',
                    'static', 'stock',
                    // SourcePawn
                    'enum', 'funcenum', 'struct',
                ),
                'statement' => array(
                    // Pawn
                    'assert', 'break', 'case', 'continue', 'default', 'do',
                    'else', 'exit', 'for', 'goto', 'if', 'return', 'sleep',
                    'state', 'switch', 'while',
                ),
                'operator' => array(
                    // Pawn
                    'deﬁned', 'sizeof', 'state', 'tagof',
                ),
                'type' => array(
                    // Pawn
                    'bool', 'Float',
                    // SourcePawn
                    'Function', 'String', 'Handle', 'any',
                ),
                'literal' => array(
                    // Pawn
                    'true', 'false',
                ),
                'core' => array(
                    'NULL_VECTOR', 'NULL_STRING',
                    'INVALID_HANDLE', 'INVALID_FUNCTION',
                    'Plugin_Continue', 'Plugin_Changed', 'Plugin_Handled', 'Plugin_Stop',
                ),
            ),
            'identifier' => Rule::C_IDENTIFIER,
        ));

        $this->addMappings(array(
            'operator' => '',
            'include' => 'preprocessor',
            'incpath' => 'tag',
        ));
    }
}

?>
