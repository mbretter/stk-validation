<?php

namespace StkTest\Validation\Resolver;

use PHPUnit\Framework\TestCase;
use Stk\Immutable\Map;
use Stk\Validation\Resolver;

class RespectTest extends TestCase
{

    public function testSimple()
    {
        $data = new Map([
            'name'  => 'Joe',
            'email' => 'john@doe.com'
        ]);

        $schema = [
            [
                'field'   => 'name',
                'rule'    => ['regex', '/^[\w_.-]+$/i'],
                'message' => 'Name contains invalid characters'
            ],
            [
                'field'   => 'name',
                'rule'    => ['notOptional'],
                'message' => 'Name must be provided'
            ],
            [
                'field'   => 'email',
                'rule'    => 'email',
                'message' => 'E-Mail is invalid'
            ]
        ];

        $resolver = new Resolver\Respect();
        $errors   = $resolver->resolve($data, $schema);
        $this->assertEmpty($errors);
    }

    public function testWithErrors()
    {
        $data = new Map([
            'name'  => 'Joe`',
            'email' => 'johndoe.com'
        ]);

        $schema = [
            [
                'field'   => 'name',
                'rule'    => ['regex', '/^[\w_.-]+$/i'],
                'message' => 'Name contains invalid characters'
            ],
            [
                'field'   => 'email',
                'rule'    => 'email',
                'message' => 'E-Mail is invalid'
            ]
        ];

        $resolver = new Resolver\Respect();
        $errors   = $resolver->resolve($data, $schema);
        $this->assertEquals([
            'name'  => 'Name contains invalid characters',
            'email' => 'E-Mail is invalid',
        ], $errors);
    }

    public function testNested()
    {
        $data = new Map([
            'person' => [
                'email' => 'john'
            ]
        ]);

        $schema = [
            [
                'field'   => ['person', 'email'],
                'rule'    => [
                    'allOf',
                    ['regex', '/^[\w_.-]+$/i'],
                    ['length', 6, 40]
                ],
                'message' => 'invalid email'
            ]
        ];

        $resolver = new Resolver\Respect();
        $errors   = $resolver->resolve($data, $schema);
        $this->assertEquals([
            'person.email' => 'invalid email',
        ], $errors);
    }

    public function testMandatory()
    {
        $data = new Map([
            'name' => '',
        ]);

        $schema = [
            [
                'field'   => 'name',
                'rule'    => 'notOptional',
                'message' => 'Name is mandatory'
            ],
            [
                'field'   => 'name',
                'rule'    => ['regex', '/^[\w_.-]+$/i'],
                'message' => 'Name contains invalid characters'
            ]
        ];

        $resolver = new Resolver\Respect();
        $errors   = $resolver->resolve($data, $schema);
        $this->assertEquals([
            'name' => 'Name is mandatory',
        ], $errors);
    }

    public function testWildcard()
    {
        $data = new Map([
            'color' => 'red',
            'lang'  => [
                'de' => [
                    'name'        => 'S$epp',
                    'description' => "Sepp%Maier"
                ],
                'en' => [
                    'name'        => 'J$oe',
                    'description' => "John Doe"
                ]
            ]
        ]);

        $schema = [
            [
                'field'   => 'color',
                'rule'    => 'hexRgbColor',
                'message' => 'Color is not a valid hex value'
            ],
            [
                'field'   => ['lang', '*', 'name'],
                'rule'    => ['regex', '/^[\w_.-]+$/i'],
                'message' => 'Name contains invalid characters'
            ]
        ];

        $resolver = new Resolver\Respect();
        $errors   = $resolver->resolve($data, $schema);
        $this->assertEquals([
            'lang.de.name' => 'Name contains invalid characters',
            'lang.en.name' => 'Name contains invalid characters',
            'color'        => 'Color is not a valid hex value'
        ], $errors);
    }

    public function testWildcardDuplicate()
    {
        $data = new Map([
            'color' => 'red',
            'lang'  => [
                'de' => [
                    'name'        => 'S$epp',
                    'description' => "Sepp Maier"
                ],
                'en' => [
                    'name'        => 'J$oe',
                    'description' => "John Doe"
                ]
            ]
        ]);

        $schema = [
            [
                'field'   => ['lang', 'de', 'name'],
                'rule'    => ['regex', '/^[\w_.-]+$/i'],
                'message' => 'Name is invalid'
            ],
            [
                'field'   => ['lang', '*', 'name'],
                'rule'    => ['regex', '/^[\w_.-]+$/i'],
                'message' => 'Name contains invalid characters'
            ]
        ];

        $resolver = new Resolver\Respect();
        $errors   = $resolver->resolve($data, $schema);
        $this->assertEquals([
            'lang.de.name' => 'Name is invalid',
            'lang.en.name' => 'Name contains invalid characters',
        ], $errors);
    }

    public function testWildcardMultiple()
    {
        $data = new Map([
            'color' => 'red',
            'lang'  => [
                'de' => [
                    'person'  => [
                        'name' => 'S$epp',
                    ],
                    'company' => [
                        'name' => '$$',
                    ]
                ],
                'en' => [
                    'person'  => [
                        'name' => 'John',
                    ],
                    'company' => [
                        'name' => '$$',
                    ]
                ]
            ]
        ]);

        $schema = [
            [
                'field'   => 'color',
                'rule'    => 'hexRgbColor',
                'message' => 'Color is not a valid hex value'
            ],
            [
                'field'   => ['lang', '*', '*', 'name'],
                'rule'    => ['regex', '/^[\w_.-]+$/i'],
                'message' => 'Name contains invalid characters'
            ]
        ];

        $resolver = new Resolver\Respect();
        $errors   = $resolver->resolve($data, $schema);
        $this->assertEquals([
            'color'                => 'Color is not a valid hex value',
            'lang.de.person.name'  => 'Name contains invalid characters',
            'lang.de.company.name' => 'Name contains invalid characters',
            'lang.en.company.name' => 'Name contains invalid characters',
        ], $errors);
    }

    public function testInvalidDefinition()
    {
        $data = new Map([
            'name' => '',
        ]);

        $schema = [
            [
                'field'   => 'name',
                'message' => 'Name is mandatory'
            ]
        ];

        $this->expectExceptionMessage('invalid rule definition.');
        $resolver = new Resolver\Respect();
        $resolver->resolve($data, $schema);
    }

    public function testWithEmptyRule()
    {
        $data = new Map([
            'name' => '',
        ]);

        $schema = [
            [
                'field'   => 'name',
                'rule'    => [],
                'message' => 'Name is invalid'
            ]
        ];

        $resolver = new Resolver\Respect();
        $errors   = $resolver->resolve($data, $schema);
        $this->assertEquals([
            'name' => 'Name is invalid'
        ], $errors);
    }

}