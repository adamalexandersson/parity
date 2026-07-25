<?php

namespace Sprout\Schema;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

class SchemaValidator
{
    protected Validator $validator;

    protected object $schema;

    public function __construct(?string $schemaPath = null)
    {
        $schemaPath ??= dirname(__DIR__, 2).'/resources/schema/component.schema.json';

        $this->validator = new Validator;
        $this->schema = json_decode((string) file_get_contents($schemaPath), false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return list<array{path: string, message: string}>
     */
    public function validate(array $schema): array
    {
        $data = json_decode(json_encode($schema, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
        $result = $this->validator->validate($data, $this->schema);

        if ($result->isValid()) {
            return [];
        }

        $error = $result->error();

        if ($error === null) {
            return [['path' => '/', 'message' => 'Unknown schema validation error']];
        }

        $formatted = (new ErrorFormatter)->format($error);
        $issues = [];

        foreach ($formatted as $path => $messages) {
            foreach ((array) $messages as $message) {
                $issues[] = [
                    'path' => (string) $path,
                    'message' => (string) $message,
                ];
            }
        }

        return $issues;
    }

    /**
     * Feature keys derived from the JSON Schema enums (parity coverage source of truth).
     *
     * @return array<string, list<string>>
     */
    public static function featureCatalog(?string $schemaPath = null): array
    {
        $schemaPath ??= dirname(__DIR__, 2).'/resources/schema/component.schema.json';
        $schema = json_decode((string) file_get_contents($schemaPath), true, 512, JSON_THROW_ON_ERROR);
        $defs = $schema['$defs'] ?? [];

        return [
            'conditionOperators' => $defs['conditionOperators']['enum'] ?? [],
            'casts' => $defs['casts']['enum'] ?? [],
            'outcomeTypes' => $defs['outcomeTypes']['enum'] ?? [],
            'classRuleModes' => $defs['classRuleModes']['enum'] ?? [],
            'slotKinds' => $defs['slotKinds']['enum'] ?? [],
        ];
    }
}
