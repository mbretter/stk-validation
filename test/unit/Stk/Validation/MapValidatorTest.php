<?php

namespace StkTest\Validation;

use PHPUnit\Framework\TestCase;
use Stk\Immutable\Map;
use Stk\Validation\MapValidator;

class MapValidatorTest extends TestCase
{
    protected MapValidator $validator;

    public function setUp(): void
    {
        parent::setUp();

        $this->validator = new MapValidator();
    }

    public function testSimple(): void
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

        $errors = $this->validator->validate($data, $schema);
        $this->assertEmpty($errors);
    }

    public function testWithErrors(): void
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

        $errors = $this->validator->validate($data, $schema);
        $this->assertEquals([
            'name'  => 'Name contains invalid characters',
            'email' => 'E-Mail is invalid',
        ], $errors);
    }

    public function testNested(): void
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

        $errors = $this->validator->validate($data, $schema);
        $this->assertEquals([
            'person.email' => 'invalid email',
        ], $errors);
    }

    public function testMandatory(): void
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

        $errors = $this->validator->validate($data, $schema);
        $this->assertEquals([
            'name' => 'Name is mandatory',
        ], $errors);
    }

    public function testMandatory2(): void
    {
        $data = new Map([
            'name' => '',
        ]);

        $schema = [
            [
                'field'   => 'name',
                'rule'    => [['notOptional'], ['stringType']],
                'message' => 'Name is mandatory'
            ],
        ];

        $errors = $this->validator->validate($data, $schema);
        $this->assertEquals([
            'name' => 'Name is mandatory',
        ], $errors);
    }

    public function testWildcard(): void
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

        $errors = $this->validator->validate($data, $schema);
        $this->assertEquals([
            'lang.de.name' => 'Name contains invalid characters',
            'lang.en.name' => 'Name contains invalid characters',
            'color'        => 'Color is not a valid hex value'
        ], $errors);
    }

    public function testWildcardDuplicate(): void
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

        $errors = $this->validator->validate($data, $schema);
        $this->assertEquals([
            'lang.de.name' => 'Name is invalid',
            'lang.en.name' => 'Name contains invalid characters',
        ], $errors);
    }

    public function testWildcardMultiple(): void
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

        $errors = $this->validator->validate($data, $schema);
        $this->assertEquals([
            'color'                => 'Color is not a valid hex value',
            'lang.de.person.name'  => 'Name contains invalid characters',
            'lang.de.company.name' => 'Name contains invalid characters',
            'lang.en.company.name' => 'Name contains invalid characters',
        ], $errors);
    }

    public function testInvalidDefinition(): void
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
        $this->validator->validate($data, $schema);
    }

    public function testWithEmptyRule(): void
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

        $errors = $this->validator->validate($data, $schema);
        $this->assertEquals([
            'name' => 'Name is invalid'
        ], $errors);
    }

    public function testChain(): void
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
                    ['regex', '/^[\w_.-]+$/i'],
                    ['length', 6, 40]
                ],
                'message' => 'invalid email'
            ]
        ];

        $errors = $this->validator->validate($data, $schema);
        $this->assertEquals([
            'person.email' => 'invalid email',
        ], $errors);
    }

    public function testChainOk(): void
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
                    ['regex', '/^[\w_.-]+$/i'],
                    ['length', 2, 40]
                ],
                'message' => 'invalid email'
            ]
        ];

        $errors = $this->validator->validate($data, $schema);
        $this->assertEmpty($errors);
    }

    public function testNullable(): void
    {
        $data = new Map([
            'person' => [
                'email' => null
            ]
        ]);

        $schema = [
            [
                'field'   => ['person', 'email'],
                'rule'    => [
                    'nullable',
                    'email'
                ],
                'message' => 'invalid email'
            ]
        ];

        $errors = $this->validator->validate($data, $schema);
        $this->assertEmpty($errors);
    }

    public function testOptional(): void
    {
        $data = new Map([
            'person' => [
                'email' => ''
            ]
        ]);

        $schema = [
            [
                'field'   => ['person', 'email'],
                'rule'    => [
                    'optional',
                    'email'
                ],
                'message' => 'invalid email'
            ]
        ];

        $errors = $this->validator->validate($data, $schema);
        $this->assertEmpty($errors);
    }

    public function testNotOptionalNonExistendVal(): void
    {
        $data = new Map();

        $schema = [
            [
                'field'   => ['person', 'email'],
                'rule'    => 'notOptional',
                'message' => 'email required'
            ]
        ];

        $errors = $this->validator->validate($data, $schema);
        $this->assertNotEmpty($errors);
        $this->assertEquals(['person.email' => 'email required'], $errors);
    }

    public function testNotOptionalWildcardNonExistendVal(): void
    {
        $data = new Map([
            'lang' => [
                'de' => [
                    'name' => 'Bob'
                ]
            ]
        ]);

        $schema = [
            [
                'field'   => ['lang', '*', 'email'],
                'rule'    => 'notOptional',
                'message' => 'email required'
            ]
        ];

        // does not work at the moment
        $errors = $this->validator->validate($data, $schema);
        $this->assertEmpty($errors);
    }

    public function testNullableNotNull(): void
    {
        $data = new Map([
            'person' => [
                'email' => 'x'
            ]
        ]);

        $schema = [
            [
                'field'   => ['person', 'email'],
                'rule'    => [
                    'nullable',
                    'email'
                ],
                'message' => 'invalid email'
            ]
        ];

        $errors = $this->validator->validate($data, $schema);
        $this->assertEquals([
            'person.email' => 'invalid email',
        ], $errors);
    }

    public function testWithKey(): void
    {
        $data = new Map([
            'person' => [
                'email' => 'x'
            ]
        ]);

        $schema = [
            [
                'field'   => ['person', 'email'],
                'key'     => 'person_email',
                'rule'    => [
                    'nullable',
                    'email'
                ],
                'message' => 'invalid email'
            ]
        ];

        $errors = $this->validator->validate($data, $schema);
        $this->assertEquals([
            'person_email' => 'invalid email',
        ], $errors);
    }

    public function testWithKeyArray(): void
    {
        $data = new Map([
            'person' => [
                'email' => 'x'
            ]
        ]);

        $schema = [
            [
                'field'   => ['person', 'email'],
                'key'     => ['errors', 'person_email'],
                'rule'    => [
                    'nullable',
                    'email'
                ],
                'message' => 'invalid email'
            ]
        ];

        $errors = $this->validator->validate($data, $schema);
        $this->assertEquals([
            'errors.person_email' => 'invalid email',
        ], $errors);
    }
}