<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class HttpTest
{
    public const FAKE_UUID = '00000000-0000-0000-0000-000000000000';

    public function __construct(
        public array $pathParams = [], // Paramètres pour remplacer les placeholder en {param} dans la route symfony
        public array $queryParams = [], // Paramètres GET de type ?query=value
        public ?string $name = null, // Nom unique
        public ?string $preTest = null, // Nom du test à executer avant celui ci
        public ?string $preRequestSQL = null,
        public array $json = [],
        public int $status = 200,
    ) {
    }
}
